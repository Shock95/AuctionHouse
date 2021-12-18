<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\database;

use Generator;
use pocketmine\item\Item;
use pocketmine\utils\Config;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\storage\DataStorage;
use shock95x\auctionhouse\task\ListingExpireTask;
use SOFe\AwaitGenerator\Await;

class Database {

	private string $type;

	private AuctionHouse $plugin;
	private Config $config;
	private DataConnector $connector;

	public function __construct(AuctionHouse $plugin, Config $config) {
		$this->plugin = $plugin;
		$this->config = $config;
	}

	public function connect(): self {
		try {
			$this->type = $this->config->getNested("database.type");
			$this->connector = libasynql::create($this->plugin, $this->config->get("database"), [
				"sqlite" => "statements/sqlite.sql",
				"mysql" => "statements/mysql.sql"
			], true);

			$this->connector->executeGeneric(Query::INIT);
		} catch(SqlError $error) {
			$this->plugin->getLogger()->error($error->getMessage());
		} finally {
			$this->connector->waitAll();
			DataStorage::getInstance()->init($this);
			$this->plugin->getScheduler()->scheduleRepeatingTask(new ListingExpireTask($this), 1200);
		}
		return $this;
	}

	public function fetchAll(): Generator {
		$this->connector->executeSelect(Query::FETCH_ALL, [], yield, yield Await::REJECT);
		return yield Await::ONCE;
	}

	public function setExpired(int $id, bool $expired = true, ?callable $onSuccess = null, ?callable $onError = null): void {
        $this->connector->executeGeneric(Query::SET_EXPIRED, ["id" => $id, "expired" => $expired]);
    }

	public function delete(int $id, ?callable $onSuccess = null, ?callable $onError = null): void {
		$this->connector->executeGeneric(Query::DELETE, ["id" => $id], $onSuccess, $onError);
	}


	public function close(): void {
		$this->connector?->waitAll();
		$this->connector?->close();
	}

	public function getConnector(): DataConnector {
		return $this->connector;
	}

	public function getType(): string {
		return $this->type;
	}

	public function asyncSelect(string $query, array $args): Generator {
		$this->connector->executeSelect($query, $args, yield, yield Await::REJECT);
		return yield Await::ONCE;
	}

	public function asyncSelectRaw(string $query, array $args = []): Generator {
		$this->connector->executeSelectRaw($query, $args, yield, yield Await::REJECT);
		return yield Await::ONCE;
	}

	public function asyncGeneric(string $query, array $args = []): Generator {
		$this->connector->executeGeneric($query, $args, yield, yield Await::REJECT);
		return yield Await::ONCE;
	}

	public function asyncGenericRaw(string $query, array $args = []): Generator {
		$this->connector->executeGenericRaw($query, $args, yield, yield Await::REJECT);
		return yield Await::ONCE;
	}

	public function asyncChangeRaw(string $query, array $args = []): Generator {
		$this->connector->executeChangeRaw($query, $args, yield, yield Await::REJECT);
		return yield Await::ONCE;
	}

	public function createListingFromRows(array $rows): AHListing {
		return new AHListing(
			$rows["id"],
			$rows["uuid"],
			$rows["price"],
			$rows["username"],
			$rows["created"],
			$rows["end_time"],
			boolval($rows["expired"]),
			Item::jsonDeserialize(json_decode($rows["item"], true))
		);
	}
}