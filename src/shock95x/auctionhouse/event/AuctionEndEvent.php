<?php
namespace shock95x\auctionhouse\event;

use pocketmine\event\Event;
use pocketmine\Player;
use pocketmine\Server;
use shock95x\auctionhouse\auction\Listing;

class AuctionEndEvent extends Event {

	private $listing;
	private $type;
	private $purchaser;

	const CANCELLED = 0;
	const EXPIRED = 1;
	const PURCHASED = 2;
	const ADMIN_PURGED = 3;
	const ADMIN_REMOVED = 4;

	public function __construct(Listing $listing, int $type, Player $purchaser = null) {
		$this->listing = $listing;
		$this->type = $type;
		$this->purchaser = $purchaser;
	}

	public function getListing() : Listing {
		return $this->listing;
	}

	public function getType() : int {
		return $this->type;
	}

	public function getPurchaser() : ?Player {
		return $this->purchaser;
	}

	public function getSeller() : Player {
		$player = Server::getInstance()->getPlayerByRawUUID($this->listing->getSeller(true));
		if($player->isOnline()) {
			return $player;
		}
		return Server::getInstance()->getOfflinePlayer($this->listing->getSeller());
	}
}