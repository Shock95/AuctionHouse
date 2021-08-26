<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\manager\MenuManager;
use shock95x\auctionhouse\menu\type\PagingMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;

class ExpiredMenu extends PagingMenu {

	public function __construct(Player $player, bool $returnMain = true, int $page = 1) {
		$this->setName(Locale::getMessage($player, "expired-menu-name"));
		parent::__construct($player, $page, $returnMain);
	}

	public function setItems(int $page, int $max, int $total) : void {
		$info = Utils::getButtonItem($this->getPlayer(), "info", "main-description");

		$stats = Utils::getButtonItem($this->getPlayer(), "return_all", "expired-stats", ["%page%", "%max%", "%total%"], [$page, $max, $total]);
		$stats->getNamedTag()->setByte("return_all", 1);

		$this->getInventory()->setItem(53, $info);
		$this->getInventory()->setItem(49, $stats);
	}

	public function renderItems(): void {
		parent::renderItems();
        $total = count(DataHolder::getListingsByPlayer($this->getPlayer(), true));
        $max = 0;
        for($i = 0; $i < $total; $i += 45) $max++;
        if($max == 0) $max = 1;

		$this->page > 1 ?: $this->page = 1;
        $start = ($this->page - 1) * 45;
        $listings = array_slice(DataHolder::getListingsByPlayer($this->getPlayer(), true), $start, 45);

		if($this->checkLastPage($max)) return;

        foreach($listings as $key => $auction) {
			$item = clone $auction->getItem();
			$tag = $item->hasCompoundTag() ? $item->getNamedTag() : new CompoundTag();
			$tag->setLong("marketId", $auction->getId());

			$expiredItem = Locale::getMessage($this->getPlayer(), "expired-item");

			$lore = str_replace(["%price%"], [$auction->getPrice(true, Settings::formatPrice())], preg_filter('/^/', TextFormat::RESET, $expiredItem));
			$lore = Settings::allowLore() ? array_merge($item->getLore(), $lore) : $lore;
			$item->setNamedTag($tag)->setCustomName(
				TextFormat::RESET . $item->getName())->setLore($lore);

            $this->getInventory()->setItem($key, $item);
        }
        for($i = count($listings); $i < 45; ++$i) {
            $this->getInventory()->setItem($i, Item::get(Item::AIR));
        }
		$this->setItems($this->page, $max, $total);
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
		if($slot <= 44 && $itemClicked->getId() !== Item::AIR) {
			if($itemClicked->getNamedTag()->hasTag("marketId")) {
				$marketId = $itemClicked->getNamedTag()->getLong("marketId");
				$auction = DataHolder::getListingById($marketId);
				if($auction == null || $auction->getSellerUUID() != $player->getRawUniqueId()) {
					return false;
				}
				$item = $auction->getItem();
				if($player->getInventory()->canAddItem($item)) {
					DataHolder::removeListing($auction);
					$inventory->remove($itemClicked);
					$player->getInventory()->addItem($item);
					$player->sendMessage(str_replace(["@item", "@amount"], [$item->getName(), $item->getCount()], Locale::getMessage($player, "returned-item", true)));
				}
			}
		}
		$listingCount = count(DataHolder::getListingsByPlayer($player, true));
		if($itemClicked->getNamedTag()->hasTag("return_all")) {
			if(Utils::getEmptySlotCount($player->getInventory()) < $listingCount) {
				Locale::sendMessage($player, "inventory-full");
				return false;
			}
			foreach(DataHolder::getListingsByPlayer($player, true) as $listing) {
				if ($player->getInventory()->canAddItem($listing->getItem())) {
					DataHolder::removeListing($listing);
					$player->getInventory()->addItem($listing->getItem());
				}
			}
			for($i = 0; $i < 45; $i++) {
				$inventory->setItem($i, Item::get(Item::AIR));
			}
		}
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}

	public function show(Player $player): void {
		MenuManager::setViewingMenu($player, MenuManager::EXPIRED_MENU);
		parent::show($player);
	}
}