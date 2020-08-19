<?php
namespace shock95x\auctionhouse\commands;

use CortexPE\Commando\BaseCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use shock95x\auctionhouse\commands\subcommand\AboutCommand;
use shock95x\auctionhouse\commands\subcommand\AdminCommand;
use shock95x\auctionhouse\commands\subcommand\ExpiredCommand;
use shock95x\auctionhouse\commands\subcommand\ListingsCommand;
use shock95x\auctionhouse\commands\subcommand\ReloadCommand;
use shock95x\auctionhouse\commands\subcommand\SellCommand;
use shock95x\auctionhouse\commands\subcommand\ShopCommand;
use shock95x\auctionhouse\menu\MainMenu;

class AHCommand extends BaseCommand {

	protected function prepare(): void {
		$this->registerSubCommand(new ShopCommand("shop", "Shows AH shop menu"));
		$this->registerSubCommand(new AdminCommand("admin", "Shows AH admin menu"));
		$this->registerSubCommand(new SellCommand("sell", "Sell item in hand to the AH"));
		$this->registerSubCommand(new ListingsCommand("listings", "Shows player listings"));
		$this->registerSubCommand(new ExpiredCommand("expired", "Shows expired listings"));
		$this->registerSubCommand(new ReloadCommand("reload", "Reload plugin configuration files"));
		$this->registerSubCommand(new AboutCommand("about", "Plugin information"));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		if(!$sender instanceof Player) {
			return;
		}
		if(count($args) == 0) {
			new MainMenu($sender);
		}
	}
}
