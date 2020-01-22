<?php
namespace shock95x\auctionhouse\utils;

use pocketmine\Player;

class Pagination {

	const BACK = 0;
	const NEXT = 1;
	const REFRESH = 2;

	private static $page;

	public static function getPage(Player $player) : int {
		$uuid = $player->getRawUniqueId();
		if(self::$page[$uuid] == null) {
			return 1;
		}
		return self::$page[$uuid];
	}

	public static function setPage(Player $player, int $page) {
		self::$page[$player->getRawUniqueId()] = $page;
	}

}