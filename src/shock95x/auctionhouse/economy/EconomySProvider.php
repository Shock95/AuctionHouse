<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\economy;

use onebone\economyapi\EconomyAPI;
use pocketmine\Player;

class EconomySProvider implements EconomyProvider {

	/** @var EconomyAPI */
	protected $economyAPI;

	/**
	 * EconomySProvider constructor.
	 */
	public function __construct() {
		$this->economyAPI = EconomyAPI::getInstance();
	}

	/**
	 * @param string|Player $player
	 * @param int|float $amount
	 */
	public function addMoney($player, $amount): void {
		$this->economyAPI->addMoney($player, $amount);
	}

	/**
	 * @param string|Player $player
	 * @param int|float $amount
	 */
	public function subtractMoney($player, $amount): void {
		$this->economyAPI->reduceMoney($player, $amount);
	}

	/**
	 * @param string|Player $player
	 *
	 * @return int|float
	 */
	public function getMoney($player) {
		return $this->economyAPI->myMoney($player);
	}

	/**
	 * @return string
	 */
	public function getMonetaryUnit(): string {
		return $this->economyAPI->getMonetaryUnit();
	}
}