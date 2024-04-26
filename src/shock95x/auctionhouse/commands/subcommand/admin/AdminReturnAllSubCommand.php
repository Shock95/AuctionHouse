<?php

namespace shock95x\auctionhouse\commands\subcommand\admin;

use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\Query;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\SOFe\AwaitGenerator\Await;

class AdminReturnAllSubCommand extends BaseSubCommand {

	protected function prepare(): void {}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
		Await::f2c(function() use ($sender){
			$database = AuctionHouse::getInstance()->getDatabase();
			yield from $database->getConnector()->asyncChange(Query::EXPIRE_ALL);
		});
	}
}