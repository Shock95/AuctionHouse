<?php
namespace shock95x\auctionhouse\utils;

use pocketmine\item\Item;
use pocketmine\utils\Config;

class Settings {

	private static $prefix = "[&l&6Auction House&r]";
	private static $defaultLang = "en_US";
	private static $expireInterval = 48;
	private static $listingPrice = 0;
	private static $creativeSale = false;
	private static $maxItems = 45;
	private static $minPrice = 0;
	private static $maxPrice = -1;
	private static $blacklist = [];

	public static function init(Config $config) {
		self::$prefix = $config->get("prefix");
		self::$defaultLang = $config->get("default-language");
		self::$expireInterval = $config->get("expire-interval");
		self::$listingPrice = $config->get("listing-price");
		self::$creativeSale = $config->get("creative-sale");
		self::$maxItems = $config->get("max-items");
		self::$minPrice = $config->get("min-price");
		self::$maxPrice = $config->get("max-price");
		self::$blacklist = $config->getNested("blacklist");
	}

	/**
	 * @return string
	 */
	public static function getPrefix(): string {
		return self::$prefix;
	}

	/**
	 * @return string
	 */
	public static function getDefaultLang(): string {
		return self::$defaultLang;
	}

	/**
	 * @return int
	 */
	public static function getExpireInterval(): int {
		return self::$expireInterval;
	}

	/**
	 * @return int
	 */
	public static function getListingPrice(): int {
		return self::$listingPrice;
	}

	/**
	 * @return bool
	 */
	public static function getCreativeSale() : bool {
		return self::$creativeSale;
	}

	/**
	 * @return int
	 */
	public static function getMaxItems(): int {
		return self::$maxItems;
	}

	public static function getMinPrice() : int {
		return self::$minPrice;
	}

	public static function getMaxPrice() : int {
		return self::$maxPrice;
	}
	/**
	 * @return Item[]
	 */
	public static function getBlacklist(): array {
		$array = [];
		foreach (self::$blacklist as $item) {
			$array[] = Item::fromString($item);
		}
		return $array;
	}
}