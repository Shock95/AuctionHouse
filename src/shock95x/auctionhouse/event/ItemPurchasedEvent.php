<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\event;

use pocketmine\event\Cancellable;
use pocketmine\event\Event;
use pocketmine\Player;
use shock95x\auctionhouse\auction\Listing;

class ItemPurchasedEvent extends Event implements Cancellable {

	private $player;
	private $listing;

	public function __construct(Player $player, Listing $listing) {
		$this->player = $player;
		$this->listing = $listing;
	}

	public function getPlayer() : Player {
		return $this->player;
	}

	public function getListing() : Listing {
		return $this->listing;
	}
}