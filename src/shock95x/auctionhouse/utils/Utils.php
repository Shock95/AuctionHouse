<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\utils;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\AuctionHouse;

class Utils {

	public static function getEndTime(): int {
		return time() + (Settings::getExpireInterval() * 3600);
	}

	public static function prefixMessage($string): string {
		return str_replace("&", "\xc2\xa7", Settings::getPrefix() . " " . $string);
	}

	public static function isBlacklisted(Item $item): bool {
		foreach(Settings::getBlacklist() as $blacklistedItem) {
			return $item->equals($blacklistedItem, true, false);
		}
		return false;
	}

	public static function canAfford(Player $player, AHListing $listing): bool {
		return (AuctionHouse::getInstance()->getEconomyProvider()->getMoney($player) >= $listing->getPrice());
	}

	public static function getEmptySlotCount(Inventory $inventory): int {
		$count = 0;
		for($contents = $inventory->getContents(), $i = 0; $i < $inventory->getSize(); $i++) {
			if(!isset($contents[$i])) $count++;
		}
		return $count;
	}

	public static function getMaxListings(Player $player): int {
		foreach ($player->getEffectivePermissions() as $permission) {
			if(strpos($permission->getPermission(), "auctionhouse.limit") === 0) {
				return (int) explode(".", $permission->getPermission())[2];
			}
		}
		return Settings::getMaxListings();
	}

	public static function getButtonItem(Player $player, string $itemKey, string $messageKey, array $searchArgs = [], array $replaceArgs = []): Item {
		$item = Item::fromString(Settings::getButtons()[$itemKey]);
		$message = Locale::getMessage($player, $messageKey);

		$item->setCustomName(TextFormat::RESET . str_replace($searchArgs, $replaceArgs, $message["name"]));
		if(isset($message["lore"])) {
			if(is_array($message["lore"])) $item->setLore(preg_filter('/^/', TextFormat::RESET, str_replace($searchArgs, $replaceArgs, $message["lore"])));
		}
		return $item;
	}

	public static function checkConfig(Plugin $plugin, Config $config, string $key, int $version): void {
		if($config->get($key) != $version) {
			$path = $config->getPath();
			$info = pathinfo($path);

			$oldFile = $info["filename"] . "_old." . $info["extension"];
			rename($path, $info["dirname"] . "/" . $oldFile);

			$configDir = str_replace($plugin->getDataFolder(), "", $path);

			$plugin->saveResource($configDir);
			$message = "Your {$info["basename"]} file is outdated. Your old {$info["basename"]} has been saved as $oldFile and a new {$info["basename"]} file has been created. Please update accordingly.";

			$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use ($plugin, $message): void{
				$plugin->getLogger()->critical($message);
			}), 1); // should display once the server is done loading
		}
	}
}