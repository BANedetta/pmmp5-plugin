<?php

namespace Taskov1ch\BANedetta\providers;

use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use Taskov1ch\BANedetta\BANedetta;
use Taskov1ch\BANedetta\libs\poggit\libasynql\DataConnector;
use Taskov1ch\BANedetta\libs\poggit\libasynql\libasynql as DataBase;

class libasynql
{
	private DataConnector $db;
	private string $type;

	public function __construct(BANedetta $main)
	{
		$config = $main->getConfig()->get("database");
		$this->type = $config["type"];

		$this->db = DataBase::create(
			$main, $config,
			[
				"mysql" => "database/mysql.sql",
				"sqlite" => "database/sqlite.sql"
			]
		);

		$this->db->executeGeneric("table.init");
		$this->db->waitAll();
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getData(string $id): Promise
	{
		$id = strtolower($id);
		$promise = new PromiseResolver();

		$this->db->executeSelect(
			"data.get_data",
			compact("id"),
			fn (array $data) => $promise->resolve($data[0] ?? []),
			fn () => $promise->reject()
		);

		return $promise->getPromise();
	}

	public function ban(string $id, string $by, string $reason, ?callable $onCompletion = null): void
	{
		$id = strtolower($id);
		$this->db->executeInsert("data.ban", compact("id", "by", "reason"), $onCompletion);
	}

	public function unban(string $id, ?callable $onCompletion = null): void
	{
		$id = strtolower($id);
		$this->db->executeInsert("data.unban", compact("id"), $onCompletion);
	}

	public function confirm(string $id, ?callable $onCompletion = null): void
	{
		$id = strtolower($id);
		$this->db->executeInsert("data.confirm", compact("id"), $onCompletion);
	}

	public function trigger(string $id, ?callable $onCompletion = null): void
	{
		$id = strtolower($id);
		$this->db->executeInsert("data.trigger", compact("id"), $onCompletion);
	}

	public function getAllPendingDatas(): Promise
	{
		$promise = new PromiseResolver();

		$this->db->executeSelect(
			"data.get_all_pending_datas",
			[],
			fn (array $data) => $promise->resolve($data),
			fn () => $promise->reject()
		);

		return $promise->getPromise();
	}

	public function getDataBase(): DataConnector
	{
		return $this->db;
	}
}
