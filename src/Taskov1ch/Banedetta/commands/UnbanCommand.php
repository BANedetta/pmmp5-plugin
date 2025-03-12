<?php

namespace Taskov1ch\BANedetta\commands;

use pocketmine\command\CommandSender;
use Taskov1ch\BANedetta\BANedetta;

class UnbanCommand extends BANedettaCommand
{
	private BANedetta $main;

	public function do(CommandSender $sender, array $args): void
	{
		$translator = $this->main->getTranslator();

		if (count($args) != 1) {
			$message = $translator->translate($sender, "for_sender.unban_command.usage");
			$sender->sendMessage($message);
			return;
		}

		$target = strtolower(array_shift($args));
		$manager = $this->main->getBansManager();

		$manager->getData($target)->onCompletion(
			function (?array $data) use ($sender, $manager, $translator): void {
				if (!$data) {
					$message = $translator->translate($sender, "for_sender.unban_command.not_banned");
					$sender->sendMessage($message);
					return;
				}

				$manager->unban($data["id"]);

				$message = $translator->translate($sender, "for_sender.unban_command.success");
				$sender->sendMessage($message);
			},
			fn () => null
		);
	}
}
