<?php
namespace shock95x\auctionhouse\commands\subcommand;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\utils\Utils;

class AboutCommand extends BaseSubCommand {

	protected function prepare(): void {}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		$sender->sendMessage(Utils::prefixMessage(TextFormat::BLUE . "This server is running " . TextFormat::GOLD  . "AuctionHouse v" . $this->getPlugin()->getDescription()->getVersion() . TextFormat::BLUE . " by " . TextFormat::GREEN . "Shock95x"));
	}
}