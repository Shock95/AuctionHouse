<?php

namespace shock95x\auctionhouse\economy;

use pocketmine\Player;

interface EconomyProvider {

	/**
	 * @param string|Player $player
	 * @param int|float $amount
	 */
	public function addMoney($player, int $amount): void;

	/**
	 * @param string|Player $player
	 * @param int|float $amount
	 */
	public function subtractMoney($player, int $amount): void;

	/**
	 * @param string|Player $player
	 *
	 * @return int|float
	 */
	public function getMoney($player): int;

	/**
	 * Returns the monetary unit
	 *
	 * @return string
	 */
	public function getMonetaryUnit() : string;
}
