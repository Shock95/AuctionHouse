<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\event;

use pocketmine\event\Event;
use pocketmine\player\Player;
use pocketmine\Server;
use shock95x\auctionhouse\AHListing;

class AuctionStartEvent extends Event{

	public function __construct(
		private AHListing $listing
	){
	}

	public function getListing() : AHListing{
		return $this->listing;
	}

	public function getPlayer() : ?Player{
		return Server::getInstance()->getPlayerByRawUUID($this->listing->getSellerUUID());
	}
}
