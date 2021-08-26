<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\category\defaults;

use pocketmine\item\Item;
use pocketmine\nbt\tag\ListTag;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\category\Category;

class EnchantedCategory implements Category {

	public function sort(AHListing $listing): bool {
		return $listing->getItem()->hasEnchantments();
	}

	public function getName(): string {
		return "Enchanted";
	}

	public function getDisplayName(): string {
		return TextFormat::BOLD . TextFormat::GOLD . $this->getName();
	}

	public function getMenuItem(): Item {
		$item = Item::get(Item::BOOK)->setCustomName(TextFormat::RESET . $this->getDisplayName());
		$item->setNamedTagEntry(new ListTag("ench"));
		return $item;
	}
}