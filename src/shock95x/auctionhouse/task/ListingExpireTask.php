<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\task;

use DateTime;
use pocketmine\scheduler\Task;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\database\Database;
use shock95x\auctionhouse\database\storage\DataStorage;
use shock95x\auctionhouse\event\AuctionEndEvent;
use shock95x\auctionhouse\utils\Settings;
use SOFe\AwaitGenerator\Await;
use function time;

class ListingExpireTask extends Task{

	public function __construct(public Database $database){ }

	public function onRun() : void{
		Await::f2c(function(){
			$time = new DateTime();
			$dataStorage = DataStorage::getInstance();
			$result = yield from $this->database->asyncSelectRaw("SELECT * from listings WHERE :time >= end_time AND expired = FALSE;", ["time" => time()]);
			$listings = $this->createListingsFromRows($result[0]->getRows());
			foreach($listings as $listing){
				yield from Await::promise(fn($resolve, $reject) => $dataStorage->setExpired($listing, true, $resolve, $reject));
				(new AuctionEndEvent($listing, AuctionEndEvent::EXPIRED))->call();
			}
			$result = yield from $this->database->asyncSelectRaw("SELECT * from listings WHERE expired = TRUE;");
			$listings = $this->createListingsFromRows($result[0]->getRows());
			foreach($listings as $listing){
				$time->setTimestamp($listing->getEndTime());
				if($time->diff(new DateTime())->days >= Settings::getExpiredDuration()){
					yield from Await::promise(fn($resolve, $reject) => $dataStorage->removeListing($listing, $resolve, $reject));
					(new AuctionEndEvent($listing, AuctionEndEvent::EXPIRED_PURGED))->call();
				}
			}
		});
	}

	/**
	 * @return AHListing[]
	 */
	private function createListingsFromRows(array $rows) : array{
		$listings = [];
		foreach($rows as $row){
			$listings[] = $this->database->createListingFromRows($row);
		}
		return $listings;
	}
}
