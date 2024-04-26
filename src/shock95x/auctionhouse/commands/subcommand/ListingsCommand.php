<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\commands\subcommand;

use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\CortexPE\Commando\BaseSubCommand;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\CortexPE\Commando\constraint\InGameRequiredConstraint;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\CortexPE\Commando\exception\ArgumentOrderException;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use shock95x\auctionhouse\commands\arguments\PlayerArgument;
use shock95x\auctionhouse\menu\ListingsMenu;
use shock95x\auctionhouse\menu\player\PlayerListingsMenu;
use shock95x\auctionhouse\menu\type\AHMenu;
use shock95x\auctionhouse\utils\Locale;

class ListingsCommand extends BaseSubCommand {

	/**
	 * @throws ArgumentOrderException
	 */
	protected function prepare(): void {
		$this->setPermission("auctionhouse.command.listings");
		$this->registerArgument(0, new PlayerArgument("player", true));
		$this->addConstraint(new InGameRequiredConstraint($this));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		assert($sender instanceof Player);
		if(!isset($args["player"])) {
			(new ListingsMenu($sender))->open();
			return;
		}
		$player = strtolower($args["player"]);
		if(strtolower($sender->getName()) == $player) {
			Locale::sendMessage($sender, "player-listings-usage");
			return;
		}
		(new PlayerListingsMenu($sender, $args["player"]))->open();
	}
}