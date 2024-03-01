<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\category\defaults;

use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\category\ICategory;

class EnchantedCategory implements ICategory{

	public function sort(AHListing $listing) : bool{
		return $listing->getItem()->hasEnchantments();
	}

	public function getName() : string{
		return "Enchanted";
	}

	public function getDisplayName() : string{
		return TextFormat::BOLD . TextFormat::GOLD . $this->getName();
	}

	public function getMenuItem() : Item{
		$item = VanillaItems::BOOK()->setCustomName(TextFormat::RESET . $this->getDisplayName());
		$item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(AuctionHouse::FAKE_ENCH_ID)));
		return $item;
	}
}
