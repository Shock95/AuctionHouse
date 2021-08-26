<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\database;

use Generator;
use pocketmine\Server;
use pocketmine\utils\Config;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;
use shock95x\auctionhouse\AuctionHouse;
use SOFe\AwaitGenerator\Await;

class Database {

	private DataConnector $connector;
	private Config $config;
	private DataHolder $holder;

	public function __construct(Config $config) {
		$this->config = $config;
	}

	public function connect(): self {
		try {
			$this->connector = libasynql::create(AuctionHouse::getInstance(), $this->config->get("database"), [
				"sqlite" => "statements/sqlite.sql",
				"mysql" => "statements/mysql.sql"
			]);
			$this->connector->executeGeneric(Query::INIT);
		} catch(SqlError $error) {
			Server::getInstance()->getLogger()->error($error->getMessage());
		}
		$this->connector->waitAll();
		$this->holder = new DataHolder($this);
		$this->holder->loadListings();
		return $this;
	}

	/**
	 * @param string $uuid
	 * @param string $username
	 * @param int $price
	 * @param string $nbt
	 * @param int $endTime
	 * @param bool $expired
	 * @param int|null $id
	 */
	public function insert(string $uuid, string $username, int $price, string $nbt, int $endTime, bool $expired = false, ?int $id = null): void {
		$this->connector->executeInsert(Query::INSERT, [
			"uuid" => $uuid,
			"username" => $username,
			"price" => $price,
			"nbt" => $nbt,
			"id" => $id ?? time(),
			"end_time" => $endTime,
			"expired" => $expired
		]);
	}

	public function fetchAll(): Generator {
		$this->connector->executeSelect(Query::FETCH_ALL, [], yield, yield Await::REJECT);
		return yield Await::ONCE;
	}

	public function setExpired(int $id, bool $expired = true): void {
        $this->connector->executeGeneric(Query::SET_EXPIRED, ["id" => $id, "expired" => $expired]);
    }

	public function delete(int $id): void {
		$this->connector->executeGeneric(Query::DELETE, ["id" => $id]);
	}

	public function close(): void {
		if(isset($this->connector)) {
			$this->connector->waitAll();
			$this->connector->close();
		}
	}

	public function getConnector(): DataConnector {
		return $this->connector;
	}
}