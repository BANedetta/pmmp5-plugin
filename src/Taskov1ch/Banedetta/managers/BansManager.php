<?php

namespace Taskov1ch\BANedetta\managers;

use pocketmine\console\ConsoleCommandSender;
use pocketmine\player\Player;
use pocketmine\promise\Promise;
use pocketmine\scheduler\ClosureTask;
use Taskov1ch\BANedetta\BANedetta;
use Taskov1ch\BANedetta\libs\poggit\libasynql\DataConnector;
use Taskov1ch\BANedetta\providers\libasynql;

class BansManager
{
	private libasynql $db;

	/**
	 * @var \pocketmine\scheduler\TaskHandler[]
	 */
	private array $schedules = [];

	/**
	 * @param BANedetta $main Instance of the main plugin class.
	 */
	public function __construct(private BANedetta $main)
	{
		$this->db = new libasynql($main);
	}

	/**
	 * Checks if a player is banned and kicks them if they are.
	 *
	 * @param Player $player The player to check.
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
	 * Checks if the player can receive rewards and grants them if they are eligible.
	 *
	 * @param Player $player The player to check.
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
	 * Bans a player.
	 *
	 * @param string $nickname The player's nickname.
	 * @param string $by The name of the administrator who issued the ban.
	 * @param string $reason The reason for the ban.
	 * @param bool $isAdmin Flag indicating whether the ban was issued by an admin (default is true).
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
		} else {
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
	 * Unbans a player.
	 *
	 * @param string $nickname The player's nickname.
	 * @param bool $removePost Flag indicating whether to remove the post about the ban (default is true).
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
	 * Gets the ban data for a player.
	 *
	 * @param string $nickname The player's nickname.
	 * @return Promise A promise that resolves with the ban data (array) or null if the player is not banned.
	 */
	public function getData(string $nickname): Promise
	{
		$id = strtolower($nickname);

		return $this->db->getData($id);
	}

	/**
	 * Confirms a ban.
	 *
	 * @param string $id The ID of the ban.
	 */
	public function confirm(string $id): void
	{
		$this->db->getData($id)->onCompletion(
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
	 * If the ban was wrong, it bans the administrator for abuse.
	 *
	 * @param string $id The ID of the ban.
	 */
	public function notConfirm(string $id): void
	{
		$this->db->getData($id)->onCompletion(
			function (?array $data) {
				if (!$data || $data["confirmed"] || $data["trigger"]) {
					return;
				}

				$this->removeSchedule($data["id"]);
				$this->unban($data["id"]);

				$reason = $this->main->getTranslator()->translate(null, "for_sender.abuse_reason");
				$this->ban($data["by"], "console", $reason, true);

				$this->main->getPostsManager()->notConfirmed($data["id"]);
			},
			fn () => null
		);
	}

	/**
	 * Schedules the cancellation of the ban confirmation after a specified time.
	 *
	 * @param string $id The ID of the ban.
	 * @param int $timeLimit The time in seconds before the confirmation cancellation (defaults to 0, which means using the time limit from the config).
	 */
	public function schedule(string $id, int $timeLimit = 0): void
	{
		$id = strtolower($id);
		$timeLimit = $timeLimit > 0 ?
			$timeLimit : $this->main->getConfig()->get("time_limit");

		if ($timeLimit > 0 and !isset($this->schedules[$id])) {
			$this->schedules[$id] = $this->main->getScheduler()->scheduleDelayedTask(new ClosureTask(
				fn () => $this->notConfirm($id)
			), 20 * $timeLimit);
		}
	}

	/**
	 * Removes the scheduled task for canceling the ban confirmation.
	 *
	 * @param string $id The ID of the ban.
	 */
	public function removeSchedule(string $id): void
	{
		$id = strtolower($id);

		if (isset($this->schedules[$id])) {
			if (!$this->schedules[$id]->isCancelled()) {
				$this->schedules[$id]->remove();
			}

			unset($this->schedules[$id]);
		}
	}

	/**
	 * Gets all pending ban data.
	 *
	 * @return Promise A promise that resolves with all pending ban data.
	 */
	public function getAllPendingDatas(): Promise
	{
		return $this->db->getAllPendingDatas();
	}

	/**
	 * Gets the database connector.
	 *
	 * @return DataConnector The database connector.
	 */
	public function getDataBase(): DataConnector
	{
		return $this->db->getDataBase();
	}

	/**
	 * Kicks a player with a ban message.
	 *
	 * @param Player $player The player to kick.
	 * @param string $by The name of the administrator who issued the ban.
	 * @param string $reason The reason for the ban.
	 */
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
