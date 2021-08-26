<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu;

use muqsit\invmenu\InvMenu;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\level\sound\FizzSound;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\event\AuctionEndEvent;
use shock95x\auctionhouse\event\ItemPurchasedEvent;
use shock95x\auctionhouse\manager\MenuManager;
use shock95x\auctionhouse\menu\type\AHMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;

class ConfirmPurchaseMenu extends AHMenu {

	private AHListing $listing;

	protected static string $inventoryType = InvMenu::TYPE_CHEST;

	public function __construct(Player $player, AHListing $listing) {
		$this->setName(Locale::getMessage($player, "purchase-menu-name"));
		$this->listing = $listing;
		$this->newMenu = true;

		parent::__construct($player);
	}
	
	public function renderItems(): void {
		$listing = $this->listing;
		$item = clone $listing->getItem();

		$info =  Locale::getMessage($this->getPlayer(), "purchase-item");
		$lore = str_replace(["%price%", "%seller%"], [$listing->getPrice(true, Settings::formatPrice()), $listing->getSeller()], preg_filter('/^/', TextFormat::RESET, $info["lore"]));
		$lore = Settings::allowLore() ? [...$item->getLore(), ...$lore] : $lore;

		$item->setLore($lore);
		$this->getInventory()->setItem(13, $item);

		for($x = 9; $x <= 12; $x++) {
			$confirmItem = Utils::getButtonItem($this->getPlayer(), "confirm_purchase", "purchase-confirm");
			$confirmItem->getNamedTag()->setLong("purchase", $listing->getId());

			$this->getInventory()->setItem($x, $confirmItem);
		}
		for($x = 14; $x <= 17; $x++) {
			$cancelItem = Utils::getButtonItem($this->getPlayer(), "cancel_purchase", "purchase-cancel");
			$cancelItem->getNamedTag()->setByte("cancelled", 1);

			$this->getInventory()->setItem($x, $cancelItem);
		}
	}
	
	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
		$username = $player->getName();
		if ($itemClicked->getNamedTag()->hasTag("cancelled")) {
			$player->removeWindow($inventory);
			Locale::sendMessage($player, "cancelled-purchase");
			return false;
		}
		if (!$itemClicked->getNamedTag()->hasTag("purchase")) {
			return false;
		}
		$marketId = $itemClicked->getNamedTag()->getLong("purchase");
		$auction = DataHolder::getListingById($marketId);
		if ($auction == null) {
			Locale::sendMessage($player, "listing-gone");
			return false;
		}
		$item = $auction->getItem();

		if ($auction->getSellerUUID() == $player->getRawUniqueId()) {
			$player->removeWindow($inventory);
			Locale::sendMessage($player, "self-purchase");
			return false;
		}
		if (AuctionHouse::getInstance()->getEconomyProvider()->getMoney($player) < $auction->getPrice()) {
			$player->removeWindow($inventory);
			Locale::sendMessage($player, "cannot-afford");
			return false;
		}
		if (!$player->getInventory()->canAddItem($item)) {
			$player->removeWindow($inventory);
			Locale::sendMessage($player, "not-enough-space");
			return false;
		}
		$event = new ItemPurchasedEvent($player, $auction);
		$event->call();
		if($event->isCancelled()) {
			return false;
		}
		DataHolder::removeListing($auction);
		AuctionHouse::getInstance()->getEconomyProvider()->addMoney($auction->getSeller(), $auction->getPrice());
		AuctionHouse::getInstance()->getEconomyProvider()->subtractMoney($player, $auction->getPrice());
		$player->getInventory()->addItem($item);
		$player->removeWindow($inventory);
		$pl = AuctionHouse::getInstance()->getServer()->getPlayerByRawUUID($auction->getSellerUUID());
		$player->sendMessage(str_replace(["@player", "@item", "@price", "@amount"], [$username, $item->getName(), $auction->getPrice(true, Settings::formatPrice()), $item->getCount()], Locale::getMessage($player, "purchased-item", true)));

		if ($pl != null) {
			$pl->getLevel()->addSound(new FizzSound($pl), [$pl]);
			$pl->sendMessage(str_replace(["@player", "@item", "@price", "@amount"], [$username, $item->getName(), $auction->getPrice(true, Settings::formatPrice()), $item->getCount()], Locale::getMessage($player, "seller-message", true)));
		}
		(new AuctionEndEvent($auction, AuctionEndEvent::PURCHASED, $player))->call();
		return true;
	}

	public function show(Player $player): void {
		MenuManager::setViewingMenu($player, MenuManager::CONFIRM_PURCHASE_MENU);
		parent::show($player);
	}
}