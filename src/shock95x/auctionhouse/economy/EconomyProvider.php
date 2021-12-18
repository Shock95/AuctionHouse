<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\economy;

use pocketmine\player\Player;

interface EconomyProvider {

	public function addMoney(string|Player $player, float $amount, ?callable $callback = null): void;

	public function subtractMoney(string|Player $player, float $amount, ?callable $callback = null): void;

	public function getMoney(string|Player $player, callable $callback): void;

	/**
	 * Get monetary unit of economy provider
	 * @return string
	 */
	public function getMonetaryUnit(): string;

	/**
	 * Get name of economy provider
	 * @return string
	 */
	public static function getName() : string;
}