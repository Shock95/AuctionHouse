<?php

namespace shock95x\auctionhouse\database\storage;

use Generator;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\database\Database;
use shock95x\auctionhouse\database\Query;
use shock95x\auctionhouse\event\AuctionStartEvent;
use shock95x\auctionhouse\utils\Utils;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\SOFe\AwaitGenerator\Await;

/**
 * Use AuctionHouse->getDatabase()
 * @deprecated
 */
class DataStorage {

	use SingletonTrait;

	protected Database $database;

	public function init(Database $database): void {
		$this->database = $database;
	}

	public function getListingById(int $id, callable $callback): void {
		$this->database->getConnector()->executeSelect(Query::FETCH_ID, ["id" => $id], function(array $rows) use ($callback): void {
			foreach ($rows as $listing) {
				$callback(AHListing::fromRow($listing));
				return;
			}
			$callback(null);
		});
	}

	public function getListings(callable $callback, int $offset = 0, int $limit = 45) {
		$this->database->getConnector()->executeSelect(Query::FETCH_ACTIVE_NEXT, ["id" => $offset, "limit" => $limit], function(array $rows) use ($callback): void {
			/** @var AHListing[] $listings */
			$listings = [];
			foreach ($rows as $listing) {
				$listings[] = AHListing::fromRow($listing);
			}
			$callback($listings);
		});
	}

	public function getActiveListings(callable $callback, int $offset = 0, int $limit = 45) {
		$this->database->getConnector()->executeSelect(Query::FETCH_ACTIVE_NEXT, ["id" => $offset, "limit" => $limit], function(array $rows) use ($callback): void {
			/** @var AHListing[] $listings */
			$listings = [];
			foreach ($rows as $listing) {
				$listings[] = AHListing::fromRow($listing);
			}
			$callback($listings);
		});
	}

	public function getActiveListingsByUsername(callable $callback, string $username, int $offset = 0, int $limit = 45): void {
		$this->database->getConnector()->executeSelect(Query::FETCH_ACTIVE_USERNAME, ["username" => $username, "id" => $offset, "limit" => $limit], function(array $rows) use ($callback): void {
			/** @var AHListing[] $listings */
			$listings = [];
			foreach ($rows as $listing) {
				$listings[] = AHListing::fromRow($listing);
			}
			$callback($listings);
		});
	}

	public function getActiveListingsByPlayer(callable $callback, Player $player, int $offset = 0, int $limit = 45): void {
		$this->database->getConnector()->executeSelect(Query::FETCH_ACTIVE_UUID, ["uuid" => $player->getUniqueId()->getBytes(), "id" => $offset, "limit" => $limit], function(array $rows) use ($callback): void {
			/** @var AHListing[] $listings */
			$listings = [];
			foreach ($rows as $listing) {
				$listings[] = AHListing::fromRow($listing);
			}
			$callback($listings);
		});
	}

	public function getExpiredListingsByPlayer(callable $callback, Player $player, int $offset = 0, int $limit = 45): void {
		$this->database->getConnector()->executeSelect(Query::FETCH_EXPIRED_UUID, ["uuid" => $player->getUniqueId()->getBytes(), "id" => $offset, "limit" => $limit], function(array $rows) use ($callback): void {
			/** @var AHListing[] $listings */
			$listings = [];
			foreach ($rows as $listing) {
				$listings[] = AHListing::fromRow($listing);
			}
			$callback($listings);
		}, fn() => $callback([]));
	}

	public function getTotalListingCount(callable $callback): void {
		$this->database->getConnector()->executeSelect(Query::COUNT_ALL, [], function(array $rows) use ($callback): void {
			$callback($rows[0]["COUNT(*)"]);
		}, fn() => $callback(false));
	}

	public function getActiveListingCount(callable $callback) {
		$this->database->getConnector()->executeSelect(Query::COUNT_ACTIVE, [], function(array $rows) use ($callback): void {
			$callback($rows[0]["COUNT(*)"]);
		}, fn() => $callback(false));
	}

	public function getActiveCountByPlayer(Player $player, callable $callback) {
		$this->database->getConnector()->executeSelect(Query::COUNT_ACTIVE_UUID, ["uuid" => $player->getUniqueId()->getBytes()], function(array $rows) use ($callback): void {
			$callback($rows[0]["COUNT(*)"]);
		}, fn() => $callback(false));
	}

	public function getActiveCountByUsername(string $username, callable $callback) {
		$this->database->getConnector()->executeSelect(Query::COUNT_ACTIVE_USERNAME, ["username" => $username], function(array $rows) use ($callback): void {
			$callback($rows[0]["COUNT(*)"]);
		}, fn() => $callback(false));
	}

	public function getExpiredCount(callable $callback) {
		$this->database->getConnector()->executeSelect(Query::COUNT_EXPIRED, [], function(array $rows) use ($callback): void {
			$callback($rows[0]["COUNT(*)"]);
		}, fn() => $callback(false));
	}

	public function getExpiredCountByPlayer(Player $player, callable $callback) {
		$this->database->getConnector()->executeSelect(Query::COUNT_EXPIRED_UUID, ["uuid" => $player->getUniqueId()->getBytes()], function(array $rows) use ($callback): void {
			$callback($rows[0]["COUNT(*)"]);
		}, fn() => $callback(false));
	}

	public function setExpired(AHListing $listing,  ?callable $onSuccess = null, ?callable $onError = null) {
		$this->database->getConnector()->executeGeneric(Query::EXPIRE_ID, ["id" => $listing->getId()], $onSuccess, $onError);
	}

	public function removeListing(AHListing $listing, ?callable $onSuccess = null, ?callable $onError = null): void {
		$this->removeListingById($listing->getId(), $onSuccess, $onError);
	}

	public function removeListingById(int $id, ?callable $onSuccess = null, ?callable $onError = null): void {
		$this->database->getConnector()->executeGeneric(Query::DELETE_ID, ["id" => $id], $onSuccess, $onError);
	}

	public function createListing(Player $player, Item $item, int $price, ?callable $callback = null): void {
		$uuid = $player->getUniqueId()->getBytes();
		$name = $player->getName();
		$created = time();
		$endTime = Utils::getExpireTime($created);
		$this->database->getConnector()->executeInsert(Query::INSERT, [
			"uuid" => $uuid,
			"username" => $name,
			"price" => $price,
			"item" => Utils::serializeItem($item),
			"created" => $created,
			"end_time" => $endTime,
			"expired" => false],
			function($id) use ($endTime, $item, $created, $name, $price, $uuid, $callback) {
				$listing = new AHListing($id, $uuid, $price, $name, $created, $endTime, false, $item);
				(new AuctionStartEvent($listing))->call();
				$callback($listing);

			}, fn() => $callback(null));
	}

	public function createListingAsync(Player $player, Item $item, int $price): Generator {
		return yield from Await::promise(fn($cb) => $this->createListing($player, $item, $price));
	}
}