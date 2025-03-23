<?php

namespace Taskov1ch\BANedetta\commands;

use pocketmine\command\CommandSender;

class BanCommand extends BANedettaCommand
{

	public function do(CommandSender $sender, array $args): void
	{
		$translator = $this->main->getTranslator();

		if (count($args) < 2) {
			$message = $translator->translate($sender, "for_sender.ban_command.usage");
			$sender->sendMessage($message);
			return;
		}

		$target = strtolower(array_shift($args));
		$by = strtolower($sender->getName());

		$isAdmin = $this->isAdmin($sender);
		$targetIsAdmin = $this->isAdmin($target);

		if ($targetIsAdmin) {
			$message = $translator->translate($sender, "for_sender.ban_command.is_admin");
			$sender->sendMessage($message);
			return;
		}

		$reason = implode(" ", $args);

		if (strlen($reason) > 200) {
			$message = $translator->translate($sender, "for_sender.ban_command.long_reason");
			$sender->sendMessage($message);
			return;
		}

		$this->main->getBansManager()->ban($target, $by, $reason, $isAdmin);

		$message = $translator->translate(
			$sender,
			"for_sender.ban_command.success" . ($isAdmin ? ".for_admin" : ""),
			["{%banned}" => $target, "{%reason}" => $reason]
		);
		$sender->sendMessage($message);
	}
}
