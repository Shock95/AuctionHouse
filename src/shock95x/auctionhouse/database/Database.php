<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\database;

use Generator;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\result\SqlChangeResult;
use poggit\libasynql\result\SqlInsertResult;
use poggit\libasynql\result\SqlSelectResult;
use poggit\libasynql\SqlError;
use poggit\libasynql\SqlResult;
use poggit\libasynql\SqlThread;
use Ramsey\Uuid\UuidInterface;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\legacy\LegacyConverter;
use shock95x\auctionhouse\database\storage\DataStorage;
use shock95x\auctionhouse\event\AuctionStartEvent;
use shock95x\auctionhouse\task\SQLiteExpireTask;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;
use SOFe\AwaitGenerator\Await;

enum DatabaseType: string {
	case MySQL = "mysql";
	case SQLite = "sqlite";
}

class Database {

	private DatabaseType $type;
	private DataConnector $connector;
	private AuctionHouse $plugin;

	public function __construct(AuctionHouse $plugin) {
		$this->plugin = $plugin;
	}

	public function connect(Config $config): self {
		try {
			$this->type = DatabaseType::from($config->getNested("database.type"));
			$this->connector = libasynql::create($this->plugin, $config->get("database"), [
				"sqlite" => "statements/sqlite.sql",
				"mysql" => "statements/mysql.sql"
			], true);

		} catch(SqlError $error) {
			$this->plugin->getLogger()->error($error->getMessage());
		}
		Await::f2c(function () {
			yield from $this->executeMultiAsync(Query::INIT);
			LegacyConverter::getInstance()->init($this);
			yield from LegacyConverter::getInstance()->convert();
			if($this->type == DatabaseType::SQLite) {
				yield from $this->asyncChangeRaw("PRAGMA foreign_keys = ON;");
				$this->plugin->getScheduler()->scheduleRepeatingTask(new SQLiteExpireTask($this), 1200);
			} else if($this->type == DatabaseType::MySQL) {
				yield from $this->connector->asyncChange("auctionhouse.init.events", ["duration" => Settings::getExpiredDuration()]);
			}
		});
		$this->connector->waitAll();
		DataStorage::getInstance()->init($this);
		return $this;
	}

	public function getConnector(): DataConnector {
		return $this->connector;
	}

	public function getType(): DatabaseType {
		return $this->type;
	}

	public function close(): void {
		$this->connector->waitAll();
		$this->connector->close();
	}

	public function getListingById(int $id, callable $callback): void {
		$this->connector->executeSelect(Query::FETCH_ID, ["id" => $id], function(array $rows) use ($callback): void {
			$callback(empty($rows) ? null : AHListing::fromRow($rows[0]));
		});
	}

	public function getListings(callable $callback, int $offset = 0, int $limit = 45) {
		$this->connector->executeSelect(Query::FETCH_ALL, ["id" => $offset, "limit" => $limit], function(array $rows) use ($callback): void {
			/** @var AHListing[] $listings */
			$listings = [];
			foreach ($rows as $listing) {
				$listings[] = AHListing::fromRow($listing);
			}
			$callback($listings);
		});
	}

	public function getListingsByUsername(callable $callback, string $username, int $offset = 0, int $limit = 45): void {
		$this->connector->executeSelect(Query::FETCH_USERNAME, ["username" => $username, "id" => $offset, "limit" => $limit], function(array $rows) use ($callback): void {
			/** @var AHListing[] $listings */
			$listings = [];
			foreach ($rows as $listing) {
				$listings[] = AHListing::fromRow($listing);
			}
			$callback($listings);
		});
	}

	public function getActiveListings(callable $callback, int $offset = 0, int $limit = 45) {
		$this->connector->executeSelect(Query::FETCH_ACTIVE_NEXT, ["id" => $offset, "limit" => $limit], function(array $rows) use ($callback): void {
			/** @var AHListing[] $listings */
			$listings = [];
			foreach ($rows as $listing) {
				$listings[] = AHListing::fromRow($listing);
			}
			$callback($listings);
		});
	}

	public function getActiveListingsByUsername(callable $callback, string $username, int $offset = 0, int $limit = 45): void {
		$this->connector->executeSelect(Query::FETCH_ACTIVE_USERNAME, ["username" => $username, "id" => $offset, "limit" => $limit], function(array $rows) use ($callback): void {
			/** @var AHListing[] $listings */
			$listings = [];
			foreach ($rows as $listing) {
				$listings[] = AHListing::fromRow($listing);
			}
			$callback($listings);
		});
	}

	public function getActiveListingsByPlayer(callable $callback, UuidInterface $player, int $offset = 0, int $limit = 45): void {
		$this->connector->executeSelect(Query::FETCH_ACTIVE_UUID, ["uuid" => $player->getBytes(), "id" => $offset, "limit" => $limit], function(array $rows) use ($callback): void {
			/** @var AHListing[] $listings */
			$listings = [];
			foreach ($rows as $listing) {
				$listings[] = AHListing::fromRow($listing);
			}
			$callback($listings);
		});
	}

	public function getExpiredListingsByPlayer(callable $callback, UuidInterface $player, int $offset = 0, int $limit = 45): void {
		$this->connector->executeSelect(Query::FETCH_EXPIRED_UUID, ["uuid" => $player->getBytes(), "id" => $offset, "limit" => $limit], function(array $rows) use ($callback): void {
			/** @var AHListing[] $listings */
			$listings = [];
			foreach ($rows as $listing) {
				$listings[] = AHListing::fromRow($listing);
			}
			$callback($listings);
		}, fn() => $callback([]));
	}

