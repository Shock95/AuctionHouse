<?php
namespace shock95x\auctionhouse\utils;

use pocketmine\item\Item;
use pocketmine\Player;

class Utils {

	public const AUCTION_MENU = 0;
	public const LISTINGS_MENU = 1;
	public const EXPIRED_MENU = 2;
	public const ADMIN_MENU = 3;
	public const CONFIRM_PURCHASE_MENU = 4;
	public const MANAGE_LISTING_MENU = 5;
	public const PLAYER_LISTINGS_MENU = 6;

	/** @var array */
	private static $menuOpen;

	public static function getEndTime() : int {
		return time() + (Settings::getExpireInterval() * 3600);
	}

	public static function prefixMessage($string) {
		return str_replace("&", "\xc2\xa7", Settings::getPrefix() . " " . $string);
	}

	public static function isBlacklisted(Item $item) : bool {
		foreach(Settings::getBlacklist() as $blacklistedItem) {
			if($item->getId() == $blacklistedItem->getId() && $item->getDamage() == $blacklistedItem->getDamage()) {
				return true;
			}
		}
		return false;
	}

	public static function setViewingMenu(Player $player, int $menu) {
		self::$menuOpen[$player->getRawUniqueId()] = $menu;
	}

	public static function getViewingMenu(Player $player) {
		if (isset(self::$menuOpen[$player->getRawUniqueId()])) {
			return self::$menuOpen[$player->getRawUniqueId()];
		}
		return -1;
	}
}