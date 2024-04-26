<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\commands\subcommand\admin;

use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use shock95x\auctionhouse\menu\admin\AdminMenu;
use shock95x\auctionhouse\menu\type\AHMenu;

class AdminCommand extends BaseSubCommand{

	protected function prepare() : void{
		$this->setPermission("auctionhouse.command.admin");
		$this->registerSubCommand(new AdminListingsSubCommand($this->plugin, "listings", "View a player's listings"));
		$this->registerSubCommand(new AdminRelistAllSubCommand($this->plugin, "relistall", "Relist all items"));
		$this->registerSubCommand(new AdminReturnAllSubCommand($this->plugin, "returnall", "Return all items"));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
		if($sender instanceof Player){
			(new AdminMenu($sender))->open();
		}
	}
}