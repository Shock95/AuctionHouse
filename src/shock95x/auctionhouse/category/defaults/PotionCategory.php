<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\category\defaults;

use pocketmine\block\Sapling;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\item\Potion;
use pocketmine\item\SplashPotion;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\auction\Listing;
use shock95x\auctionhouse\category\Category;

class PotionCategory implements Category {

	public function sort(Listing $listing): bool {
		return $listing->getItem() instanceof Potion || $listing->getItem() instanceof SplashPotion;
	}

	public function getName(): string {
		return "Potion";
	}

	public function getDisplayName(): string {
		return TextFormat::BOLD . TextFormat::LIGHT_PURPLE . $this->getName();
	}

	public function getMenuItem(): Item {
		return Item::get(Item::POTION)->setCustomName(TextFormat::RESET . $this->getDisplayName());
	}
}