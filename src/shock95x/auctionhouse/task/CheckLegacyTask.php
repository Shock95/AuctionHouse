<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\task;

use pocketmine\scheduler\Task;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\legacy\LegacyConverter;
use SOFe\AwaitGenerator\Await;

class CheckLegacyTask extends Task{

	public function __construct(private AuctionHouse $plugin){ }

	public function onRun() : void{
		Await::f2c(function(){
			if(yield from LegacyConverter::getInstance()->isLegacy()){
				$this->plugin->getLogger()->notice("Old database format detected! Run '/ah convert' to update");
			}
		});
	}
}
