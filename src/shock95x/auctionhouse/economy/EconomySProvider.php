<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\economy;

use onebone\economyapi\EconomyAPI;
use pocketmine\player\Player;


class EconomySProvider implements EconomyProvider {

	protected ?EconomyAPI $economyAPI;

	public function __construct() {
		$this->economyAPI = EconomyAPI::getInstance();
	}

	public function addMoney(string|Player $player, float $amount, ?callable $callback = null): void {
		$callback ? $callback($this->economyAPI->addMoney($player, $amount)) : $this->economyAPI->addMoney($player, $amount);
	}

	public function subtractMoney(string|Player $player, float $amount, ?callable $callback = null): void {
		$callback ? $callback($this->economyAPI->reduceMoney($player, $amount)) : $this->economyAPI->reduceMoney($player, $amount);
	}

	public function getMoney(string|Player $player, callable $callback): void {
		$callback($this->economyAPI->myMoney($player));
	}

	public function getMonetaryUnit(): string {
		return $this->economyAPI->getMonetaryUnit();
	}

	public static function getName(): string {
		return "EconomyAPI";
	}
}