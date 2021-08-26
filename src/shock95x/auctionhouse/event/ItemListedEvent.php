<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\event;

use pocketmine\event\Cancellable;
use pocketmine\event\Event;
use pocketmine\item\Item;
use pocketmine\Player;

class ItemListedEvent extends Event implements Cancellable {

	private Item $item;
	private Player $player;
	private int $price;

	public function __construct(Player $player, Item $item, int $price) {
		$this->item = $item;
		$this->player = $player;
		$this->price = $price;
	}

	public function getItem() : Item {
		return $this->item;
	}

	public function getPlayer() : Player {
		return $this->player;
	}

	public function getPrice() : int {
		return $this->price;
	}
}