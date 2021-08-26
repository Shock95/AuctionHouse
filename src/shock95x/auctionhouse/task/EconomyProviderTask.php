<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\task;

use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\utils\Settings;

class EconomyProviderTask extends Task {

	private AuctionHouse $plugin;

	public function __construct(AuctionHouse $plugin) {
		$this->plugin = $plugin;
	}

	public function onRun(int $currentTick) {
		$plugin = $this->plugin;
		if(!$plugin->getEconomyProvider()) {
			$plugin->getLogger()->notice("Unable to find an economy provider, disabling plugin...");
			$plugin->getServer()->getPluginManager()->disablePlugin($plugin);
			return;
		}
		Settings::setMonetaryUnit($plugin->getEconomyProvider()->getMonetaryUnit());
	}
}