<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\event;

use pocketmine\event\Event;
use pocketmine\Player;
use pocketmine\Server;
use shock95x\auctionhouse\AHListing;

class AuctionStartEvent extends Event {

	private AHListing $listing;

	public function __construct(AHListing $listing) {
		$this->listing = $listing;
	}

	public function getListing() : AHListing {
		return $this->listing;
	}

	public function getPlayer() : ?Player {
		return Server::getInstance()->getPlayerByRawUUID($this->listing->getSellerUUID());
	}
}