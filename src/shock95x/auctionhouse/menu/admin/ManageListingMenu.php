<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu\admin;

use pocketmine\block\tile\Container;
use pocketmine\data\bedrock\item\SavedItemStackData;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\event\AuctionEndEvent;
use shock95x\auctionhouse\menu\type\AHMenu;
use shock95x\auctionhouse\utils\Locale;
use SOFe\AwaitGenerator\Await;

class ManageListingMenu extends AHMenu {

	const INDEX_COPY = 47;
	const INDEX_REMOVE = 49;
	const INDEX_DELETE = 51;

	public function __construct(Player $player, AHListing $listing) {
		$this->setName(Locale::get($player, "manage-listing-name"));
		$this->setListings([$listing]);
		parent::__construct($player);
	}

	public function renderButtons(): void {
		parent::renderButtons();
		$this->inventory->setItem(self::INDEX_COPY,
			VanillaBlocks::HOPPER()->asItem()->setCustomName(TextFormat::RESET . Locale::get($this->player, "copy-item")));
		$this->inventory->setItem(self::INDEX_REMOVE,
			VanillaItems::POISONOUS_POTATO()->setCustomName(TextFormat::RESET . Locale::get($this->player, "return-item")));
		$this->inventory->setItem(self::INDEX_DELETE,
			VanillaBlocks::BARRIER()->asItem()->setCustomName(TextFormat::RESET . Locale::get($this->player, "delete-item")));
	}

	public function renderListings(): void {
		$listingItem = $this->getListings()[0]->getItem();
		if($itemsTag = $listingItem->getNamedTag()?->getListTag(Container::TAG_ITEMS)) { // Shulker items
			foreach($itemsTag->getIterator() as $itemTag) {
				$this->inventory->setItem($itemTag->getByte(SavedItemStackData::TAG_SLOT), Item::nbtDeserialize($itemTag));
			}
			$this->inventory->setItem(31, $listingItem);
		} else {
			$this->inventory->setItem(22, $listingItem);
		}
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
		Await::f2c(function() use ($player, $slot) {
			$database = AuctionHouse::getInstance()->getDatabase();
			/** @var AHListing $listing */
			$listing = yield from Await::promise(fn($resolve) => $database->getListingById($this->getListings()[0]->getId(), $resolve));
			switch($slot) {
				case self::INDEX_COPY:
					$player->getInventory()->addItem($listing->getItem());
					break;
				case self::INDEX_REMOVE:
					if($listing == null || $listing->isExpired()) {
						self::open(new AdminMenu($player, false));
						break;
					}
					yield from $database->setExpiredAsync($listing->getId());
					(new AuctionEndEvent($listing, AuctionEndEvent::CANCELLED))->call();
					if($seller = Server::getInstance()->getPlayerByUUID($listing->getSellerUUID())) {
						$item = $listing->getItem();
						$seller->sendMessage(str_ireplace(["{ITEM}", "{AMOUNT}"], [$item->getName(), $item->getCount()], Locale::get($seller, "returned-item", true)));
					}
					self::open(new AdminMenu($player, false));
					break;
				case self::INDEX_DELETE:
					if($listing == null) {
						self::open(new AdminMenu($player, false));
						break;
					}
					yield from $database->removeListingAsync($listing->getId());
					(new AuctionEndEvent($listing, AuctionEndEvent::ADMIN_REMOVED))->call();
					self::open(new AdminMenu($player, false));
					break;
			}
		});
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}
}