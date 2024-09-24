<?php

namespace Taskov1ch\Banedetta\listeners;

use pocketmine\utils\SingletonTrait;
use Taskov1ch\Banedetta\Main;
use Taskov1ch\Banedetta\vk\events\VkEvent;

class VkEventsListener
{
	use SingletonTrait;

	public function __construct(private readonly Main $main)
	{
		self::setInstance($this);
	}

	public function WallReplyNewEvent(VkEvent $event): void
	{
		$data = $event->getUpdates();

		if (!$this->main->getVk()->isAdmin($data["from_id"])) {
			return;
		}

		$bans = $this->main->getBansManager();
		$bans->getDataByPostId("vk", $data["post_id"])->onCompletion(
			function (?array $row) use ($bans, $data): void {
				if ((!$row) or $row["confirmed"] !== null) {
					return;
				}

				match($data["text"]) {
					"+" => $bans->confirm($row["nickname"]),
					"-" => $bans->deny($row["nickname"]),
					default => null
				};
			},
			fn () => null
		);
	}

}
