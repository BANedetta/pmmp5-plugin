<div align="center">

![Cover](https://raw.githubusercontent.com/BANedetta/.github/refs/heads/main/cover.png)

**🔪 It's time to ban violators in front of everyone!**

</div>

## 📝 Description
This project will significantly increase trust in the "ban" command among regular players. Now, they will need to provide evidence that the ban was applied fairly 🔍 (otherwise, the accuser might get banned themselves! 😆).

When a player bans a violator 🚫, they are given a certain amount of time to present evidence of the violation. Then, moderators step in to confirm or reject the ban on the same platform.

For helping maintain order, the player may receive a well-deserved reward 🏅.

## 💻 Platforms
Out of the box, a plugin is provided for integration with **[VK](https://github.com/BANedetta/pmmp5-vk-posts)** and **[Telegram](https://github.com/BANedetta/pmmp5-tg-posts)**, but the plugin also allows for creating custom integration plugins for other platforms.

## 🛠️ For Developers
You can create your own third-party platform integration plugin by extending the [`PostPlugin.php`](src/Taskov1ch/BANedetta/posts/PostPlugin.php) class.

```php
<?php

namespace Example\Plugin;

use Taskov1ch/BANedetta/BANedetta;
use Taskov1ch/BANedetta/posts/PostPlugin;

class CustomPlatform extends PostPlugin // PostPlugin extends PluginBase
{
	public function onEnable(): void
	{
		BANedetta::getInstance()->getPostsManager()->registerPostPlugin($this); // Register this plugin in the BANedetta system
	}

	public function onRegistered(): void
	{
		// This method is called upon successful registration of the plugin in the BANedetta system
	}

	public function getDatabaseQueriesMap(): array
	{
		return [
			"mysql" => "path/to/mysql.sql",
			"sqlite" => "path/to/sqlite.sql"
		]; // Should return a map of your SQL queries (root starts from the plugin's resources directory, as in libasynql, when you register a class to create a DataConnector)
	}

	public function createPost(string $banned, string $by, string $reason, int $timeLimit): void
	{
		// This method is called when a player has been banned, and it's time to create posts.
		// Post creation logic goes here
	}

	public function removePost(string $banned): void
	{
		// This method is called when a player has been unbanned, and it's time to remove false posts.
		// Post removal logic goes here
	}

	public function confirmed(string $banned): void
	{
		// This method is called when a moderator has confirmed a ban on one of the platforms, and it's time to update posts
		// Logic to update the post to "Confirmed" goes here
	}

	public function notConfirmed(string $banned): void
	{
		// This method is called when a moderator has rejected a ban on one of the platforms, and it's time to update posts
		// Logic to update the post to "Not Confirmed" goes here
	}

}
```

## ⚠️ Attention!

If you find a bug, a shortcoming, or something even worse, please report it in **[ISSUES](https://github.com/BANedetta/pmmp5-plugin/issues)**. It won’t take much of your time, and I’ll provide a fix :)
