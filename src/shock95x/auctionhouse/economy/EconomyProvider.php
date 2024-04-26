<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\economy;

use pocketmine\player\Player;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\SOFe\AwaitGenerator\Await;

abstract class EconomyProvider {

	public abstract function addMoney(string|Player $player, int $amount, callable $callback): void;

	public function addMoneyAsync(string|Player $player, int $amount): \Generator {
		return yield from Await::promise(fn($resolve) => $this->addMoney($player, $amount, $resolve));
	}

	public abstract function subtractMoney(string|Player $player, int $amount, callable $callback): void;

	public function subtractMoneyAsync(string|Player $player, int $amount): \Generator {
		return yield from Await::promise(fn($resolve) => $this->subtractMoney($player, $amount, $resolve));
	}

	/**
	 * Get currency symbol of economy provider
	 * @return string
	 */
	public abstract function getCurrencySymbol(): string;

	/**
	 * Get name of economy provider
	 * @return string
	 */
	public abstract static function getName() : string;
}