<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\economy;

use pocketmine\player\Player;

interface EconomyProvider{

	public function addMoney(string|Player $player, float $amount, callable $callback) : void;

	public function subtractMoney(string|Player $player, float $amount, callable $callback) : void;

	/**
	 * Get currency symbol of economy provider
	 */
	public function getCurrencySymbol() : string;

	/**
	 * Get name of economy provider
	 */
	public static function getName() : string;
}
