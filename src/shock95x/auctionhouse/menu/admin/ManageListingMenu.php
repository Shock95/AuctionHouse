<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu\admin;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\database\storage\DataStorage;
use shock95x\auctionhouse\event\AuctionEndEvent;
use shock95x\auctionhouse\menu\type\AHMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Utils;

class ManageListingMenu extends AHMenu {

	const INDEX_DUPLICATE = 39;
	const INDEX_STATUS = 40;
	const INDEX_DELETE = 41;

	public function __construct(Player $player, AHListing $listing) {
		$this->setName(Locale::get($player, "manage-listing-name"));
		$this->setListings([$listing]);
		parent::__construct($player);
	}

	public function renderButtons(): void {
		parent::renderButtons();
		$listing = $this->getListings()[0];

		$duplicateItem = ItemFactory::getInstance()->get(ItemIDs::EMERALD_BLOCK)->setCustomName(TextFormat::RESET . Locale::get($this->player, "duplicate-item"));
		$listingStatus = ItemFactory::getInstance()->get(ItemIDs::GOLD_BLOCK)->setCustomName(TextFormat::RESET .  Locale::get($this->player, $listing->isExpired() ? "listing-active" : "listing-expired"));
		$deleteItem = ItemFactory::getInstance()->get(ItemIDs::REDSTONE_BLOCK)->setCustomName(TextFormat::RESET . Locale::get($this->player, "delete-item"));

		$this->inventory->setItem(self::INDEX_DUPLICATE, $duplicateItem);
		$this->inventory->setItem(self::INDEX_STATUS, $listingStatus);
		$this->inventory->setItem(self::INDEX_DELETE, $deleteItem);
	}

	public function renderListings(): void {
		$listing = $this->getListings()[0];
		$this->inventory->setItem(22, $listing->getItem());
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
		$listing = $this->getListings()[0];
		switch ($slot) {
			case self::INDEX_DUPLICATE:
				$player->getInventory()->addItem($listing->getItem());
				break;
			case self::INDEX_STATUS:
				if($listing->isExpired()) {
					$listing->setExpired(false);
					$listing->setEndTime(Utils::getEndTime());
					$inventory->setItem($slot, $itemClicked->setCustomName(TextFormat::RESET . Locale::get($player, "listing-expired")));
				} else {
					$listing->setExpired();
					(new AuctionEndEvent($listing, AuctionEndEvent::ADMIN_REMOVED))->call();
					$inventory->setItem($slot, $itemClicked->setCustomName(TextFormat::RESET . Locale::get($player, "listing-active")));
				}
				break;
			case self::INDEX_DELETE:
				DataStorage::getInstance()->removeListing($listing);
				(new AuctionEndEvent($listing, AuctionEndEvent::ADMIN_PURGED))->call();
				self::open(new AdminMenu($player, false));
				break;
		}
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}

	public function onClose(Player $player): void {
		parent::onClose($player);
	}
}