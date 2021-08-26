<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu\admin;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\manager\MenuManager;
use shock95x\auctionhouse\menu\type\PagingMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;

class AdminMenu extends PagingMenu {
	
	public function __construct(Player $player, bool $returnMain = true, int $page = 1) {
		$this->setName(Locale::getMessage($player, "admin-menu-name"));
		parent::__construct($player, $page, true);
	}
	
	public function renderButtons(int $page, int $max, int $expiredCount, int $listingCount) {
		$stats = Utils::getButtonItem($this->getPlayer(), "stats", "main-stats-admin", ["%page%", "%max%", "%expired%", "%total%"], [$page, $max, $expiredCount, $listingCount]);
		$stats->getNamedTag()->setByte("pagination", 2);
		$stats->setNamedTagEntry(new ListTag("ench"));

		$this->getInventory()->setItem(49, $stats);
	}
	
	public function renderItems(): void {
		parent::renderItems();
        $total = count(DataHolder::getListings(true));
        $max = 0;
        for($i = 0; $i < $total; $i += 45) $max++;
        if($max == 0) $max = 1;

		$this->page > 1 ?: $this->page = 1;
        $start = ($this->page - 1) * 45;
        $listings = array_slice(DataHolder::getListings(true), $start, 45);

        if($this->checkLastPage($max)) return;

        foreach($listings as $key => $auction) {
			$item = clone $auction->getItem();
			$tag = $item->hasCompoundTag() ? $item->getNamedTag() : new CompoundTag();
			$tag->setLong("marketId", $auction->getId());
			if($auction->isExpired()) {
				$status = Locale::getMessage($this->getPlayer(), "status-expired");
			} else {
				$status = Locale::getMessage($this->getPlayer(), "status-active");
			}
			$listedItem = Locale::getMessage($this->getPlayer(), "listed-item-admin");
			$item->setNamedTag($tag)->setCustomName(TextFormat::RESET . $item->getName())->setLore(str_replace(["%price%", "%seller%", "%status%"], [$auction->getPrice(true, Settings::formatPrice()), $auction->getSeller(), $status], preg_filter('/^/', TextFormat::RESET, $listedItem)));

            $this->getInventory()->setItem($key, $item);
		}
        for($i = count($listings); $i < 45; ++$i) {
            $this->getInventory()->setItem($i, Item::get(Item::AIR));
        }
		$this->renderButtons($this->page, $max, count(DataHolder::getExpiredListings()), $total);
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
		if($slot <= 44 && $itemClicked->getNamedTag()->hasTag("marketId")) {
			$id = $itemClicked->getNamedTag()->getLong("marketId");
			$listing = DataHolder::getListingById($id);

			if($listing == null) {
				Locale::getMessage($player, "listing-gone");
				return false;
			}
			$player->removeWindow($inventory);
			new ManageListingMenu($player, $listing);
		}
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}

	public function show(Player $player): void {
		MenuManager::setViewingMenu($player, MenuManager::ADMIN_MENU);
		parent::show($player);
	}
}