<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\category\defaults;

use pocketmine\item\Item;
use pocketmine\item\Potion;
use pocketmine\item\SplashPotion;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\category\Category;

class PotionCategory implements Category {

	public function sort(AHListing $listing): bool {
		return $listing->getItem() instanceof Potion || $listing->getItem() instanceof SplashPotion;
	}

	public function getName(): string {
		return "Potions";
	}

	public function getDisplayName(): string {
		return TextFormat::BOLD . TextFormat::LIGHT_PURPLE . $this->getName();
	}

	public function getMenuItem(): Item {
		return Item::get(Item::POTION)->setCustomName(TextFormat::RESET . $this->getDisplayName());
	}
}