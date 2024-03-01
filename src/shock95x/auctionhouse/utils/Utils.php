<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\utils;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\TreeRoot;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use RuntimeException;
use function explode;
use function hex2bin;
use function is_array;
use function json_encode;
use function min;
use function pathinfo;
use function preg_filter;
use function rename;
use function str_ireplace;
use function str_replace;
use function strpos;
use function time;
use function zlib_decode;
use function zlib_encode;
use const JSON_THROW_ON_ERROR;
use const ZLIB_ENCODING_GZIP;

class Utils{

	public static function getEndTime() : int{
		return time() + (Settings::getExpireInterval() * 3600);
	}

	public static function prefixMessage($string) : string{
		return str_replace("&", "\xc2\xa7", Settings::getPrefix() . " " . $string);
	}

	public static function isBlacklisted(Item $item) : bool{
		foreach(Settings::getBlacklist() as $blacklistedItem){
			return $item->equals($blacklistedItem, true, false);
		}
		return false;
	}

	public static function getEmptySlotCount(Inventory $inventory) : int{
		$count = 0;
		for($contents = $inventory->getContents(), $i = 0; $i < $inventory->getSize(); ++$i){
			if(!isset($contents[$i])) $count++;
		}
		return $count;
	}

	public static function getMaxListings(Player $player) : int{
		foreach($player->getEffectivePermissions() as $permission){
			if(strpos($permission->getPermission(), "auctionhouse.limit") === 0){
				return (int) explode(".", $permission->getPermission())[2];
			}
		}
		return Settings::getMaxListings();
	}

	public static function getButtonItem(Player $player, string $itemKey, string $messageKey, array $searchArgs = [], array $replaceArgs = []) : Item{
		$item = LegacyStringToItemParser::getInstance()->parse(Settings::getButtons()[$itemKey]);
		$message = Locale::get($player, $messageKey);

		$item->setCustomName(TextFormat::RESET . str_ireplace($searchArgs, $replaceArgs, $message["name"]));
		if(isset($message["lore"])){
			if(is_array($message["lore"])) $item->setLore(preg_filter('/^/', TextFormat::RESET, str_replace($searchArgs, $replaceArgs, $message["lore"])));
		}
		return $item;
	}

	public static function itemSerialize(Item $item) : string{
		$data = zlib_encode((new BigEndianNbtSerializer())->write(new TreeRoot($item->nbtSerialize())), ZLIB_ENCODING_GZIP);
		if($data === false){
			/** @noinspection PhpUnhandledExceptionInspection */
			throw new RuntimeException("Failed to serialize item " . json_encode($item, JSON_THROW_ON_ERROR));
		}
		return $data;
	}

	public static function itemDeserialize(string $str) : Item{
		return Item::nbtDeserialize((new BigEndianNbtSerializer())->read(zlib_decode(hex2bin($str)))->mustGetCompoundTag());
	}

	public static function removeItem(Player $player, Item $slot) : bool{
		$inventory = $player->getInventory();
		for($i = 0, $size = $inventory->getSize(); $i < $size; ++$i){
			$item = $inventory->getItem($i);
			if($item->isNull()) continue;

			if($slot->equals($item)){
				$amount = min($item->getCount(), $slot->getCount());
				$slot->setCount($slot->getCount() - $amount);
				$item->setCount($item->getCount() - $amount);
				$inventory->setItem($i, $item);
				if($slot->getCount() <= 0){
					return true;
				}
			}
		}
		return false;
	}

	public static function checkConfig(Plugin $plugin, Config $config, string $key, int $version) : void{
		if($config->get($key) != $version){
			$path = $config->getPath();
			$info = pathinfo($path);

			$oldFile = $info["filename"] . "_old." . $info["extension"];
			rename($path, $info["dirname"] . "/" . $oldFile);

			$configDir = str_replace($plugin->getDataFolder(), "", $path);

			$plugin->saveResource($configDir);
			$message = "Your {$info["basename"]} file is outdated. Your old {$info["basename"]} has been saved as $oldFile and a new {$info["basename"]} file has been created. Please update accordingly.";

			$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($plugin, $message) : void{
				$plugin->getLogger()->critical($message);
			}), 1); // should display once the server is done loading
		}
	}
}
