<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\event;

use pocketmine\event\Event;
use pocketmine\IPlayer;
use pocketmine\Player;
use pocketmine\Server;
use shock95x\auctionhouse\AHListing;

class AuctionEndEvent extends Event {

	private AHListing $listing;
	private ?Player $purchaser;

	private int $type;

	const CANCELLED = 0;
	const EXPIRED = 1;
	const PURCHASED = 2;
	const EXPIRED_PURGED = 3;
	const ADMIN_PURGED = 4;
	const ADMIN_REMOVED = 5;

	public function __construct(AHListing $listing, int $type, ?Player $purchaser = null) {
		$this->listing = $listing;
		$this->type = $type;
		$this->purchaser = $purchaser;
	}

	public function getListing() : AHListing {
		return $this->listing;
	}

	public function getType() : int {
		return $this->type;
	}

	public function getPurchaser() : ?Player {
		return $this->purchaser;
	}

	public function getSeller() : ?IPlayer {
		$listing = $this->listing;
		return Server::getInstance()->getPlayerByRawUUID($listing->getSellerUUID()) ?? Server::getInstance()->getOfflinePlayer($listing->getSeller());
	}
}