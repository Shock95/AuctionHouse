<?php
namespace shock95x\auctionhouse\database;

use Generator;
use shock95x\auctionhouse\event\AuctionEndEvent;
use shock95x\auctionhouse\database\utils\BinaryStringParser;
use shock95x\auctionhouse\database\utils\BinaryStringParserInstance;
use shock95x\auctionhouse\AuctionHouse;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\utils\Config;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;
use SOFe\AwaitGenerator\Await;

class Database {

	/** @var DataConnector */
	private $database;
	/** @var AuctionHouse  */
	private $plugin;
	/** @var Config  */
	private $config;
	/** @var BinaryStringParserInstance */
	private $parser;
	/** @var DataHolder */
	private $holder;

	/**
	 * @param AuctionHouse $plugin
	 * @param Config $config
	 */
	public function __construct(AuctionHouse $plugin, Config $config) {
		$this->plugin = $plugin;
		$this->config = $config;
	}

	/**
	 * @return Database
	 */
	public function connect() : Database {
		try {
			$this->database = libasynql::create($this->plugin, $this->config->get("database"), [
				"sqlite" => "statements/sqlite.sql",
				"mysql" => "statements/mysql.sql"
			]);
			$this->database->executeGeneric(Query::INIT);
		} catch(SqlError $error) {
			$this->plugin->getLogger()->error($error->getMessage());
		}
		$this->database->waitAll();

		$this->parser = BinaryStringParser::fromDatabase($this->config->get("database")["type"]);
		$this->holder = new DataHolder($this);
		$this->holder->loadListings();
		return $this;
	}

	public function getConnector() : DataConnector {
		return $this->database;
	}

	protected function asyncSelect(string $query, array $args = []): Generator {
		$this->database->executeSelect($query, $args, yield, yield Await::REJECT);
		return yield Await::ONCE;
	}

	public function save() {
		$list = DataHolder::getListings();
		foreach($list as $listing) {
			if(time() >= $listing->getEndTime()) {
				$listing->setExpired();
				(new AuctionEndEvent($listing, AuctionEndEvent::EXPIRED))->call();
			}
			$this->insert($listing->getSeller(true), $listing->getSeller(), $listing->getPrice(), (new BigEndianNBTStream())->writeCompressed($listing->getItem()->nbtSerialize()), $listing->getEndTime(), $listing->isExpired(), $listing->getMarketId());
		}
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
	public function insert(string $uuid, string $username, int $price, string $nbt, int $endTime, bool $expired = false, $id = null) {
		$this->database->executeInsert(Query::INSERT, [
			"uuid" => $uuid,
			"username" => $username,
			"price" => $price,
			"nbt" => $this->parser->encode($nbt),
			"id" => $id == null ? time() : $id,
			"end_time" => $endTime,
			"expired" => $expired
		]);
		$this->database->waitAll();
	}

	public function fetchAll() : Generator {
		$this->database->executeSelect(Query::FETCH_ALL, [], yield, yield Await::REJECT);
		return yield Await::ONCE;
	}

	public function deleteFromId(string $id) {
		$this->database->executeGeneric(Query::DELETE, ["id" => $id]);
	}

	public function close() {
		if(isset($this->database)) {
			$this->database->waitAll();
			$this->database->close();
		}
	}

	public function getParser() : BinaryStringParserInstance {
		return $this->parser;
	}

	public function getDatabase() : DataConnector {
		return $this->database;
	}
}
