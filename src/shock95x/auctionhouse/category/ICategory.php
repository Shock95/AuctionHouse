<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\category;

use pocketmine\item\Item;
use shock95x\auctionhouse\AHListing;

interface ICategory{

	public function sort(AHListing $listing) : bool; // Sort function for category

	public function getName() : string;  // Category name

	public function getDisplayName() : string; // Category display name (Formatted)

	public function getMenuItem() : Item; // Category menu item

}
