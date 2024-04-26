<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\Database;
use shock95x\auctionhouse\event\AuctionEndEvent;
use shock95x\auctionhouse\menu\type\PagingMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\SOFe\AwaitGenerator\Await;

class ExpiredMenu extends PagingMenu {

	const INDEX_RETURN_ALL = 49;

	public function __construct(Player $player) {
		$this->setName(Locale::get($player, "expired-menu-name"));
		parent::__construct($player);
	}

	protected function init(Database $database): void {
		Await::f2c(function () use ($database) {
			$this->setListings(yield from Await::promise(fn($resolve) => $database->getExpiredListingsByPlayer($resolve, $this->player->getUniqueId(), $this->getItemOffset())));
			$this->setTotalCount(yield from Await::promise(fn($resolve) => $database->getExpiredCountByPlayer($this->player->getUniqueId(), $resolve)));
			parent::init($database);
		});
	}

	public function renderButtons() : void {
		parent::renderButtons();
		$stats = Utils::getButtonItem($this->player, "return_all", "expired-stats", ["{PAGE}", "{MAX}", "{TOTAL}"], [$this->getPage(), $this->getPageCount(), $this->getTotalCount()]);
		$this->getInventory()->setItem(53, Utils::getButtonItem($this->player, "info", "main-description"));
		$this->getInventory()->setItem(49, $stats);
	}

	public function renderListings(): void {
        foreach($this->getListings() as $index => $listing) {
			$item = clone $listing->getItem();

			$expiredItem = Locale::get($this->player, "expired-item");

			$lore = str_ireplace(["{PRICE}"], [$listing->getPrice(true, Settings::formatPrice())], preg_filter('/^/', TextFormat::RESET, $expiredItem));
			$lore = Settings::allowLore() ? [...$item->getLore(), ...$lore] : $lore;
			$item->setLore($lore);

            $this->getInventory()->setItem($index, $item);
        }
       	parent::renderListings();
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
		Await::f2c(function () use ($player, $slot, $itemClicked, $inventory) {
			$database = AuctionHouse::getInstance()->getDatabase();
			if($slot <= 44 && isset($this->getListings()[$slot])) {
				$id = $this->getListings()[$slot]->getId();
				/** @var AHListing $listing */
				$listing = yield from Await::promise(fn($resolve) => $database->getListingById($id, $resolve));
				if($listing == null || !$listing->getSellerUUID()->equals($player->getUniqueId())) {
					return;
				}
				$item = $listing->getItem();
				if($player->getInventory()->canAddItem($item)) {
					$res = yield from $database->removeListingAsync($id);
					if(!$res) return;
					$inventory->setItem($slot, VanillaItems::AIR());
					$player->getInventory()->addItem($item);
					$player->sendMessage(str_ireplace(["{ITEM}", "{AMOUNT}"], [$item->getName(), $item->getCount()], Locale::get($player, "returned-item", true)));
				}
			}
			if($slot == self::INDEX_RETURN_ALL) {
				if(Utils::getEmptySlotCount($player->getInventory()) < $this->getTotalCount()) {
					Locale::sendMessage($player, "inventory-full");
					return;
				}
				foreach ($this->getListings() as $index => $expired) {
					if ($player->getInventory()->canAddItem($expired->getItem())) {
						$res = yield from $database->removeListingAsync($expired->getId());
						if(!$res) return;
						(new AuctionEndEvent($expired, AuctionEndEvent::CANCELLED))->call();
						$inventory->setItem($index, VanillaItems::AIR());
						$player->getInventory()->addItem($expired->getItem());
					}
				}
			}
			parent::handle($player, $itemClicked, $inventory, $slot);
		});
		return true;
	}
}