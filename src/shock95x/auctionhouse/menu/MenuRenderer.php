<?php
namespace shock95x\auctionhouse\menu;

use shock95x\auctionhouse\AuctionHouse;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class MenuRenderer {

	public static function setListingItems(Player $player, Inventory $inventory, int $page, int $max, int $total) {
		$description = AuctionHouse::getInstance()->getMessage($player, "listings-description", true, false);
		$stats = AuctionHouse::getInstance()->getMessage($player, "listings-stats", true, false);
		$inventory->setItem(53, Item::get($description["id"])->setCustomName(TextFormat::RESET . $description["name"])->setLore(preg_filter('/^/', TextFormat::RESET, $description["lore"])));
		$inventory->setItem(49, Item::get($stats["id"])->setCustomName(TextFormat::RESET . $stats["name"])->setLore(str_replace(["%page%", "%max%", "%total%"], [$page, $max, $total], preg_filter('/^/', TextFormat::RESET, $stats["lore"]))));
		MenuRenderer::paginationItems($player, $inventory, true);
	}

	public static function setAuctionItems(Player $player, Inventory $inventory, int $page, int $max, int $total, int $selling, int $expiredNum) {
		$stats = AuctionHouse::getInstance()->getMessage($player, "main-stats", true, false);
		$chest = Item::get($stats["id"])->setCompoundTag(new CompoundTag("", [new ByteTag("pagination", 2)]))->setCustomName((TextFormat::RESET . $stats["name"]))->setLore(str_replace(["%page%", "%max%", "%total%"], [$page, $max, $total], preg_filter('/^/', TextFormat::RESET, $stats["lore"])));

		$main = AuctionHouse::getInstance()->getMessage($player, "main-description", true, false);
		$mainItem = Item::get($main["id"])->setCustomName(TextFormat::RESET . $main["name"])->setLore(preg_filter('/^/', TextFormat::RESET, $main["lore"]));

		$how = AuctionHouse::getInstance()->getMessage($player, "sell-description", true, false);
		$howItem = Item::get($how["id"])->setCustomName(TextFormat::RESET . $how["name"])->setLore(preg_filter('/^/', TextFormat::RESET, $how["lore"]));

		$listings = AuctionHouse::getInstance()->getMessage($player, "view-listed-items", true, false);
		$listingsItem = Item::get($listings["id"])->setCompoundTag(new CompoundTag("", [new ByteTag("listings", 1)]))->setCustomName(TextFormat::RESET . $listings["name"])->setLore(str_replace("%selling%", $selling, preg_filter('/^/', TextFormat::RESET, $listings["lore"])));

		$expired = AuctionHouse::getInstance()->getMessage($player, "view-expired-items", true, false);
		$expiredItem = Item::get($expired["id"])->setCompoundTag(new CompoundTag("", [new ByteTag("expired", 1)]))->setCustomName(TextFormat::RESET . $expired["name"])->setLore(str_replace("%expired%", $expiredNum, preg_filter('/^/', TextFormat::RESET, $expired["lore"])));

		MenuRenderer::paginationItems($player, $inventory);
		$array = [49 => $chest, 45 => $listingsItem, 46 => $expiredItem, 52 => $howItem, 53 => $mainItem];
		foreach ($array as $slot => $item) $inventory->setItem($slot, $item);
	}

	public static function paginationItems(Player $player, Inventory $inventory, bool $mainPage = false) {
		$pagination = [Item::get(Item::PAPER), Item::get(Item::PAPER)];
		for ($x = 0; $x <= 1; $x++) {
			$pagination[$x]->setCompoundTag(new CompoundTag("", [new ByteTag("pagination", $x)]));
		}
		$previous = AuctionHouse::getInstance()->getMessage($player, "previous-page", true, false);
		$next = AuctionHouse::getInstance()->getMessage($player, "next-page", true, false);
		$pagination[0]->setCustomName(TextFormat::RESET . $previous["name"])->setLore((preg_filter('/^/', TextFormat::RESET, $previous["lore"])));
		$pagination[1]->setCustomName(TextFormat::RESET . $next["name"])->setLore((preg_filter('/^/', TextFormat::RESET, $next["lore"])));
		$items = [48 => $pagination[0], 50 => $pagination[1]];
		if ($mainPage) {
			$return = Item::get(Item::PAPER)->setCompoundTag(new CompoundTag("", [new ByteTag("return", 1)]))->setCustomName(TextFormat::RESET . TextFormat::GOLD . "Back");
			$items[45] = $return;
		}
		foreach ($items as $slot => $item) $inventory->setItem($slot, $item);
	}

	public static function setExpiredItems(Player $player, Inventory $inventory, int $page, int $max, int $total) {
		$description = AuctionHouse::getInstance()->getMessage($player, "expired-description", true, false);
		$stats = AuctionHouse::getInstance()->getMessage($player, "expired-stats", true, false);
		$inventory->setItem(53, Item::get($description["id"])->setCustomName(TextFormat::RESET . $description["name"])->setLore(preg_filter('/^/', TextFormat::RESET, $description["lore"])));
		$inventory->setItem(49, Item::get($stats["id"])->setCompoundTag(new CompoundTag("", [new ByteTag("getAll", 1)]))->setCustomName(TextFormat::RESET . $stats["name"])->setLore(str_replace(["%page%", "%max%", "%total%"], [$page, $max, $total], preg_filter('/^/', TextFormat::RESET, $stats["lore"]))));
		MenuRenderer::paginationItems($player, $inventory, true);
	}
}