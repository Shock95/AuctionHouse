<?php

namespace shock95x\auctionhouse\commands\subcommand\admin;

use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\CortexPE\Commando\BaseSubCommand;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\CortexPE\Commando\constraint\InGameRequiredConstraint;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use shock95x\auctionhouse\commands\arguments\PlayerArgument;
use shock95x\auctionhouse\menu\admin\AdminListingsMenu;
use shock95x\auctionhouse\menu\type\AHMenu;

class AdminListingsSubCommand extends BaseSubCommand {

	protected function prepare(): void {
		$this->addConstraint(new InGameRequiredConstraint($this));
		$this->registerArgument(0, new PlayerArgument("player", false));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		assert($sender instanceof Player);
		(new AdminListingsMenu($sender, $args["player"]))->open();
	}
}