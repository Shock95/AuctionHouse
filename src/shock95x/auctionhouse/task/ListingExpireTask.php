<?php
namespace shock95x\auctionhouse\task;

use shock95x\auctionhouse\database\Database;
use pocketmine\scheduler\Task;

class ListingExpireTask extends Task {

	private $database;

	/**
	 * ListingExpireTask constructor.
	 *
	 * @param Database $database
	 */
	public function __construct(Database $database) {
		$this->database = $database;
	}

	/**
	 * Actions to execute when run
	 *
	 * @param int $currentTick
	 *
	 * @return void
	 */
	public function onRun(int $currentTick) {
		$this->database->save();
	}
}