	public function getListingsCount(callable $callback): void {
		$this->connector->executeSelect(Query::COUNT_ALL, [], function(array $rows) use ($callback): void {
			$callback($rows[0]["COUNT(*)"]);
		}, fn() => $callback(false));
	}

	public function getActiveListingsCount(callable $callback) {
		$this->connector->executeSelect(Query::COUNT_ACTIVE, [], function(array $rows) use ($callback): void {
			$callback($rows[0]["COUNT(*)"]);
		}, fn() => $callback(false));
	}

	public function getActiveCountByPlayer(UuidInterface $player, callable $callback) {
		$this->connector->executeSelect(Query::COUNT_ACTIVE_UUID, ["uuid" => $player->getBytes()], function(array $rows) use ($callback): void {
			$callback($rows[0]["COUNT(*)"]);
		}, fn() => $callback(false));
	}

	public function getActiveCountByUsername(string $username, callable $callback) {
		$this->connector->executeSelect(Query::COUNT_ACTIVE_USERNAME, ["username" => $username], function(array $rows) use ($callback): void {
			$callback($rows[0]["COUNT(*)"]);
		}, fn() => $callback(false));
	}

	public function getExpiredCountByUsername(string $username, callable $callback) {
		$this->connector->executeSelect(Query::COUNT_EXPIRED_USERNAME, ["username" => $username], function(array $rows) use ($callback): void {
			$callback($rows[0]["COUNT(*)"]);
		}, fn() => $callback(false));
	}

	public function getListingsCountByUsername(string $username, callable $callback) {
		$this->connector->executeSelect(Query::COUNT_USERNAME, ["username" => $username], function(array $rows) use ($callback): void {
			$callback($rows[0]["COUNT(*)"]);
		}, fn() => $callback(false));
	}

	public function getExpiredCount(callable $callback) {
		$this->connector->executeSelect(Query::COUNT_EXPIRED, [], function(array $rows) use ($callback): void {
			$callback($rows[0]["COUNT(*)"]);
		}, fn() => $callback(false));
	}

	public function getExpiredCountByPlayer(UuidInterface $player, callable $callback) {
		$this->connector->executeSelect(Query::COUNT_EXPIRED_UUID, ["uuid" => $player->getBytes()], function(array $rows) use ($callback): void {
			$callback($rows[0]["COUNT(*)"]);
		}, fn() => $callback(false));
	}

	public function setExpired(int $id, callable $callback) {
		$this->connector->executeChange(Query::EXPIRE_ID, ["id" => $id], $callback, fn() => $callback(false));
	}

	public function removeListing(int $id, callable $callback): void {
		$this->connector->executeChange(Query::DELETE_ID, ["id" => $id], $callback, fn() => $callback(false));
	}

	public function createListing(Player $player, Item $item, int $price, callable $callback): void {
		$username = $player->getName();
		$uuid = $player->getUniqueId();
		$createdTime = time();
		$expireTime = Utils::getExpireTime($createdTime);
		$this->connector->executeMulti(Query::INSERT, [
			"player_uuid" => $uuid->getBytes(),
			"username" => $username,
			"price" => $price,
			"item" => Utils::serializeItem($item),
			"created_at" => $createdTime,
			"expires_at" => $expireTime],
			SqlThread::MODE_INSERT,
			function($results) use ($expireTime, $item, $createdTime, $username, $price, $uuid, $callback) {
				/** @var SqlInsertResult[] $results */
				$id = $results[1]->getInsertId();
				$listing = new AHListing($id, $uuid, $price, $username, $createdTime, $expireTime, false, $item);
				(new AuctionStartEvent($listing))->call();
				$callback($listing);
			}, fn() => $callback(null)
		);
	}

	public function setExpiredAsync(int $id): Generator {
		return yield from Await::promise(fn($res) => $this->setExpired($id, $res));
	}

	public function removeListingAsync(int $id): Generator {
		return yield from Await::promise(fn($res) => $this->removeListing($id, $res));
	}

	public function createListingAsync(Player $player, Item $item, int $price): Generator {
		return yield from Await::promise(fn($cb) => $this->createListing($player, $item, $price, $cb));
	}

	public function asyncGenericRaw(string $query, array $args = []): Generator {
		return yield from Await::promise(fn ($resolve, $reject) => $this->connector->executeImplRaw([$query], [$args], [SqlThread::MODE_GENERIC], $resolve, $reject));
	}

	public function asyncChangeRaw(string $query, array $args = []): Generator {
		return yield from Await::promise(fn ($resolve, $reject) => $this->connector->executeImplRaw([$query], [$args], [SqlThread::MODE_CHANGE], $resolve, $reject));
	}

	public function asyncInsertRaw(string $query, array $args = []): Generator {
		return yield from Await::promise(fn ($resolve, $reject) => $this->connector->executeImplRaw([$query], [$args], [SqlThread::MODE_INSERT], $resolve, $reject));
	}

	public function asyncSelectRaw(string $query, array $args = []): Generator {
		return yield from Await::promise(fn ($resolve, $reject) => $this->connector->executeImplRaw([$query], [$args], [SqlThread::MODE_SELECT], $resolve, $reject));
	}

	public function executeMultiAsync(string $queryName, array $args = [], int $mode = SqlThread::MODE_GENERIC): Generator {
		return yield from Await::promise(fn ($resolve, $reject) => $this->connector->executeMulti($queryName, [$args], $mode , $resolve, $reject));
	}
}