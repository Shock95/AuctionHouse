<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\libs\_45f135c7cfd69bf7\muqsit\invmenu\inventory;

use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryListener;
use pocketmine\item\Item;

final class SharedInventorySynchronizer implements InventoryListener{

	public function __construct(
		readonly private Inventory $inventory
	){}

	public function getSynchronizingInventory() : Inventory{
		return $this->inventory;
	}

	public function onContentChange(Inventory $inventory, array $old_contents) : void{
		$this->inventory->setContents($inventory->getContents());
	}

	public function onSlotChange(Inventory $inventory, int $slot, Item $old_item) : void{
		$this->inventory->setItem($slot, $inventory->getItem($slot));
	}
}