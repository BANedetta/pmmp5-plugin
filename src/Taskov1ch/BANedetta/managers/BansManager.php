<?php

namespace Taskov1ch\BANedetta\managers;

use pocketmine\console\ConsoleCommandSender;
use pocketmine\player\Player;
use pocketmine\promise\Promise;
use pocketmine\scheduler\ClosureTask;
use poggit\libasynql\DataConnector;
use Taskov1ch\BANedetta\BANedetta;
use Taskov1ch\BANedetta\providers\libasynql;

class BansManager
{
	private libasynql $db;

	/**
	 * @var \pocketmine\scheduler\TaskHandler[]
	 */
	private array $schedules = [];

	public function __construct(private BANedetta $main)
	{
		$this->db = new libasynql($main);
	}

	/**
	 * Gets the database connector.
	 *
	 * @return DataConnector The database connector instance.
	 */
	public function getDataBase(): DataConnector
	{
		return $this->db->getDataBase();
	}

	/**
	 * Confirms a ban.
	 *
	 * @param string $nickname The player's nickname.
	 */
	public function confirm(string $nickname): void
	{
		$this->db->getData($nickname)->onCompletion(
			function (?array $data) {
				if (!$data || $data["confirmed"]) {
					return;
				}

				$this->removeSchedule($data["id"]);
				$this->db->confirm($data["id"]);

				$player = $this->main->getServer()->getPlayerExact($data["by"]);

				if ($player && $player->isOnline()) {
					$this->checkAndGiveRewards($player);
				}

				$this->main->getPostsManager()->confirm($data["id"]);
			},
			fn () => null
		);
	}

	/**
	 * Cancels the ban confirmation and unbans the player.
	 * If the ban was unjustified, the administrator who issued it will be banned for abuse.
	 *
	 * @param string $nickname The banned player's nickname.
	 */
	public function notConfirm(string $nickname): void
	{
		$this->db->getData($nickname)->onCompletion(
			function (?array $data) {
				if (!$data || $data["confirmed"] || $data["trigger"]) {
					return;
				}

				$this->removeSchedule($data["id"]);
				$this->unban($data["id"], false);

				$reason = $this->main->getTranslator()->translate(null, "for_sender.abuse_reason");
				$this->ban($data["by"], "console", $reason, true);

				$this->main->getPostsManager()->notConfirmed($data["id"]);
			},
			fn () => null
		);
	}

	/**
	 * @internal
	 */
	public function getType(): string
	{
		return $this->db->getType();
	}

	/**
	 * @internal
	 */
	public function checkAndKick(Player $player): void
	{
		$this->getData($player->getName())->onCompletion(
			function (?array $data) use ($player) {
				if ($data) {
					$this->kick($player, $data["by"], $data["reason"]);
				}
			},
			fn () => null
		);
	}

	/**
	 * @internal
	 */
	public function checkAndGiveRewards(Player $player): void
	{
		$this->getData($player->getName())->onCompletion(
			function (?array $data) use ($player) {
				if ($data && $data["confirmed"] && !$data["trigger"]) {
					$commands = array_map(
						fn (string $command) => str_replace(
							"{%player}",
							$data["by"],
							$command
						),
						$this->main->getConfig()->get("rewards")
					);
					$console = new ConsoleCommandSender(
						$this->main->getServer(),
						$this->main->getServer()->getLanguage()
					);

					foreach ($commands as $command) {
						$this->main->getServer()->dispatchCommand(
							$console,
							$command
						);
					}

					$message = $this->main->getTranslator()->translate($player, "for_sender.awarded");
					$player->sendMessage($message);

					$this->db->trigger($data["id"]);
				}
			},
			fn () => null
		);
	}

	/**
	 * @internal
	 */
	public function ban(string $nickname, string $by, string $reason, bool $isAdmin = true): void
	{
		$id = strtolower($nickname);
		$by = strtolower($by);

		$player = $this->main->getServer()->getPlayerExact($id);

		if ($player and $player->isOnline()) {
			$this->kick($player, $by, $reason);
		}

		if (!$isAdmin) {
			$this->main->getPostsManager()->createPost($id, $by, $reason);
			$this->schedule($id);
		}

		$this->db->ban(
			$id,
			$by,
			$reason,
			$isAdmin ? fn () => $this->db->trigger($id) : null
		);
	}

	/**
	 * @internal
	 */
	public function unban(string $nickname, bool $removePost = true): void
	{
		$id = strtolower($nickname);

		if ($removePost) {
			$this->main->getPostsManager()->removePost($id);
		}

		$this->removeSchedule($id);
		$this->db->unban($id);
	}

	/**
	 * @internal
	 */
	public function getData(string $nickname): Promise
	{
		$id = strtolower($nickname);

		return $this->db->getData($id);
	}

	/**
	 * @internal
	 */
	public function schedule(string $nickname, int $timeLimit = 0): void
	{
		$id = strtolower($nickname);

		$timeLimit = $timeLimit > 0 ?
			$timeLimit : $this->main->getConfig()->get("time_limit");

		if ($timeLimit > 0 and !isset($this->schedules[$id])) {
			$this->schedules[$id] = $this->main->getScheduler()->scheduleDelayedTask(new ClosureTask(
				fn () => $this->notConfirm($id)
			), 20 * $timeLimit);
		}
	}

	/**
	 * @internal
	 */
	public function removeSchedule(string $nickname): void
	{
		$id = strtolower($nickname);

		if (isset($this->schedules[$id])) {
			$this->schedules[$id]->remove();
			unset($this->schedules[$id]);
		}
	}

	/**
	 * @internal
	 */
	public function getAllPendingDatas(): Promise
	{
		return $this->db->getAllPendingDatas();
	}

	private function kick(Player $player, string $by, string $reason): void
	{
		$screen = $this->main->getTranslator()->translate(
			$player,
			"for_banned.screen",
			["{%by}" => $by, "{%reason}" => $reason]
		);

		$player->disconnect($screen);
	}
}
