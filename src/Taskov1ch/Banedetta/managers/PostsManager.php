<?php

namespace Taskov1ch\BANedetta\managers;

use Taskov1ch\BANedetta\BANedetta;
use Taskov1ch\BANedetta\posts\PostPlugin;

class PostsManager
{
	private array $plugins = [];

	public function __construct(private BANedetta $main)
	{
	}

	/**
	 * Registers a plugin for managing posts on a specific platform.
	 *
	 * This method adds a new PostPlugin to the list of registered plugins.
	 * It checks if a plugin with the same name already exists or if the plugin is disabled.
	 * If either of these conditions is true, the plugin will not be added.
	 *
	 * @param PostPlugin $plugin The plugin to add.
	 * @return bool Returns true if the plugin was successfully added, otherwise false.
	 */
	public function registerPostPlugin(PostPlugin $plugin): bool
	{
		$name = strtolower($plugin->getName());

		if (
			isset($this->plugins[$name]) ||
			!$plugin->isEnabled()
		) {
			return false;
		}

		$db = $plugin->getBansManager()->getDataBase();
		$map = $plugin->getDatabaseQueriesMap();

		$type = $plugin->getBansManager()->getType();
		$db->loadQueryFile($plugin->getResource($map[$type]));

		$plugin->onRegistered();

		$this->plugins[] = $plugin;
		return true;
	}

	public function createPost(string $banned, string $by, string $reason): void
	{
		$timeLimit = $this->main->getConfig()->get("time_limit");

		foreach ($this->plugins as $plugin) {
			$plugin->createPost($banned, $by, $reason, $timeLimit);
		}
	}

	public function removePost(string $banned): void
	{
		foreach ($this->plugins as $plugin) {
			$plugin->removePosts($banned);
		}
	}

	public function confirm(string $banned): void
	{
		foreach ($this->plugins as $plugin) {
			$plugin->confirmed($banned);
		}
	}

	public function notConfirmed(string $banned): void
	{
		foreach ($this->plugins as $plugin) {
			$plugin->notConfirmed($banned);
		}
	}
}
