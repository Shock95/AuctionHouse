<?php

namespace shock95x\auctionhouse\task;

use pocketmine\scheduler\Task;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\legacy\LegacyConverter;
use SOFe\AwaitGenerator\Await;

class CheckLegacyTask extends Task {

	public function __construct(private AuctionHouse $plugin) {}

	public function onRun(): void {
		Await::f2c(function () {
			if(yield LegacyConverter::getInstance()->isLegacy()) {
				$this->plugin->getLogger()->info("Old database format detected, converting...");
				yield LegacyConverter::getInstance()->convert();
				$this->plugin->getLogger()->info("Conversion complete!");
			}
		});
	}
}