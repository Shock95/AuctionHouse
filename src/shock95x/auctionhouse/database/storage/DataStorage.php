<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\database\storage;

use Generator;
use JsonException;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\database\Database;
use shock95x\auctionhouse\database\Query;
use shock95x\auctionhouse\event\AuctionStartEvent;
use shock95x\auctionhouse\utils\Utils;
use SOFe\AwaitGenerator\Await;
use function bin2hex;
use function is_numeric;
use function json_encode;
use function time;

class DataStorage{

	use SingletonTrait;

	protected Database $database;

	public function init(Database $database) : void{
		$this->database = $database;
	}

	public function getListingById(int $id, callable $callback) : void{
		$this->database->getConnector()->executeSelect(Query::FETCH_ID, ["id" => $id], function(array $rows) use ($callback) : void{
			foreach($rows as $listing){
				$callback($this->database->createListingFromRows($listing));
				return;
			}
			$callback(null);
		});
	}

	public function getListings(callable $callback, int $offset = 0, int $limit = 45){
		$this->database->getConnector()->executeSelect(Query::FETCH_ACTIVE_NEXT, ["id" => $offset, "limit" => $limit], function(array $rows) use ($callback) : void{
			/** @var AHListing[] $listings */
			$listings = [];
			foreach($rows as $listing){
				$listings[] = $this->database->createListingFromRows($listing);
			}
			$callback($listings);
		});
	}

	public function getActiveListings(callable $callback, int $offset = 0, int $limit = 45){
		$this->database->getConnector()->executeSelect(Query::FETCH_ACTIVE_NEXT, ["id" => $offset, "limit" => $limit], function(array $rows) use ($callback) : void{
			/** @var AHListing[] $listings */
			$listings = [];
			foreach($rows as $listing){
				$listings[] = $this->database->createListingFromRows($listing);
			}
			$callback($listings);
		});
	}

	public function getActiveListingsByUsername(callable $callback, string $username, int $offset = 0, int $limit = 45) : void{
		$this->database->getConnector()->executeSelect(Query::FETCH_ACTIVE_USERNAME, ["username" => $username, "id" => $offset, "limit" => $limit], function(array $rows) use ($callback) : void{
			/** @var AHListing[] $listings */
			$listings = [];
			foreach($rows as $listing){
				$listings[] = $this->database->createListingFromRows($listing);
			}
			$callback($listings);
		});
	}

	public function getActiveListingsByPlayer(callable $callback, Player $player, int $offset = 0, int $limit = 45) : void{
		$this->database->getConnector()->executeSelect(Query::FETCH_ACTIVE_UUID, ["uuid" => $player->getUniqueId()->toString(), "id" => $offset, "limit" => $limit], function(array $rows) use ($callback) : void{
			/** @var AHListing[] $listings */
			$listings = [];
			foreach($rows as $listing){
				$listings[] = $this->database->createListingFromRows($listing);
			}
			$callback($listings);
		});
	}

	public function getExpiredListingsByPlayer(callable $callback, Player $player, int $offset = 0, int $limit = 45) : void{
		$this->database->getConnector()->executeSelect(Query::FETCH_EXPIRED_UUID, ["uuid" => $player->getUniqueId()->toString(), "id" => $offset, "limit" => $limit], function(array $rows) use ($callback) : void{
			/** @var AHListing[] $listings */
			$listings = [];
			foreach($rows as $listing){
				$listings[] = $this->database->createListingFromRows($listing);
			}
			$callback($listings);
		}, fn() => $callback([]));
	}

	public function getTotalListingCount(callable $callback) : void{
		$this->database->getConnector()->executeSelect(Query::COUNT_ALL, [], function(array $rows) use ($callback) : void{
			$callback($rows[0]["COUNT(*)"]);
		}, fn() => $callback(false));
	}

	public function getActiveListingCount(callable $callback){
		$this->database->getConnector()->executeSelect(Query::COUNT_ACTIVE, [], function(array $rows) use ($callback) : void{
			$callback($rows[0]["COUNT(*)"]);
		}, fn() => $callback(false));
	}

