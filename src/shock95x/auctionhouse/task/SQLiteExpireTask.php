<?php
namespace shock95x\auctionhouse\task;

use pocketmine\scheduler\Task;
use shock95x\auctionhouse\database\Database;
use shock95x\auctionhouse\utils\Settings;
use SOFe\AwaitGenerator\Await;

class SQLiteExpireTask extends Task {

	public function __construct(public Database $database) {}

	public function onRun(): void {
		Await::f2c(function () {
			$expiredDuration = Settings::getExpiredDuration();
			yield from $this->database->asyncChangeRaw("UPDATE listings SET expired = TRUE WHERE expired = FALSE AND expires_at <= unixepoch();");
			if($expiredDuration > 0) {
				yield from $this->database->asyncChangeRaw("DELETE FROM listings WHERE expired = TRUE AND expires_at + (:duration * 86400) <= unixepoch();", ["duration" => $expiredDuration]);
			}
		});
	}
}