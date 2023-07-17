<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use pocketmine\item\Item;
use pocketmine\player\Player;

class ItemListedEvent extends Event implements Cancellable{

	use CancellableTrait;

	public function __construct(
		private Player $player,
		private Item $item,
		private int $price
	){
	}

	public function getItem() : Item{
		return $this->item;
	}

	public function getPlayer() : Player{
		return $this->player;
	}

	public function getPrice() : int{
		return $this->price;
	}
}
