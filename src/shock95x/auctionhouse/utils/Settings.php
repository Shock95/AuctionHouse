<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\utils;

use pocketmine\item\Item;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Settings{

	private static string $prefix = "[&l&6Auction House&r]";
	private static string $defaultLang = "en_US";
	private static int $expireInterval = 48;
	private static int $listingPrice = 0;
	private static int $listingCooldown = 0;
	private static bool $creativeSale = false;
	private static bool $allowLore = true;
	private static int $expiredDuration = 15;
	private static bool $formatPrice = true;
	private static int $maxListings = 45;
	private static int $minPrice = 0;
	private static int $maxPrice = -1;
	private static array $blacklist = [];
	private static array $signTriggers = ["[AuctionHouse]", "[AH]"];
	private static array $buttons = [];
	private static string $currencySymbol = "";

	public static function init(Config $config, bool $reload = false){
		if($reload) $config->reload();
		self::$prefix = TextFormat::colorize($config->get("prefix"));
		self::$defaultLang = $config->get("default-language");
		self::$expireInterval = $config->get("expire-interval");
		self::$listingPrice = $config->get("listing-price");
		self::$listingCooldown = $config->get("listing-cooldown");
		self::$creativeSale = $config->get("creative-sale");
		self::$allowLore = $config->get("show-lore");
		self::$expiredDuration = (int) $config->get("expired-duration");
		self::$formatPrice = $config->get("price-formatted");
		self::$maxListings = $config->get("max-listings");
		self::$minPrice = $config->get("min-price");
		self::$maxPrice = $config->get("max-price");
		self::$blacklist = $config->getNested("blacklist");
		self::$signTriggers = $config->getNested("sign-triggers");
		self::$buttons = $config->getNested("buttons");
	}

	public static function getPrefix() : string{
		return self::$prefix;
	}

	public static function getDefaultLang() : string{
		return self::$defaultLang;
	}

	public static function getExpireInterval() : int{
		return self::$expireInterval;
	}

	public static function getListingPrice() : int{
		return self::$listingPrice;
	}

	public static function getListingCooldown() : int{
		return self::$listingCooldown;
	}

	public static function allowCreativeSale() : bool{
		return self::$creativeSale;
	}

	public static function allowLore() : bool{
		return self::$allowLore;
	}

	public static function getExpiredDuration() : int{
		return self::$expiredDuration;
	}

	public static function formatPrice() : bool{
		return self::$formatPrice;
	}

	public static function getMaxListings() : int{
		return self::$maxListings;
	}

	public static function getMinPrice() : int{
		return self::$minPrice;
	}

	public static function getMaxPrice() : int{
		return self::$maxPrice;
	}

	/**
	 * @return Item[]
	 */
	public static function getBlacklist() : array{
		$array = [];
		foreach(self::$blacklist as $item){
			try{
				$array[] = LegacyStringToItemParser::getInstance()->parse($item);
			}catch(LegacyStringToItemParserException $exception){
			}
		}
		return $array;
	}

	/**
	 * @return string[]
	 */
	public static function getSignTriggers() : array{
		return self::$signTriggers;
	}

	public static function getButtons() : array{
		return self::$buttons;
	}

	public static function setCurrencySymbol(string $currencySymbol) : void{
		self::$currencySymbol = $currencySymbol;
	}

	public static function getCurrencySymbol() : string{
		return self::$currencySymbol;
	}
}
