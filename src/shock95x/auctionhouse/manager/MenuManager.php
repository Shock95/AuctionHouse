<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\manager;

use pocketmine\Player;

class MenuManager {

	public const AUCTION_MENU = 0;
	public const LISTINGS_MENU = 1;
	public const EXPIRED_MENU = 2;
	public const ADMIN_MENU = 3;
	public const CONFIRM_PURCHASE_MENU = 4;
	public const MANAGE_LISTING_MENU = 5;
	public const PLAYER_LISTINGS_MENU = 6;
	public const CATEGORY_MENU = 7;
	public const CATEGORY_LIST_MENU = 8;

	private static array $menuOpen;

	public static function setViewingMenu(Player $player, int $menu): void {
		self::$menuOpen[$player->getRawUniqueId()] = $menu;
	}

	public static function getViewingMenu(Player $player): int {
		if (isset(self::$menuOpen[$player->getRawUniqueId()])) {
			return self::$menuOpen[$player->getRawUniqueId()];
		}
		return -1;
	}

	public static function remove(Player $player) {
		if(isset(self::$menuOpen[$player->getRawUniqueId()])) {
			unset(self::$menuOpen[$player->getRawUniqueId()]);
		}
	}
}