<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\event;

use pocketmine\event\Cancellable;
use pocketmine\event\Event;
use pocketmine\Player;
use shock95x\auctionhouse\AHListing;

class ItemPurchasedEvent extends Event implements Cancellable {

	private Player $player;
	private AHListing $listing;

	public function __construct(Player $player, AHListing $listing) {
		$this->player = $player;
		$this->listing = $listing;
	}

	public function getPlayer() : Player {
		return $this->player;
	}

	public function getListing() : AHListing {
		return $this->listing;
	}
}