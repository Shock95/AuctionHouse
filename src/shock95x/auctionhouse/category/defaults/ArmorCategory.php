<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\category\defaults;

use pocketmine\item\Armor;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\category\ICategory;

class ArmorCategory implements ICategory{

	public function sort(AHListing $listing) : bool{
		return $listing->getItem() instanceof Armor;
	}

	public function getName() : string{
		return "Armor";
	}

	public function getDisplayName() : string{
		return TextFormat::BOLD . TextFormat::AQUA . $this->getName();
	}

	public function getMenuItem() : Item{
		return VanillaItems::DIAMOND_CHESTPLATE()->setCustomName(TextFormat::RESET . $this->getDisplayName());
	}
}
