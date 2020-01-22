<?php
namespace shock95x\auctionhouse\utils;

use pocketmine\item\Item;

class Utils {

	public static function getEndTime() : int {
		return time() + (Settings::getExpireInterval() * 3600);
}

	public static function colorMessage(string $string) {
		return str_replace("&", "\xc2\xa7", $string);
	}

	public static function prefixMessage($string) {
		return str_replace("&", "\xc2\xa7", Settings::getPrefix() . " " . $string);
	}

	public static function isBlacklisted(Item $item) {
		//todo
	}
}