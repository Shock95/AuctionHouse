<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\task;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\scheduler\Task;

class CannotPurchaseTask extends Task {

	/** @var Inventory  */
	private $inventory;
	/** @var Item  */
	private $item;
	/** @var int  */
	private $slot;

	public function __construct(Inventory $inventory, Item $item, int $slot) {
		$this->inventory = $inventory;
		$this->item = $item;
		$this->slot = $slot;
	}

	public function onRun(int $currentTick): void {
		if($this->inventory != null) {
			if($this->inventory->slotExists($this->slot)) {
				$this->inventory->setItem($this->slot, $this->item);
			}
		}
	}
}