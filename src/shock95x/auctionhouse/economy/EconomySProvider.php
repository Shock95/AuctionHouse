<?php
namespace shock95x\auctionhouse\economy;

use onebone\economyapi\EconomyAPI;
use ReflectionException;

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
	 * @param $player
	 * @param int $amount
	 *
	 * @throws ReflectionException
	 */
	public function addMoney($player, int $amount): void {
		$this->economyAPI->addMoney($player, $amount);
	}

	/**
	 * @param $player
	 * @param int $amount
	 *
	 * @throws ReflectionException
	 */
	public function subtractMoney($player, int $amount): void {
		$this->economyAPI->reduceMoney($player, $amount);
	}

	/**
	 * @param $player
	 *
	 * @return int
	 */
	public function getMoney($player): int {
		return $this->economyAPI->myMoney($player);
	}

	/**
	 * @return string
	 */
	public function getMonetaryUnit(): string {
		return $this->economyAPI->getMonetaryUnit();
	}
}