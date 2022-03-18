<?php

namespace shock95x\auctionhouse\economy;

use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;
use pocketmine\player\Player;

class BedrockEconomyProvider implements EconomyProvider {

	protected ?BedrockEconomyAPI $economy;

	public function __construct() {
		$this->economy = BedrockEconomyAPI::getInstance();
	}

	public function addMoney(string|Player $player, float $amount, ?callable $callback = null): void {
		if($player instanceof Player) $player = $player->getName();
		$this->economy->addToPlayerBalance($player, $amount, ClosureContext::create(fn (bool $r) => $callback($r)));
	}

	public function subtractMoney(string|Player $player, float $amount, ?callable $callback = null): void {
		if($player instanceof Player) $player = $player->getName();
		$this->economy->subtractFromPlayerBalance($player, $amount, ClosureContext::create(fn (bool $r) => $callback($r)));
	}

	public function getMoney(string|Player $player, callable $callback): void {
		if($player instanceof Player) $player = $player->getName();
		$this->economy->getPlayerBalance($player, ClosureContext::create(fn (?int $balance) => $callback($balance)));
	}

	public function getMonetaryUnit(): string {
		return "";
	}

	public static function getName(): string {
		return "BedrockEconomy";
	}
}