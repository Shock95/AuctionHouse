<?php
namespace shock95x\auctionhouse\task;

use pocketmine\scheduler\Task;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\event\AuctionEndEvent;

class ListingExpireTask extends Task {

	public function onRun(int $currentTick) {
		foreach(DataHolder::getListings() as $listing) {
            if(time() >= $listing->getEndTime()) {
                DataHolder::setExpired($listing);
                (new AuctionEndEvent($listing, AuctionEndEvent::EXPIRED))->call();
            }
        }
    }
}