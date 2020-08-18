<?php
namespace shock95x\auctionhouse\menu;

use muqsit\invmenu\InvMenu;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\event\AuctionEndEvent;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;

class ConfirmPurchaseMenu extends AHMenu {

	/** @var Item  */
	private $clickedItem;

	public function __construct(Player $player, Item $item) {
		$this->setName(Locale::getMessage($player, "purchase-menu-name", true, false));
		$this->inventoryType = InvMenu::TYPE_CHEST;
		$this->clickedItem = $item;
		$this->newMenu = true;

		parent::__construct($player);
	}
	
	public function renderItems() {
		$id = $this->clickedItem->getNamedTag()->getLong("marketId");
		$this->getInventory()->setItem(13, $this->clickedItem);

		$confirm = Locale::getMessage($this->getPlayer(), "purchase-confirm", true, false);
		$cancel = Locale::getMessage($this->getPlayer(), "purchase-cancel", true, false);

		for($x = 9; $x <= 12; $x++) {
			$cancelItem = Item::get(Item::STAINED_GLASS_PANE, 14)->setNamedTag(new CompoundTag("", [new ByteTag("cancelled", 1)]))->setCustomName(TextFormat::RESET . $cancel["name"]);
			$this->getInventory()->setItem($x, $cancelItem);
		}
		for($x = 14; $x <= 17; $x++) {
			$confirmItem = Item::get(Item::STAINED_GLASS_PANE, 5)->setNamedTag(new CompoundTag("", [new LongTag("purchase", $id)]))->setCustomName(TextFormat::RESET . $confirm["name"]);
			$this->getInventory()->setItem($x, $confirmItem);
		}
		return true;
	}
	
	public function handle(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool {
		$username = $player->getName();
		$inventory = $action->getInventory();

		if ($itemClicked->getNamedTag()->hasTag("cancelled")) {
			$player->removeWindow($inventory);
			Locale::getMessage($player, "cancelled-purchase");
			return false;
		}
		if (!$itemClicked->getNamedTag()->hasTag("purchase")) {
			return false;
		}
		$marketId = $itemClicked->getNamedTag()->getLong("purchase");
		$auction = DataHolder::getListingById($marketId);
		if($auction == null) {
			Locale::getMessage($player, "listing-gone");
			return false;
		}
		$item = $auction->getItem();

		if($auction->getSeller(true) == $player->getRawUniqueId()) {
			$player->removeWindow($inventory);
			Locale::getMessage($player, "self-purchase");
			return false;
		}
		if (AuctionHouse::getInstance()->economyProvider->getMoney($player) < $auction->getPrice()) {
			$player->removeWindow($inventory);
			Locale::getMessage($player, "cannot-afford");
			return false;
		}
		if (!$player->getInventory()->canAddItem($item)) {
			$player->removeWindow($inventory);
			Locale::getMessage($player, "not-enough-space");
			return false;
		}
		DataHolder::removeAuction($auction);
		AuctionHouse::getInstance()->economyProvider->addMoney($auction->getSeller(), $auction->getPrice());
		AuctionHouse::getInstance()->economyProvider->subtractMoney($player, $auction->getPrice());
		$player->getInventory()->addItem($item);
		$player->removeWindow($inventory);
		$pl = AuctionHouse::getInstance()->getServer()->getPlayerByRawUUID($auction->getSeller(true));
		$player->sendMessage(str_replace(["@player", "@item", "@price", "@amount"], [$username, $item->getName(), $auction->getPrice(true, Settings::formatPrice()), $item->getCount()], Locale::getMessage($player, "purchased-item", true)));

		if($pl != null) {
			$pl->sendMessage(str_replace(["@player", "@item", "@price", "@amount"], [$username, $item->getName(), $auction->getPrice(true, Settings::formatPrice()), $item->getCount()], Locale::getMessage($player, "seller-message", true)));
		}
		(new AuctionEndEvent($auction, AuctionEndEvent::PURCHASED, $player))->call();
		return true;
	}
}