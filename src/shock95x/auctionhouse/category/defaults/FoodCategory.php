<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\category\defaults;

use pocketmine\item\Food;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\category\Category;

class FoodCategory implements Category {

	public function sort(AHListing $listing): bool {
		return $listing->getItem() instanceof Food;
	}

	public function getName(): string {
		return "Food";
	}

	public function getDisplayName(): string {
		return TextFormat::BOLD . TextFormat::LIGHT_PURPLE . $this->getName();
	}

	public function getMenuItem(): Item {
		return Item::get(Item::STEAK)->setCustomName(TextFormat::RESET . $this->getDisplayName());
	}
}