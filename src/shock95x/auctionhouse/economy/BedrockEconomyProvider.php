<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\economy;

use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\api\version\BetaBEAPI;
use pocketmine\player\Player;
use pocketmine\Server;

class BedrockEconomyProvider implements EconomyProvider{

	protected ?BetaBEAPI $economy;

	public function __construct(){
		$this->economy = BedrockEconomyAPI::beta();
	}

	public function addMoney(string|Player $player, float $amount, callable $callback) : void
    {
		if($player instanceof Player) $player = $player->getName();
		$this->economy->add($player, (int) $amount)->onCompletion(
            function () use ($callback): void {
                $callback(true);
            },
            function () use ($callback): void {
                $callback(false);
            },
        );
	}

	public function subtractMoney(string|Player $player, float $amount, callable $callback) : void{
		if($player instanceof Player) $player = $player->getName();
		$this->economy->deduct($player, (int) $amount)->onCompletion(
            function () use ($callback): void {
                $callback(true);
            },
            function () use ($callback): void {
                $callback(false);
            },
        );
	}

	public function getCurrencySymbol() : string{
        $eco = Server::getInstance()->getPluginManager()->getPlugin(self::getName());
		return $eco->getCurrencyManager()->getSymbol();
	}

	public static function getName() : string{
		return "BedrockEconomy";
	}
}
