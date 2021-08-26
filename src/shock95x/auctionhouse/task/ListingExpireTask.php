<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\task;

use DateTime;
use pocketmine\scheduler\Task;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\event\AuctionEndEvent;
use shock95x\auctionhouse\utils\Settings;

class ListingExpireTask extends Task {

	public function onRun(int $currentTick): void {
		foreach(DataHolder::getListings(true) as $listing) {
            if(time() >= $listing->getEndTime() && !$listing->isExpired()) {
                DataHolder::setExpired($listing);
                (new AuctionEndEvent($listing, AuctionEndEvent::EXPIRED))->call();
            }
            if(Settings::getExpiredDuration() != -1 && $listing->isExpired()) {
				$timeStamp = new DateTime();
				$timeStamp->setTimestamp($listing->getEndTime());
				if($timeStamp->diff(new DateTime())->days >= Settings::getExpiredDuration()) {
					DataHolder::removeListing($listing);
					(new AuctionEndEvent($listing, AuctionEndEvent::EXPIRED_PURGED))->call();
				}
			}
        }
    }
}