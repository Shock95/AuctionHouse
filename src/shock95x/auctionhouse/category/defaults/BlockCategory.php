<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\category\defaults;

use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\category\Category;

class BlockCategory implements Category {

	public function sort(AHListing $listing): bool {
		return $listing->getItem() instanceof ItemBlock;
	}

	public function getName(): string {
		return "Blocks";
	}

	public function getDisplayName(): string {
		return TextFormat::BOLD . TextFormat::RED . $this->getName();
	}

	public function getMenuItem(): Item {
		return Item::get(Item::BRICK_BLOCK)->setCustomName(TextFormat::RESET . $this->getDisplayName());
	}
}