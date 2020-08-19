<?php
namespace shock95x\auctionhouse\commands\subcommand;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\menu\ListingsMenu;
use shock95x\auctionhouse\menu\player\PlayerListingMenu;
use shock95x\auctionhouse\utils\Locale;

class ListingsCommand extends BaseSubCommand {

	protected function prepare(): void {
		$this->registerArgument(0, new RawStringArgument("player", true));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		if(!$sender instanceof Player) {
			return;
		}
		if(!isset($args["player"])) {
			new ListingsMenu($sender, false);
			return;
		}
		$player = strtolower($args["player"]);

		if(strtolower($sender->getName()) == $player) {
			Locale::getMessage($sender, "player-listings-usage");
			return;
		}
		if(empty(DataHolder::getListingsByUsername($player))) {
			Locale::getMessage($sender, "player-not-found");
			return;
		}
		new PlayerListingMenu($sender, $args["player"]);
	}
}