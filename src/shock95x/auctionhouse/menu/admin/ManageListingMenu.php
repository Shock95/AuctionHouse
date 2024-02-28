<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\menu\admin;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\database\storage\DataStorage;
use shock95x\auctionhouse\event\AuctionEndEvent;
use shock95x\auctionhouse\menu\type\AHMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Utils;
use function implode;
use function preg_filter;
use function str_ireplace;

class ManageListingMenu extends AHMenu{

	const INDEX_DUPLICATE = 38;
	const INDEX_STATUS = 40;
	const INDEX_DELETE = 42;

	public function __construct(Player $player, AHListing $listing){
		$this->setName(Locale::get($player, "manage-listing-name"));
		$this->setListings([$listing]);
		parent::__construct($player);
	}

	public function renderButtons() : void{
		parent::renderButtons();
		$listing = $this->getListings()[0];
		$duplicateItem = VanillaBlocks::EMERALD()->asItem()->setCustomName(TextFormat::RESET . Locale::get($this->player, "duplicate-item"));
		$status = Locale::get($this->player, $listing->isExpired() ? "status-expired" : "status-active");
		$listingStatus = VanillaBlocks::GOLD()->asItem()->setCustomName(str_ireplace("{STATUS}", $status, implode("\n", preg_filter('/^/', TextFormat::RESET, Locale::get($this->player, "listing-status")))));
		$deleteItem = VanillaBlocks::REDSTONE()->asItem()->setCustomName(TextFormat::RESET . Locale::get($this->player, "delete-item"));

		$this->inventory->setItem(self::INDEX_DUPLICATE, $duplicateItem);
		$this->inventory->setItem(self::INDEX_STATUS, $listingStatus);
		$this->inventory->setItem(self::INDEX_DELETE, $deleteItem);
	}

	public function renderListings() : void{
		$this->inventory->setItem(22, $this->getListings()[0]->getItem());
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot) : bool{
		$listing = $this->getListings()[0];
		switch($slot){
			case self::INDEX_DUPLICATE:
				$player->getInventory()->addItem($listing->getItem());
				break;
			case self::INDEX_STATUS:
				if($listing->isExpired()){
					$listing->setExpired(false);
					DataStorage::getInstance()->setExpired($listing, value: false);
					$listing->setEndTime(Utils::getEndTime());
				}else{
					$listing->setExpired();
					(new AuctionEndEvent($listing, AuctionEndEvent::ADMIN_REMOVED))->call();
				}
				$this->renderButtons();
				break;
			case self::INDEX_DELETE:
				DataStorage::getInstance()->removeListing($listing);
				(new AuctionEndEvent($listing, AuctionEndEvent::ADMIN_PURGED))->call();
				self::open(new AdminMenu($player, false));
				break;
		}
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}

    /**
     * Not need for this class, cause this class extends AHMenu, and AHMenu already have method
     * @deprecated
     * @param Player $player
     * @return void
     * @author comment by XackiGiFF
     */
	public function onClose(Player $player) : void{
		parent::onClose($player);
	}
}
