<?php

namespace Taskov1ch\BANedetta\posts;

use pocketmine\plugin\PluginBase;
use Taskov1ch\BANedetta\BANedetta;
use Taskov1ch\BANedetta\managers\BansManager;

/**
 * Abstract class PostPlugin
 *
 * This class serves as a base for plugins that handle posting ban information.
 * It provides methods for creating and removing ban posts, as well as accessing the BansManager.
 */
abstract class PostPlugin extends PluginBase
{

	/**
	 * Called when the plugin is registered in the PostsManager.
	 *
	 * This method is called after the plugin has been registered in the
	 * PostsManager. It is a good place to perform any initialization
	 * tasks that depend on the plugin being fully integrated into the
	 * BANedetta system.
	 */
	public function onRegistered(): void
	{
	}

	/**
	 * Returns a map of database query files for different database types.
	 *
	 * This method should return an associative array where the keys are database
	 * types (e.g., "mysql", "sqlite") and the values are the paths to the
	 * corresponding SQL files containing the database schema and queries.
	 *
	 * @return array<string, string> An associative array mapping database types to SQL file paths.
	 * Example: ["mysql" => "database/mysql.sql", "sqlite" => "database/sqlite.sql"]
	 */
	abstract public function getDatabaseQueriesMap(): array;

	/**
	 * Creates a ban post.
	 *
	 * @param string $banned The name of the banned player.
	 * @param string $by The name of the player who banned the target.
	 * @param string $reason The reason for the ban.
	 * @param int $timeLimit The number of seconds given to present evidence.
	 */
	abstract public function createPost(string $banned, string $by, string $reason, int $timeLimit): void;

	/**
	 * Removes a ban post associated with a specific player.
	 *
	 * @param string $banned The name of the banned player.
	 */
	abstract public function removePost(string $banned): void;

	/**
	 * Confirms the ban post.
	 *
	 * @param string $banned The name of the banned player.
	 */
	abstract public function confirmed(string $banned): void;

	/**
	 * Rejects the ban post.
	 *
	 * @param string $banned The name of the banned player.
	 */
	abstract public function notConfirmed(string $banned): void;

	/**
	 * Gets the BansManager instance.
	 *
	 * @return BansManager The BansManager instance.
	 */
	public function getBansManager(): BansManager
	{
		return BANedetta::getInstance()->getBansManager();
	}
}
