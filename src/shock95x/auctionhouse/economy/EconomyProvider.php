<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\economy;

use pocketmine\Player;

interface EconomyProvider {

	/**
	 * @param string|Player $player
	 * @param float $amount
	 */
	public function addMoney($player, float $amount): void;

	/**
	 * @param string|Player $player
	 * @param float $amount
	 */
	public function subtractMoney($player, float $amount): void;

	/**
	 * @param string|Player $player
	 *
	 * @return float
	 */
	public function getMoney($player): float;

	/**
	 * Returns the monetary unit
	 *
	 * @return string
	 */
	public function getMonetaryUnit(): string;

	public static function getName() : string;
}