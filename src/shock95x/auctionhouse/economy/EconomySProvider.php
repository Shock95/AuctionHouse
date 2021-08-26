<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\economy;

use onebone\economyapi\EconomyAPI;
use pocketmine\Player;

class EconomySProvider implements EconomyProvider {

	protected ?EconomyAPI $economyAPI;

	public function __construct() {
		$this->economyAPI = EconomyAPI::getInstance();
	}

	/**
	 * @param string|Player $player
	 * @param float $amount
	 */
	public function addMoney($player, float $amount): void {
		$this->economyAPI->addMoney($player, $amount);
	}

	/**
	 * @param string|Player $player
	 * @param float $amount
	 */
	public function subtractMoney($player, float $amount): void {
		$this->economyAPI->reduceMoney($player, $amount);
	}

	/**
	 * @param string|Player $player
	 *
	 * @return float
	 */
	public function getMoney($player): float {
		return $this->economyAPI->myMoney($player);
	}

	public function getMonetaryUnit(): string {
		return $this->economyAPI->getMonetaryUnit();
	}

	public static function getName(): string {
		return "EconomyAPI";
	}
}