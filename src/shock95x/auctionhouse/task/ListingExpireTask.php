<?php
namespace shock95x\auctionhouse\task;

use pocketmine\scheduler\Task;
use shock95x\auctionhouse\database\Database;

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