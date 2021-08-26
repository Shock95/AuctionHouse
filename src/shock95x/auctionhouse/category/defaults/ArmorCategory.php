<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\category\defaults;

use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\category\Category;

class ArmorCategory implements Category {

	public function sort(AHListing $listing): bool {
		return $listing->getItem() instanceof Armor;
	}

	public function getName(): string {
		return "Armor";
	}

	public function getDisplayName(): string {
		return TextFormat::BOLD . TextFormat::AQUA . $this->getName();
	}

	public function getMenuItem(): Item {
		return Item::get(Item::DIAMOND_CHESTPLATE)->setCustomName(TextFormat::RESET . $this->getDisplayName());
	}
}