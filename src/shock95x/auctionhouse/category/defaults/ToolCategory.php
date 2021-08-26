<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\category\defaults;

use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\category\Category;

class ToolCategory implements Category {

	public function sort(AHListing $listing): bool {
		return $listing->getItem() instanceof Tool;
	}

	public function getName(): string {
		return "Tools";
	}

	public function getDisplayName(): string {
		return TextFormat::BOLD . TextFormat::GREEN . $this->getName();
	}

	public function getMenuItem(): Item {
		return Item::get(Item::DIAMOND_AXE)->setCustomName(TextFormat::RESET . $this->getDisplayName());
	}
}