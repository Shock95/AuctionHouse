<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\commands\subcommand;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\utils\Utils;

class ReloadCommand extends BaseSubCommand{

	protected function prepare() : void{
		$this->setPermission("auctionhouse.command.reload");
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
		AuctionHouse::getInstance()->reload();
		$sender->sendMessage(Utils::prefixMessage(TextFormat::GREEN . "Configuration files reloaded"));
	}
}
