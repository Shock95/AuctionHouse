<?php
namespace AuctionHouse\database;


use AuctionHouse\auction\Listing;
use AuctionHouse\AuctionHouse;
use AuctionHouse\event\AuctionStartEvent;
use AuctionHouse\task\ListingExpireTask;
use AuctionHouse\utils\Utils;
use pocketmine\Player;
use SOFe\AwaitGenerator\Await;

class DataHolder {

	/** @var Listing[] */
	private static $listings = array();
	private static $database;

	public function __construct(Database $database) {
		self::$database = $database;
	}

	public function loadListings() {
		Await::f2c(function () {
			$rows = (array) yield self::$database->fetchAll();
			foreach($rows as $listing) {
				self::$listings[] = new Listing($listing, self::$database->getParser());
			}
		});
		AuctionHouse::getInstance()->getScheduler()->scheduleRepeatingTask(new ListingExpireTask(DataHolder::$database), 6000);
	}

	/**
	 * @param Player $player
	 * @param bool $expired
	 * @return Listing[]
	 */
	public static function getListingsByPlayer(Player $player, bool $expired = false) {
		$array = [];
		foreach((array) self::$listings as $listing) {
			if($listing->getSeller(true) == $player->getRawUniqueId()) {
				if($expired) {
					if($listing->isExpired()) {
						$array[] = $listing;
					}
				} else {
					if(!$listing->isExpired()) {
						$array[] = $listing;
					}
				}
			}
		}
		return $array;
	}

	public static function getListingById(int $id) : Listing {
		foreach((array) self::$listings as $listing) {
			if($listing->getMarketId() == $id) {
				return $listing;
			}
		}
		return null;
	}

	public static function addListing(Player $player, int $price, string $nbt) {
		$listing = new Listing(["uuid" => $player->getRawUniqueId(), "username" => $player->getName(), "price" => $price, "nbt" => self::$database->getParser()->encode($nbt), "id" => time(), "end_time" => Utils::getEndTime(), "expired" => false],  self::$database->getParser());
		self::$listings[] = $listing;
		(new AuctionStartEvent($listing))->call();
	}

	/**
	 * @param bool $expired
	 * @return Listing[]
	 */
	public static function getListings(bool $expired = false) {
		if(!$expired) {
			$array = [];
			foreach ((array)self::$listings as $listing) {
				if(!$listing->isExpired()) {
					$array[] = $listing;
				}
			}
			return $array;
		}
		return self::$listings;
	}

	public static function removeAuction(Listing $auction) {
		$index = array_search($auction, (array) self::$listings);
		if($index !== false){
			unset(self::$listings[$index]);
		}
		self::$database->deleteFromId($auction->getMarketId());
	}
}