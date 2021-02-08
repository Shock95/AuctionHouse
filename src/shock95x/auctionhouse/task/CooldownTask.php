<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\task;

use pocketmine\scheduler\Task;
use shock95x\auctionhouse\utils\Utils;

class CooldownTask extends Task {

	private $uniqueId;

	public function __construct(string $uniqueId) {
		$this->uniqueId = $uniqueId;
	}

	public function onRun(int $currentTick): void {
		Utils::removeCooldown($this->uniqueId);
	}
}