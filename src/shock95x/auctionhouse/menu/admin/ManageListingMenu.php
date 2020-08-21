<?php


namespace shock95x\auctionhouse\menu\admin;

use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\auction\Listing;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\event\AuctionEndEvent;
use shock95x\auctionhouse\menu\AHMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Utils;

class ManageListingMenu extends AHMenu {

	/** @var Listing  */
	private $listing;

	public function __construct(Player $player, Listing $listing) {
		$this->setName(Locale::getMessage($player, "manage-listing-name", true, false));
		$this->listing = $listing;
		$this->newMenu = true;
		parent::__construct($player);
	}

	public function renderItems() {
		$item = $this->listing->getItem();
		$player = $this->getPlayer();
		$inventory = $this->getInventory();

		$inventory->setItem(22, $item);

		$duplicate = Locale::getMessage($player, "duplicate-item", true, false);
		$remove = Locale::getMessage($player, "remove-listing", true, false);
		$delete = Locale::getMessage($player, "delete-item", true, false);

		$duplicateItem = Item::get(Item::EMERALD_BLOCK)->setNamedTag(new CompoundTag("", [new IntTag("duplicate", 1)]))->setCustomName(TextFormat::RESET . $duplicate);
		$removeItem = Item::get(Item::GOLD_BLOCK)->setNamedTag(new CompoundTag("", [new IntTag("remove", 1)]))->setCustomName(TextFormat::RESET . $remove);
		$deleteItem = Item::get(Item::REDSTONE_BLOCK)->setNamedTag(new CompoundTag("", [new IntTag("delete", 1)]))->setCustomName(TextFormat::RESET . $delete);

		foreach([$duplicateItem, $removeItem, $deleteItem] as $controlItem) {
			$controlItem->getNamedTag()->setLong("marketId", $this->listing->getMarketId());
		}

		$inventory->setItem(39, $duplicateItem);
		$inventory->setItem(40, $removeItem);
		$inventory->setItem(41, $deleteItem);
		return true;
	}

	public function handle(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool {
		$listing = $this->listing;
		if($itemClicked->getNamedTag()->hasTag("duplicate")) {
			$player->getInventory()->addItem($listing->getItem());
			return true;
		}
		if($itemClicked->getNamedTag()->hasTag("remove")) {
			$listing->setExpired();
			(new AuctionEndEvent($listing, AuctionEndEvent::ADMIN_REMOVED))->call();
			return true;
		}
		if($itemClicked->getNamedTag()->hasTag("delete")) {
			DataHolder::removeAuction($listing);
			(new AuctionEndEvent($listing, AuctionEndEvent::ADMIN_PURGED))->call();
			new AdminMenu($player, false);
			return true;
		}
		return parent::handle($player, $itemClicked, $itemClickedWith, $action);
	}

	public function show(Player $player) {
		Utils::setViewingMenu($player, Utils::MANAGE_LISTING_MENU);
		parent::show($player);
	}
}