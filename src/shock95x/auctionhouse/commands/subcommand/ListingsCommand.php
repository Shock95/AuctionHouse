<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\commands\subcommand;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use CortexPE\Commando\exception\ArgumentOrderException;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use shock95x\auctionhouse\commands\arguments\PlayerArgument;
use shock95x\auctionhouse\menu\ListingsMenu;
use shock95x\auctionhouse\menu\player\PlayerListingMenu;
use shock95x\auctionhouse\menu\type\AHMenu;
use shock95x\auctionhouse\utils\Locale;
use function assert;
use function strtolower;

class ListingsCommand extends BaseSubCommand{

	/**
	 * @throws ArgumentOrderException
	 */
	protected function prepare() : void{
		$this->setPermission("auctionhouse.command.listings");
		$this->registerArgument(0, new PlayerArgument("player", true));
		$this->addConstraint(new InGameRequiredConstraint($this));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
		assert($sender instanceof Player);
		if(!isset($args["player"])){
			AHMenu::open(new ListingsMenu($sender, false));
			return;
		}
		$player = strtolower($args["player"]);
		if(strtolower($sender->getName()) == $player){
			Locale::sendMessage($sender, "player-listings-usage");
			return;
		}
		AHMenu::open(new PlayerListingMenu($sender, $args["player"]));
	}
}
