<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\economy;

use pocketmine\player\Player;

interface EconomyProvider {

	public const USAGE_LISTING_PRICE = 0;
	public const USAGE_PURCHASE_PRICE = 1;
	public const USAGE_SALES_PRICE = 2;

	public function addMoney(Player $player, float $amount, array $labels, int $usage, callable $callback): void;

	public function subtractMoney(Player $player, float $amount, array $labels, int $usage, callable $callback): void;

	/**
	 * Get currency symbol of economy provider
	 * @return string
	 */
	public function getCurrencySymbol(): string;

	/**
	 * Get name of economy provider
	 * @return string
	 */
	public static function getName() : string;
}
