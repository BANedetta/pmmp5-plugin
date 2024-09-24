<?php

namespace Taskov1ch\Banedetta\vk\async;

use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\Internet;
use Taskov1ch\Banedetta\vk\Vk;

class AsyncWallEdit extends AsyncTask
{
	public function __construct(private readonly string $params)
	{
	}

	public function onRun(): void
	{
		var_dump(Internet::getURL(Vk::ENDPOINT . "wall.edit?" . $this->params)->getBody());
	}

}
