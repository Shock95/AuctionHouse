<?php
namespace shock95x\auctionhouse\event;

use pocketmine\event\Event;
use pocketmine\Player;

class MenuCloseEvent extends Event {

	private $player;
	private $type;

	public function __construct(Player $player, int $type) {
		$this->player = $player;
		$this->type = $type;
	}

	public function getPlayer() : Player {
		return $this->player;
	}

	public function getType() : int {
		return $this->type;
	}
}