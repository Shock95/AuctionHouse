<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\event;

use pocketmine\event\Event;
use pocketmine\player\IPlayer;
use pocketmine\player\Player;
use pocketmine\Server;
use shock95x\auctionhouse\AHListing;

class AuctionEndEvent extends Event {

	const CANCELLED = 0;
	const PURCHASED = 1;
	const ADMIN_REMOVED = 2;

	public function __construct(
		private AHListing $listing,
		private int $type,
		private ?Player $buyer = null
	) {}

	public function getListing() : AHListing {
		return $this->listing;
	}

	public function getType() : int {
		return $this->type;
	}

	public function getBuyer() : ?Player {
		return $this->buyer;
	}

	public function getSeller() : ?IPlayer {
		$listing = $this->listing;
		return Server::getInstance()->getPlayerByUUID($listing->getSellerUUID()) ?? Server::getInstance()->getOfflinePlayer($listing->getSeller());
	}
}