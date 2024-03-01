<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\event;

use pocketmine\event\Event;
use pocketmine\player\Player;
use shock95x\auctionhouse\menu\type\AHMenu;

class MenuCloseEvent extends Event{

	public function __construct(
		private Player $player,
		private AHMenu $menu
	){
	}

	public function getPlayer() : Player{
		return $this->player;
	}

	public function getMenu() : AHMenu{
		return $this->menu;
	}
}
