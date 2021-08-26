<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\database;

use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\event\AuctionStartEvent;
use shock95x\auctionhouse\task\ListingExpireTask;
use shock95x\auctionhouse\utils\Utils;
use SOFe\AwaitGenerator\Await;

class DataHolder {

	/** @var AHListing[] */
	private static array $listings;
	private static Database $database;
	public static BigEndianNBTStream $endianStream;

	public function __construct(Database $database) {
		self::$database = $database;
		self::$endianStream = new BigEndianNBTStream();
	}

	public function loadListings(): void {
		self::$listings = array();
		Await::f2c(function () {
			$rows = (array) yield self::$database->fetchAll();
			foreach($rows as $listing) {
                $nbt = self::$endianStream->readCompressed($listing["nbt"]);
                if($nbt instanceof CompoundTag) {
                	array_unshift(self::$listings, new AHListing($listing["id"], $listing["uuid"], $listing["price"], $listing["username"], $listing["end_time"], boolval($listing["expired"]), Item::nbtDeserialize($nbt)));
                }
			}
		});
		self::$database->getConnector()->waitAll();
        AuctionHouse::getInstance()->getScheduler()->scheduleRepeatingTask(new ListingExpireTask(), 1200);
	}

	/**
	 * @param Player $player
	 * @param bool $expired
	 *
	 * @return AHListing[]
	 */
	public static function getListingsByPlayer(Player $player, bool $expired = false): array {
		$array = [];
		foreach(self::$listings as $listing) {
			if($listing->getSellerUUID() == $player->getRawUniqueId()) {
				if($expired && $listing->isExpired()) {
					$array[] = $listing;
				} else if(!$expired && !$listing->isExpired()) {
					$array[] = $listing;
				}
			}
		}
		return $array;
	}

	/**
	 * @param string $player
	 * @param bool $expired
	 *
	 * @return AHListing[]
	 */
	public static function getListingsByUsername(string $player, bool $expired = false): array {
		$array = [];
		foreach(self::$listings as $listing) {
			if(strtolower($listing->getSeller()) == strtolower($player)) {
				if($expired && $listing->isExpired()) {
					$array[] = $listing;
				} else if(!$expired && !$listing->isExpired()) {
					$array[] = $listing;
				}
			}
		}
		return $array;
	}

	public static function getListingById(int $id): ?AHListing {
		foreach(self::$listings as $listing) {
			if($listing->getId() == $id) {
				return $listing;
			}
		}
		return null;
	}

    public static function addListing(Player $player, Item $item, int $price): AHListing {
	    $listing = new AHListing(time(), $player->getRawUniqueId(), $price, $player->getName(), Utils::getEndTime(), false, $item);
	    array_unshift(self::$listings, $listing);

        $nbt = self::$endianStream->writeCompressed($item->nbtSerialize());
        self::$database->insert($listing->getSellerUUID(), $listing->getSeller(), (int) $listing->getPrice(), $nbt, $listing->getEndTime(), $listing->isExpired(), $listing->getId());
        (new AuctionStartEvent($listing))->call();
        return $listing;
    }

	/**
	 * @param bool $includeExpired
	 * @return AHListing[]
	 */
	public static function getListings(bool $includeExpired = false): array {
		if(!$includeExpired) {
			$array = [];
			foreach (self::$listings as $listing) {
				if(!$listing->isExpired()) {
					$array[] = $listing;
				}
			}
			return $array;
		}
		return self::$listings;
	}

	/**
	 * @return AHListing[]
	 */
	public static function getExpiredListings(): array {
		$array = [];
		foreach(self::$listings as $listing) {
			if($listing->isExpired()) {
				$array[] = $listing;
			}
		}
		return $array;
	}

    public static function setExpired(AHListing $auction, bool $expired = true): void {
        $auction->setExpired($expired);
        self::$database->setExpired($auction->getId(), $expired);
    }

	public static function removeListing(AHListing $auction): void {
		$index = array_search($auction, self::$listings);
		if($index !== false){
			unset(self::$listings[$index]);
		}
		self::$database->delete($auction->getId());
	}
}