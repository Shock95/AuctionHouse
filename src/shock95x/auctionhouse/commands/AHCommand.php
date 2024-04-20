<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\commands;

use CortexPE\Commando\BaseCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use shock95x\auctionhouse\commands\subcommand\AboutCommand;
use shock95x\auctionhouse\commands\subcommand\admin\AdminCommand;
use shock95x\auctionhouse\commands\subcommand\CategoryCommand;
use shock95x\auctionhouse\commands\subcommand\ExpiredCommand;
use shock95x\auctionhouse\commands\subcommand\ListingsCommand;
use shock95x\auctionhouse\commands\subcommand\ReloadCommand;
use shock95x\auctionhouse\commands\subcommand\SellCommand;
use shock95x\auctionhouse\commands\subcommand\ShopCommand;
use shock95x\auctionhouse\menu\ShopMenu;
use shock95x\auctionhouse\menu\type\AHMenu;

class AHCommand extends BaseCommand {

	protected function prepare() : void {
		$this->setPermission("auctionhouse.command");
		$this->registerSubCommand(new ShopCommand($this->plugin, "shop", "Shows AH shop menu"));
		$this->registerSubCommand(new AdminCommand($this->plugin, "admin", "Opens AH admin menu"));
		$this->registerSubCommand(new SellCommand($this->plugin, "sell", "Sell item in hand to the AH"));
		$this->registerSubCommand(new CategoryCommand($this->plugin, "category", "Opens category menu"));
		$this->registerSubCommand(new ListingsCommand($this->plugin, "listings", "Shows player listings"));
		$this->registerSubCommand(new ExpiredCommand($this->plugin, "expired", "Shows expired listings"));
		$this->registerSubCommand(new ReloadCommand($this->plugin, "reload", "Reload plugin configuration files"));
		$this->registerSubCommand(new AboutCommand($this->plugin, "about", "Plugin information"));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		if(count($args) == 0 && $sender instanceof Player) {
			AHMenu::open(new ShopMenu($sender));
		}
	}
}
