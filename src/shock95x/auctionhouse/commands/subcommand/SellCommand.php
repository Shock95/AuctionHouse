<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\commands\subcommand;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseSubCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use DateTime;
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

	/**
	 * @throws ArgumentOrderException
	 */
	protected function prepare(): void {
		$this->setPermission("auctionhouse.command.sell");
		$this->registerArgument(0, new IntegerArgument("price"));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		if(!$sender instanceof Player) {
			return;
		}
		$item = $sender->getInventory()->getItemInHand();
		if($item == null || $item->getId() == Item::AIR) {
			Locale::sendMessage($sender, "no-item");
			return;
		}
		if($sender->isCreative() && !Settings::getCreativeSale()) {
			Locale::sendMessage($sender, "in-creative");
			return;
		}
		if(Utils::isBlacklisted($item)) {
			Locale::sendMessage($sender, "item-blacklisted");
			return;
		}
		if(!isset($args["price"]) || !is_numeric($args["price"])) {
			Locale::sendMessage($sender, "invalid-price");
			return;
		}
		$price = $args["price"];
		if(count(DataHolder::getListingsByPlayer($sender)) >= (Utils::getMaxListings($sender))) {
			$sender->sendMessage(str_replace(["@max"], [Utils::getMaxListings($sender)], Locale::getMessage($sender, "max-listings", true)));
			return;
		}
		$listingPrice = Settings::getListingPrice();
		if(($this->getEconomy()->getMoney($sender) < $listingPrice) && $listingPrice != 0) {
			Locale::sendMessage($sender, "invalid-balance");
			return;
		}
		if($price < Settings::getMinPrice() || ($price > Settings::getMaxPrice() && Settings::getMaxPrice() != -1)) {
			$sender->sendMessage(str_replace(["@min", "@max"], [Settings::getMinPrice(), Settings::getMaxPrice()], Locale::getMessage($sender, "price-range", true)));
			return;
		}
		if(Settings::getListingCooldown() != 0) {
			if(Utils::inCooldown($sender)) {
				$cooldown = Utils::getCooldown($sender);
				$endTime = (new DateTime())->diff((new DateTime())->setTimestamp($cooldown));
				$sender->sendMessage(str_replace(["@M", "@S"], [$endTime->i, $endTime->s], Locale::getMessage($sender, "in-cooldown", true)));
				return;
			} else {
				Utils::setCooldown($sender);
			}
		}
		$event = new ItemListedEvent($sender, $item, $price);
		$event->call();
		if(!$event->isCancelled()) {
			if($listingPrice != 0) $this->getEconomy()->subtractMoney($sender, $listingPrice);
			$sender->getInventory()->removeItem($item);
			$listing = DataHolder::addListing($sender, $item, (int) $price);
			$sender->sendMessage(str_replace(["@player", "@item", "@price", "@amount"], [$sender->getName(), $item->getName(), $listing->getPrice(true, Settings::formatPrice()), $item->getCount()], Locale::getMessage($sender, "item-listed", true)));
		}
	}

	public function getEconomy(): ?EconomyProvider {
		if(!$this->getPlugin() instanceof AuctionHouse) {
			return null;
		}
		return $this->getPlugin()->getEconomyProvider();
	}
}