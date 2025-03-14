<?php

namespace Taskov1ch\BANedetta;

use DateTime;
use IvanCraft623\languages\Language;
use IvanCraft623\languages\Translator;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use Symfony\Component\Filesystem\Path;
use Taskov1ch\BANedetta\commands\BanCommand;
use Taskov1ch\BANedetta\commands\UnbanCommand;
use Taskov1ch\BANedetta\managers\BansManager;
use Taskov1ch\BANedetta\managers\PostsManager;

class BANedetta extends PluginBase
{
	use SingletonTrait;

	private BansManager $bansManager;
	private PostsManager $postsManager;
	private Translator $translator;

	private array $postPlugins = [];

	public function onEnable(): void
	{
		self::setInstance($this);

		$this->bansManager = new BansManager($this);
		$this->postsManager = new PostsManager($this);

		$this->getServer()->getPluginManager()->registerEvents(new EventsListener($this), $this);

		$this->saveResources();
		$this->loadTranslations();
		$this->registerCommands();
		$this->initSchedules();
	}

	/**
	 * Returns the BansManager instance.
	 *
	 * @return BansManager The BansManager instance.
	 */
	public function getBansManager(): BansManager
	{
		return $this->bansManager;
	}

	/**
	 * Returns the PostsManager instance.
	 *
	 * @return PostsManager The PostsManager instance.
	 */
	public function getPostsManager(): PostsManager
	{
		return $this->postsManager;
	}

	/**
	 * Returns the Translator instance.
	 *
	 * @return Translator The Translator instance.
	 */
	public function getTranslator(): Translator
	{
		return $this->translator;
	}

	private function registerCommands(): void
	{
		$this->getServer()->getCommandMap()->registerAll("BANedetta", [
			new BanCommand($this, "bban", "Ban command.", "banedetta.ban"),
			new UnbanCommand($this, "unban", "Unban command.", "banedetta.unban")
		]);
	}

	private function saveResources(): void
	{
		$dirs = ["", "languages"];
		$resourceFolder = $this->getResourceFolder();

		foreach ($dirs as $dir) {
			$files = glob(Path::join($resourceFolder, $dir, "*.yml"));

			foreach ($files as $file) {
				$relativePath = str_replace($resourceFolder, "", $file);
				$this->saveResource($relativePath);
			}
		}
	}

	private function loadTranslations(): void
	{
		$defaultLang = $this->getConfig()->get("default_language");
		$files = glob(Path::join($this->getDataFolder(), "languages", "*.yml"));
		$this->translator = new Translator($this);

		foreach ($files as $file) {
			$langName = basename($file, ".yml");
			$lang = new Language(
				$langName,
				(new Config($file))->getAll()
			);

			$this->translator->registerLanguage($lang);

			var_dump($langName, $defaultLang);

			if ($langName === $defaultLang) {
				$this->translator->setDefaultLanguage($lang);
			}
		}
	}

	private function initSchedules(): void
	{
		$this->bansManager->getAllPendingDatas()->onCompletion(
			function (array $datas) {
				foreach ($datas as $data) {
					$date = new DateTime($data["created_at"]);
					$now = new DateTime();

					if ($date < $now) {
						$this->bansManager->notConfirm($data["id"]);
					} else {
						$interval = $now->diff($date);
						$seconds = $interval->days * 86400 +
							$interval->h * 3600 +
							$interval->i * 60 +
							$interval->s;
						$this->bansManager->schedule($data["id"], $seconds);
					}
				}
			},
			fn () => null
		);
	}
}
