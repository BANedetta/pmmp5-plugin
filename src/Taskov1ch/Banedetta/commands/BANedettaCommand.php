<?php

namespace Taskov1ch\BANedetta\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use Taskov1ch\BANedetta\BANedetta;

abstract class BANedettaCommand extends Command
{
	private array $admins;

	public function __construct(
		private BANedetta $main,
		string $command,
		string $description,
		string $permission
	) {
		parent::__construct($command, $description);
		$this->setPermission($permission);

		$this->admins = $main->getConfig()->get("admins");
	}

	protected function isAdmin(CommandSender $sender): bool
	{
		return
			$sender instanceof ConsoleCommandSender ||
			in_array(strtolower($sender->getName()), $this->admins);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args): void
	{
		$this->do($sender, $args);
	}

	abstract public function do(CommandSender $sender, array $args): void;
}
