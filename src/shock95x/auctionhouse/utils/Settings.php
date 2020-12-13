<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\utils;

use pocketmine\item\Item;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Settings {

	private static $prefix = "[&l&6Auction House&r]";
	private static $defaultLang = "en_US";
	private static $expireInterval = 48;
	private static $listingPrice = 0;
	private static $listingCooldown = 0;
	private static $creativeSale = false;
	private static $allowLore = true;
	private static $expiredDuration = 30;
	private static $formatPrice = true;
	private static $maxListings = 45;
	private static $minPrice = 0;
	private static $maxPrice = -1;
	private static $blacklist = [];
	private static $signTriggers = ["[AuctionHouse]", "[AH]"];
	private static $buttons = [];

	private static $monetaryUnit;

	public static function init(Config $config) {
	    $config->reload();
		self::$prefix = TextFormat::colorize($config->get("prefix"));
		self::$defaultLang = $config->get("default-language");
		self::$expireInterval = $config->get("expire-interval");
		self::$listingPrice = $config->get("listing-price");
		self::$listingCooldown = $config->get("listing-cooldown");
		self::$creativeSale = $config->get("creative-sale");
		self::$allowLore = $config->get("show-lore");
		self::$expiredDuration = $config->get("expired-duration");
		self::$formatPrice = $config->get("price-formatted");
		self::$maxListings = $config->get("max-listings");
		self::$minPrice = $config->get("min-price");
		self::$maxPrice = $config->get("max-price");
		self::$blacklist = $config->getNested("blacklist");
		self::$signTriggers = $config->getNested("sign-triggers");
		self::$buttons = $config->getNested("buttons");
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
	 * @return int
	 */
	public static function getListingCooldown(): int {
		return self::$listingCooldown;
	}

	/**
	 * @return bool
	 */
	public static function getCreativeSale() : bool {
		return self::$creativeSale;
	}

	/**
	 * @return bool
	 */
	public static function allowLore(): bool {
		return self::$allowLore;
	}

	/**
	 * @return int
	 */
	public static function getExpiredDuration(): int {
		return self::$expiredDuration;
	}

	/**
	 * @return bool
	 */
	public static function formatPrice(): bool {
		return self::$formatPrice;
	}

	/**
	 * @return int
	 */
	public static function getMaxListings(): int {
		return self::$maxListings;
	}

	/**
	 * @return int
	 */
	public static function getMinPrice(): int {
		return self::$minPrice;
	}

	/**
	 * @return int
	 */
	public static function getMaxPrice(): int {
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

	/**
	 * @return string[]
	 */
	public static function getSignTriggers(): array {
		return self::$signTriggers;
	}

	/**
	 * @return array
	 */
	public static function getButtons(): array {
		return self::$buttons;
	}

    /**
     * @param string $monetaryUnit
     */
    public static function setMonetaryUnit($monetaryUnit): void {
        self::$monetaryUnit = $monetaryUnit;
    }

    /**
     * @return string
     */
    public static function getMonetaryUnit(): string {
        return self::$monetaryUnit;
    }
}