<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\manager;

use pocketmine\player\Player;
use shock95x\auctionhouse\utils\Settings;
use function time;

class CooldownManager{

	private static array $cooldown;

	public static function inCooldown(Player $player) : bool{
		return isset(self::$cooldown[$player->getUniqueId()->toString()]);
	}

	public static function setCooldown(Player $player) : bool{
		if(!isset(self::$cooldown[$player->getUniqueId()->toString()])){
			self::$cooldown[$player->getUniqueId()->toString()] = time() + Settings::getListingCooldown();
			return true;
		}
		return false;
	}

	public static function getCooldown(Player $player) : int{
		if(isset(self::$cooldown[$player->getUniqueId()->toString()])){
			return self::$cooldown[$player->getUniqueId()->toString()];
		}
		return 0;
	}

	public static function removeCooldown(string $uniqueId) : void{
		if(isset(self::$cooldown[$uniqueId])){
			unset(self::$cooldown[$uniqueId]);
		}
	}
}
