<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\commands\subcommand;

use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use CortexPE\Commando\exception\ArgumentOrderException;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use shock95x\auctionhouse\category\ICategory;
use shock95x\auctionhouse\commands\arguments\CategoryArgument;
use shock95x\auctionhouse\menu\category\CategoryListMenu;
use shock95x\auctionhouse\menu\category\CategoryMenu;
use shock95x\auctionhouse\menu\type\AHMenu;
use function assert;

class CategoryCommand extends BaseSubCommand{

	/**
	 * @throws ArgumentOrderException
	 */
	protected function prepare() : void{
		$this->setPermission("auctionhouse.command.category");
		$this->registerArgument(0, new CategoryArgument("name", true));
		$this->addConstraint(new InGameRequiredConstraint($this));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
		assert($sender instanceof Player);
		if(!isset($args["name"])){
			AHMenu::open(new CategoryListMenu($sender, false));
			return;
		}
		$category = $args["name"];
		if($category instanceof ICategory){
			AHMenu::open(new CategoryMenu($sender, $category));
		}
	}
}
