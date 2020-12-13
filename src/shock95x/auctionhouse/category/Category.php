<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\category;

use pocketmine\item\Item;
use shock95x\auctionhouse\auction\Listing;

interface Category {

	public function sort(Listing $listing): bool;

	public function getName(): string;

	public function getDisplayName(): string;

	public function getMenuItem(): Item;

}