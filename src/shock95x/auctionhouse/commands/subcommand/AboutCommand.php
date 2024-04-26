<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\commands\subcommand;

use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\utils\Utils;

class AboutCommand extends BaseSubCommand {

	protected function prepare() : void{
		$this->setPermission("auctionhouse.command.about");
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		$sender->sendMessage(Utils::prefixMessage(TextFormat::BLUE . "This server is running " . TextFormat::GOLD  . "AuctionHouse v" . $this->getOwningPlugin()->getDescription()->getVersion() . TextFormat::BLUE . " by " . TextFormat::GREEN . "Shock95"));
	}
}