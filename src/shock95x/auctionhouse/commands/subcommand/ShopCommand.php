<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\commands\subcommand;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use shock95x\auctionhouse\menu\ShopMenu;

class ShopCommand extends BaseSubCommand {

	protected function prepare(): void {
		$this->setPermission("auctionhouse.command.shop");
		$this->addConstraint(new InGameRequiredConstraint($this));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		assert($sender instanceof Player);
		new ShopMenu($sender);
	}
}