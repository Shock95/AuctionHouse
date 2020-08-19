<?php
namespace shock95x\auctionhouse\commands\subcommand;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\Player;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\economy\EconomyProvider;
use shock95x\auctionhouse\event\ItemListedEvent;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;

class SellCommand extends BaseSubCommand {

	protected function prepare(): void {
		$this->registerArgument(0, new IntegerArgument("price"));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		if(!$sender instanceof Player) {
			return;
		}
		$item = $sender->getInventory()->getItemInHand();
		if($item == null || $item->getId() == Item::AIR) {
			$sender->sendMessage(Locale::getMessage($sender, "no-item"));
			return;
		}
		if($sender->isCreative() && !Settings::getCreativeSale()) {
			$sender->sendMessage(Locale::getMessage($sender, "in-creative"));
			return;
		}
		if(Utils::isBlacklisted($item)) {
			$sender->sendMessage(Locale::getMessage($sender, "item-blacklisted"));
			return;
		}
		if(!isset($args["price"]) || !is_numeric($args["price"])) {
			Locale::getMessage($sender, "invalid-price");
			return;
		}
		$price = $args["price"];
		if(count(DataHolder::getListingsByPlayer($sender)) >= (Settings::getMaxItems())) {
			Locale::getMessage($sender, "max-listings");
			return;
		}
		$listingPrice = Settings::getListingPrice();
		if(($this->getEconomy()->getMoney($sender) < $listingPrice) && $listingPrice != 0) {
			Locale::getMessage($sender, "invalid-balance");
			return;
		}
		if($price < Settings::getMinPrice() || ($price > Settings::getMaxPrice() && Settings::getMaxPrice() != -1)) {
			$sender->sendMessage(str_replace(["@min", "@max"], [Settings::getMinPrice(), Settings::getMaxPrice()], Locale::getMessage($sender, "price-range", true)));
			return;
		}
		$event = new ItemListedEvent($sender, $item, $price);
		$event->call();
		if(!$event->isCancelled()) {
			if($listingPrice != 0) $this->getEconomy()->subtractMoney($sender, $listingPrice);
			$sender->getInventory()->removeItem($item);
			DataHolder::addListing($sender, $item, (int) $price);
			$sender->sendMessage(str_replace(["@player", "@item", "@price", "@amount"], [$sender->getName(), $item->getName(), $this->getEconomy()->getMonetaryUnit() . $price, $item->getCount()], Locale::getMessage($sender, "item-listed", true)));
		}
	}

	public function getEconomy() : ?EconomyProvider {
		if(!$this->getPlugin() instanceof AuctionHouse) {
			return null;
		}
		return $this->getPlugin()->getEconomyProvider();
	}
}