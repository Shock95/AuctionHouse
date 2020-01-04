<?php
namespace AuctionHouse\menu;

use AuctionHouse\AuctionHouse;
use AuctionHouse\database\Database;
use AuctionHouse\database\DataHolder;
use AuctionHouse\economy\EconomyProvider;
use AuctionHouse\event\AuctionEndEvent;
use AuctionHouse\task\MenuDelayTask;
use AuctionHouse\utils\Pagination;
use muqsit\invmenu\InvMenu;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class MenuHandler {

	private $plugin;

	const AUCTION_MENU = 0;
	const LISTINGS_MENU = 1;
	const EXPIRED_MENU = 2;

	/** @var array */
	private static $menuOpen;

	public function __construct(AuctionHouse $plugin) {
		$this->plugin = $plugin;
	}

	public static function setViewingMenu(Player $player, int $menu) {
		self::$menuOpen[$player->getRawUniqueId()] = $menu;
	}

	public static function getViewingMenu(Player $player) {
		if(isset(self::$menuOpen[$player->getRawUniqueId()])) {
			return self::$menuOpen[$player->getRawUniqueId()];
		}
		return -1;
	}

	public function handleMainPage(Player $player, Item $item) {
		if($item->getNamedTag()->hasTag("return")) {
			$this->getPlugin()->sendAHMenu($player);
		}
	}

	public function handleListings(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action) {
		$inventory = $action->getInventory();
		if($action->getSlot() <= 44) {
			$auction = DataHolder::getListingById($itemClicked->getNamedTag()->getLong("marketId"));
			if($auction == null) {
				return;
			}
			$auction->setExpired();
			(new AuctionEndEvent($auction, AuctionEndEvent::CANCELLED, $player))->call();
			$inventory->removeItem($itemClicked);
		}
		$this->handleMainPage($player, $itemClicked);
		if($itemClicked->getNamedTag()->hasTag("pagination")) {
			$page = $itemClicked->getNamedTag()->getByte("pagination");
			$this->handlePagination($player, $page);
			return;
		}
	}

	public function handleExpired(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action) {
		$inventory = $action->getInventory();
		if($action->getSlot() <= 44 && $itemClicked->getNamedTag()->hasTag("marketId") && $itemClicked->getId() !== Item::AIR) {
			$marketId = $itemClicked->getNamedTag()->getLong("marketId");
			$auction = DataHolder::getListingById($marketId);
			if($auction == null || $auction->getSeller(true) != $player->getRawUniqueId()) {
				return;
			}
			$item = $auction->getItem();
			if($player->getInventory()->canAddItem($item) && $auction->getSeller(true) == $player->getRawUniqueId()) {
				DataHolder::removeAuction($auction);
				$inventory->setItem($action->getSlot(), Item::get(Item::AIR));
				$player->getInventory()->addItem($item);
			}
		}
		$this->handleMainPage($player, $itemClicked);
		if($itemClicked->getNamedTag()->hasTag("pagination")) {
			$page = $itemClicked->getNamedTag()->getByte("pagination");
			$this->handlePagination($player, $page);
			return;
		}
	}

	public function handleItemSelection(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action) : bool {
		$inventory = $action->getInventory();
		if($itemClicked->getNamedTag()->hasTag("refresh")) {
			$this->getPlugin()->sendAHMenu($player, $inventory, 2);
			return false;
		}
		if($itemClicked->getNamedTag()->hasTag("listings")) {
			$this->getPlugin()->sendListings($player, null);
			return false;
		}
		if($itemClicked->getNamedTag()->hasTag("expired")) {
			$this->getPlugin()->sendExpired($player, null);
			return false;
		}
		if($action->getSlot() <= 44) {
			$player->removeWindow($inventory);
			$id = $itemClicked->getNamedTag()->getLong("marketId");

			$menu = InvMenu::create(InvMenu::TYPE_CHEST)
				->readonly()
				->sessionize()
				->setName($this->getPlugin()->getMessage($player, "purchase-menu-name", true, false))
				->setListener([$this, "handlePurchase"]);

			$newInv = $menu->getInventory($player);

			$item = clone $itemClicked;
			$newInv->setItem(13, $item);

			$confirm = $this->getPlugin()->getMessage($player, "purchase-confirm", true, false);
			$cancel = $this->getPlugin()->getMessage($player, "purchase-cancel", true, false);
			for($x = 9; $x <= 12; $x++) {
				$cancelItem = Item::get(Item::STAINED_GLASS_PANE, 14)->setCompoundTag(new CompoundTag("", [new ByteTag("cancelled", 1)]))->setCustomName(TextFormat::RESET . $cancel["name"]);
				$newInv->setItem($x, $cancelItem);
			}
			for($x = 14; $x <= 17; $x++) {
				$confirmItem = Item::get(Item::STAINED_GLASS_PANE, 5)->setCompoundTag(new CompoundTag("", [new LongTag("marketId", $id)]))->setCustomName(TextFormat::RESET . $confirm["name"]);
				$newInv->setItem($x, $confirmItem);
			}
			$this->getPlugin()->getScheduler()->scheduleDelayedTask(new MenuDelayTask($player, $menu), 10);
			return true;
		}
		if($itemClicked->getNamedTag()->hasTag("pagination")) {
			$page = $itemClicked->getNamedTag()->getByte("pagination");
			$this->handlePagination($player, $page);
			return true;
		}
		return true;
	}

	public function handlePagination(Player $player, int $pagination) { // todo: handle this better
		switch(self::$menuOpen[$player->getRawUniqueId()]) {
			case self::AUCTION_MENU:
				switch($pagination) {
					case Pagination::BACK:
						$this->plugin->sendAHMenu($player, null, Pagination::getPage($player) - 1);
						break;
					case Pagination::NEXT:
						$this->plugin->sendAHMenu($player, null, Pagination::getPage($player) + 1);
						break;
					case Pagination::REFRESH:
						$this->plugin->sendAHMenu($player, null, Pagination::getPage($player));
						break;
				}
				break;
			case self::LISTINGS_MENU:
				switch($pagination) {
					case Pagination::BACK:
						$this->plugin->sendListings($player, null, Pagination::getPage($player) - 1);
						break;
					case Pagination::NEXT:
						$this->plugin->sendListings($player, null, Pagination::getPage($player) + 1);
						break;
					case Pagination::REFRESH:
						$this->plugin->sendListings($player, null, Pagination::getPage($player));
						break;
				}
				break;
			case self::EXPIRED_MENU:
				switch($pagination) {
					case Pagination::BACK:
						$this->plugin->sendExpired($player, null, Pagination::getPage($player) - 1);
						break;
					case Pagination::NEXT:
						$this->plugin->sendExpired($player, null, Pagination::getPage($player) + 1);
						break;
					case Pagination::REFRESH:
						$this->plugin->sendExpired($player, null, Pagination::getPage($player));
						break;
				}
				break;
		}
	}

	public function handlePurchase(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action) : bool {
		$username = $player->getName();
		$inventory = $action->getInventory();
		if ($itemClicked->getNamedTag()->hasTag("cancelled")) {
			$player->removeWindow($inventory);
			$this->getPlugin()->getMessage($player, "cancelled-purchase");
			return false;
		}
		if (!$itemClicked->getNamedTag()->hasTag("marketId")) {
			return false;
		}
		$marketId = $itemClicked->getNamedTag()->getLong("marketId");
		$auction = DataHolder::getListingById($marketId);
		if ($auction == null) {
			return false;
		}
		$item = $auction->getItem();

		if($auction->getSeller(true) == $player->getRawUniqueId()) {
			$player->removeWindow($inventory);
			$this->plugin->getMessage($player, "self-purchase");
			return false;
		}
		if ($this->getPlugin()->economyProvider->getMoney($player) < $auction->getPrice()) {
			$player->removeWindow($inventory);
			$this->plugin->getMessage($player, "cannot-afford");
			return false;
		}
		if (!$player->getInventory()->canAddItem($item)) {
			$player->removeWindow($inventory);
			$this->plugin->getMessage($player, "not-enough-space");
			return false;
		}
		DataHolder::removeAuction($auction);
		$this->getPlugin()->economyProvider->addMoney($auction->getSeller(), $auction->getPrice());
		$this->getPlugin()->economyProvider->subtractMoney($player, $auction->getPrice());
		$player->getInventory()->addItem($item);
		$player->removeWindow($inventory);
		$pl = $this->getPlugin()->getServer()->getPlayerByRawUUID($auction->getSeller(true));
		$player->sendMessage(str_replace(["@player", "@item", "@price", "@amount"], [$username, $item->getName(), $auction->getPrice(true), $item->getCount()], $this->plugin->getMessage($player, "purchased-item", true)));

		if($pl->isOnline()) {
			$pl->sendMessage($player->sendMessage(str_replace(["@player", "@item", "@price", "@amount"], [$username, $item->getName(), $auction->getPrice(true), $item->getCount()], $this->plugin->getMessage($player, "seller-message", true))));
		}
		(new AuctionEndEvent($auction, AuctionEndEvent::PURCHASED, $player))->call();
		return true;
	}
	
	public function getPlugin() : AuctionHouse {
		return $this->plugin;
	}

	public function getEconomy() : EconomyProvider {
		return $this->plugin->economyProvider;
	}
	
	public function getDatabase () : Database {
		return $this->plugin->getDatabase();
	}
}