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
	* Returns an array of database query file names.
	*
	* This method should return an array of strings, where each string
	* represents the name of a SQL file containing database queries.
	* These files will be loaded to initialize the necessary database
	* tables and structures for the plugin.
	*
	* @return array<string> An array of database query file names.
	*/
	abstract public function getDatabaseQueries(): array;

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
	 * @param string $banned The name of the player whose posts should be removed.
	 */
	abstract public function removePost(string $banned): void;

	/**
	 * Confirms the ban post.
	 *
	 * @param PostPlugin $by The instance of the post plugin that confirms the ban.
	 */
	abstract public function confirmed(string $id): void;

	/**
	 * Rejects the ban post.
	 *
	 * @param PostPlugin $by The instance of the post plugin that rejects the ban.
	 */
	abstract public function notConfirmed(string $id): void;

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
