<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\economy;

use pocketmine\Player;

interface EconomyProvider {

	/**
	 * @param string|Player $player
	 * @param int|float $amount
	 */
	public function addMoney($player, $amount): void;

	/**
	 * @param string|Player $player
	 * @param int|float $amount
	 */
	public function subtractMoney($player, $amount): void;

	/**
	 * @param string|Player $player
	 *
	 * @return int|float
	 */
	public function getMoney($player);

	/**
	 * Returns the monetary unit
	 *
	 * @return string
	 */
	public function getMonetaryUnit(): string;

}