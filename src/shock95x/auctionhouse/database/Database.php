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
use shock95x\auctionhouse\database\utils\BinaryStringParser;
use shock95x\auctionhouse\database\utils\BinaryStringParserInstance;
use SOFe\AwaitGenerator\Await;

class Database {

	/** @var DataConnector */
	private $database;
	/** @var Config  */
	private $config;
	/** @var BinaryStringParserInstance */
	private $parser;
	/** @var DataHolder */
	private $holder;

	/**
	 * @param Config $config
	 */
	public function __construct(Config $config) {
		$this->config = $config;
	}

	/**
	 * @return Database
	 */
	public function connect(): self {
		try {
			$this->database = libasynql::create(AuctionHouse::getInstance(), $this->config->get("database"), [
				"sqlite" => "statements/sqlite.sql",
				"mysql" => "statements/mysql.sql"
			]);
			$this->database->executeGeneric(Query::INIT);
		} catch(SqlError $error) {
			Server::getInstance()->getLogger()->error($error->getMessage());
		}
		$this->database->waitAll();

		$this->parser = BinaryStringParser::fromDatabase($this->config->get("database")["type"]);
		$this->holder = new DataHolder($this);
		$this->holder->loadListings();
		return $this;
	}



	protected function asyncSelect(string $query, array $args = []): Generator {
		$this->database->executeSelect($query, $args, yield, yield Await::REJECT);
		return yield Await::ONCE;
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
	public function insert(string $uuid, string $username, int $price, string $nbt, int $endTime, bool $expired = false, $id = null): void {
		$this->database->executeInsert(Query::INSERT, [
			"uuid" => $uuid,
			"username" => $username,
			"price" => $price,
			"nbt" => $this->parser->encode($nbt),
			"id" => $id == null ? time() : $id,
			"end_time" => $endTime,
			"expired" => $expired
		]);
	}

	public function fetchAll(): Generator {
		$this->database->executeSelect(Query::FETCH_ALL, [], yield, yield Await::REJECT);
		return yield Await::ONCE;
	}

	public function setExpired(int $id, bool $expired = true): void {
        $this->database->executeGeneric(Query::EXPIRED, ["id" => $id, "expired" => $expired]);
    }

	public function delete(int $id): void {
		$this->database->executeGeneric(Query::DELETE, ["id" => $id]);
	}

	public function close(): void {
		if(isset($this->database)) {
			$this->database->waitAll();
			$this->database->close();
		}
	}

	public function getConnector(): DataConnector {
		return $this->database;
	}

	public function getParser(): BinaryStringParserInstance {
		return $this->parser;
	}
}