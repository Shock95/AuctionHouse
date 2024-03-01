<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use pocketmine\player\Player;
use shock95x\auctionhouse\AHListing;

class ItemPurchasedEvent extends Event implements Cancellable{

	use CancellableTrait;

	public function __construct(
		private Player $player,
		private AHListing $listing
	){
	}

	public function getPlayer() : Player{
		return $this->player;
	}

	public function getListing() : AHListing{
		return $this->listing;
	}
}
