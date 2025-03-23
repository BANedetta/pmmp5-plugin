<?php

namespace Taskov1ch\BANedetta;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\scheduler\ClosureTask;

class EventsListener implements Listener
{

	public function __construct(private BANedetta $main)
	{
	}

	public function onJoin(PlayerJoinEvent $event): void
	{
		$player = $event->getPlayer();

		$this->main->getScheduler()->scheduleDelayedTask(new ClosureTask(
			function () use ($player) {
				$this->main->getBansManager()->checkAndKick($player);
				$this->main->getBansManager()->checkAndGiveRewards($player);
			}
		), 10);
	}
}
