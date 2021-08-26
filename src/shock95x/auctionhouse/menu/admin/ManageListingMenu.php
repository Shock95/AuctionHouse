<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu\admin;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\event\AuctionEndEvent;
use shock95x\auctionhouse\manager\MenuManager;
use shock95x\auctionhouse\menu\type\AHMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Utils;

class ManageListingMenu extends AHMenu {

	private AHListing $listing;

	protected bool $newMenu = true;

	public function __construct(Player $player, AHListing $listing) {
		$this->setName(Locale::getMessage($player, "manage-listing-name"));
		$this->listing = $listing;
		parent::__construct($player);
	}

	public function renderItems(): void {
		$item = $this->listing->getItem();
		$player = $this->getPlayer();
		$inventory = $this->getInventory();

		$inventory->setItem(22, $item);

		$duplicate = Locale::getMessage($player, "duplicate-item");
		$remove = Locale::getMessage($player, "listing-expired");

		$add = Locale::getMessage($player, "listing-active");
		$delete = Locale::getMessage($player, "delete-item");

		$duplicateItem = Item::get(Item::EMERALD_BLOCK)->setNamedTag(new CompoundTag("", [new ByteTag("duplicate", 1)]))->setCustomName(TextFormat::RESET . $duplicate);
		$itemStatus = Item::get(Item::GOLD_BLOCK)->setNamedTag(new CompoundTag("", [new ByteTag("status", 1)]));
		$deleteItem = Item::get(Item::REDSTONE_BLOCK)->setNamedTag(new CompoundTag("", [new ByteTag("delete", 1)]))->setCustomName(TextFormat::RESET . $delete);

		foreach([$duplicateItem, $itemStatus, $deleteItem] as $controlItem) {
			$controlItem->getNamedTag()->setLong("marketId", $this->listing->getId());
		}

		$this->listing->isExpired() ? $itemStatus->setCustomName(TextFormat::RESET . $add) : $itemStatus->setCustomName(TextFormat::RESET . $remove);

		$inventory->setItem(39, $duplicateItem);
		$inventory->setItem(40, $itemStatus);
		$inventory->setItem(41, $deleteItem);
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
		$listing = $this->listing;
		if($itemClicked->getNamedTag()->hasTag("duplicate")) {
			$player->getInventory()->addItem($listing->getItem());
			return true;
		}
		if($itemClicked->getNamedTag()->hasTag("status")) {
			if($listing->isExpired()) {
				$listing->setExpired(false);
				$listing->setEndTime(Utils::getEndTime());
				$inventory->setItem($slot, $itemClicked->setCustomName(TextFormat::RESET . Locale::getMessage($player, "listing-expired")));
			} else if (!$listing->isExpired()) {
				$listing->setExpired();
				(new AuctionEndEvent($listing, AuctionEndEvent::ADMIN_REMOVED))->call();
				$inventory->setItem($slot, $itemClicked->setCustomName(TextFormat::RESET . Locale::getMessage($player, "listing-active")));
			}
		}
		if($itemClicked->getNamedTag()->hasTag("delete")) {
			DataHolder::removeListing($listing);
			(new AuctionEndEvent($listing, AuctionEndEvent::ADMIN_PURGED))->call();
			new AdminMenu($player, false);
			return true;
		}
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}

	public function onClose(Player $player): void {
		parent::onClose($player);
		new AdminMenu($player, false);
	}

	public function show(Player $player): void {
		MenuManager::setViewingMenu($player, MenuManager::MANAGE_LISTING_MENU);
		parent::show($player);
	}
}