<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\manager;

use pocketmine\Player;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\task\CooldownTask;
use shock95x\auctionhouse\utils\Settings;

class CooldownManager {

	private static array $cooldown;

	public static function inCooldown(Player $player): bool {
		return isset(self::$cooldown[$player->getRawUniqueId()]);
	}

	public static function setCooldown(Player $player): void {
		if (!isset(self::$cooldown[$player->getRawUniqueId()])) {
			self::$cooldown[$player->getRawUniqueId()] = time() + Settings::getListingCooldown();
			AuctionHouse::getInstance()->getScheduler()->scheduleDelayedTask(new CooldownTask($player->getRawUniqueId()), Settings::getListingCooldown() * 20);
		}
	}

	public static function getCooldown(Player $player): int {
		if (isset(self::$cooldown[$player->getRawUniqueId()])) {
			return self::$cooldown[$player->getRawUniqueId()];
		}
		return 0;
	}

	public static function removeCooldown(string $uniqueId): void {
		if (isset(self::$cooldown[$uniqueId])) {
			unset(self::$cooldown[$uniqueId]);
		}
	}
}