	public function getActiveCountByPlayer(Player $player, callable $callback){
		$this->database->getConnector()->executeSelect(Query::COUNT_ACTIVE_UUID, ["uuid" => $player->getUniqueId()->toString()], function(array $rows) use ($callback) : void{
			$callback($rows[0]["COUNT(*)"]);
		}, fn() => $callback(false));
	}

	public function getActiveCountByUsername(string $username, callable $callback){
		$this->database->getConnector()->executeSelect(Query::COUNT_ACTIVE_USERNAME, ["username" => $username], function(array $rows) use ($callback) : void{
			$callback($rows[0]["COUNT(*)"]);
		}, fn() => $callback(false));
	}

	public function getExpiredCount(callable $callback){
		$this->database->getConnector()->executeSelect(Query::COUNT_EXPIRED, [], function(array $rows) use ($callback) : void{
			$callback($rows[0]["COUNT(*)"]);
		}, fn() => $callback(false));
	}

	public function getExpiredCountByPlayer(Player $player, callable $callback){
		$this->database->getConnector()->executeSelect(Query::COUNT_EXPIRED_UUID, ["uuid" => $player->getUniqueId()->toString()], function(array $rows) use ($callback) : void{
			$callback($rows[0]["COUNT(*)"]);
		}, fn() => $callback(false));
	}

	public function setExpired(AHListing $listing, $value = true, ?callable $onSuccess = null, ?callable $onError = null){
		$this->database->getConnector()->executeGeneric(Query::SET_EXPIRED, ["id" => $listing->getId(), "expired" => $value], $onSuccess, $onError);
	}

	public function removeListing(AHListing $listing, ?callable $onSuccess = null, ?callable $onError = null) : void{
		$listing->setExpired();
		$this->removeListingById($listing->getId(), $onSuccess, $onError);
	}

	public function removeListingById(int $id, ?callable $onSuccess = null, ?callable $onError = null) : void{
		$this->database->getConnector()->executeGeneric(Query::DELETE, ["id" => $id], $onSuccess, $onError);
	}

	/**
	 * @throws JsonException
	 */
	public function createListing(Player $player, Item $item, int $price, ?callable $callback = null) : void{
		$uuid = $player->getUniqueId()->toString();
		$name = $player->getName();
		$created = time();
		$endTime = Utils::getEndTime();

		$this->database->getConnector()->executeInsert(Query::INSERT, [
			"uuid" => $uuid,
			"username" => $name,
			"price" => $price,
			"item" => json_encode(bin2hex(Utils::ItemSerialize($item))),
			"created" => $created,
			"end_time" => $endTime,
			"expired" => false],

			function($id) use ($endTime, $item, $created, $name, $price, $uuid, $callback){
				$listing = new AHListing($id, $uuid, $price, $name, $created, $endTime, false, $item);
				(new AuctionStartEvent($listing))->call();
				$callback($listing);

			}, fn() => $callback(null));
	}

	public function createListingAsync(Player $player, Item $item, int $price) : Generator{
		$created = time();
		$uuid = $player->getUniqueId()->toString();
		$name = $player->getName();
		$endTime = Utils::getEndTime();

		$id = yield from Await::promise(
		/**
		 * @throws JsonException
		 */ function($resolve, $reject) use ($endTime, $created, $item, $price, $name, $uuid, $player){
			$this->database->getConnector()->executeInsert(Query::INSERT, [
				"uuid" => $uuid,
				"username" => $name,
				"price" => $price,
				"item" => json_encode(bin2hex(Utils::ItemSerialize($item))),
				"created" => $created,
				"end_time" => $endTime,
				"expired" => false], $resolve, $reject);
		});

		if(!is_numeric($id)) return null;

		$listing = new AHListing($id, $uuid, $price, $name, $created, $endTime, false, $item);
		(new AuctionStartEvent($listing))->call();
		return $listing;
	}
}
