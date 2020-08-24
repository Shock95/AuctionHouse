<?php
namespace shock95x\auctionhouse\menu\admin;

use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\auction\Listing;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\menu\AHMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;

class AdminMenu extends AHMenu {
	
	public function __construct(Player $player, bool $returnMain = true, int $page = 1) {
		$this->setName(Locale::getMessage($player, "admin-menu-name", true, false));
		$this->page = $page;
		parent::__construct($player, $returnMain, true);
	}
	
	public function setItems(int $page, int $max, int $expiredCount, int $listingCount) {
		$stats = Locale::getMessage($this->getPlayer(), "main-stats-admin", true, false);

		$this->getInventory()->setItem(49, Item::get($stats["id"])
			->setNamedTag(new CompoundTag("", [new IntTag("pagination", 2), new ListTag("ench", [], NBT::TAG_Compound)]))
			->setCustomName(TextFormat::RESET . $stats["name"])
			->setLore(str_replace(["%page%", "%max%", "%expired%", "%total%"], [$page, $max, $expiredCount, $listingCount], preg_filter('/^/', TextFormat::RESET, $stats["lore"]))));
	}
	
	public function renderItems() {
        $total = count(DataHolder::getListings(true));
        $max = 0;
        for($i = 0; $i < $total; $i += 45) $max++;
        if($max == 0) $max = 1;

        $this->page < 1 ? $this->page = 1 : $this->page;
        $start = ($this->page - 1) * 45;
        $listings = array_slice(DataHolder::getListings(true), $start, 45);

        if($this->page > $max) {
            $this->page = 1;
            $this->renderItems();
            return false;
        }
        foreach($listings as $key => $auction) {
			$item = clone $auction->getItem();
			$tag = $item->hasCompoundTag() ? $item->getNamedTag() : new CompoundTag();
			$tag->setLong("marketId", $auction->getMarketId());
			if($auction->isExpired()) {
				$status = Locale::getMessage($this->getPlayer(), "status-expired", true, false);
			} else {
				$status = Locale::getMessage($this->getPlayer(), "status-active", true, false);
			}
			$listedItem = Locale::getMessage($this->getPlayer(), "listed-item-admin", true, false);
			$item->setNamedTag($tag)->setCustomName(TextFormat::RESET . $item->getName())->setLore(str_replace(["%price%", "%seller%", "%status%"], [$auction->getPrice(true, Settings::formatPrice()), $auction->getSeller(), $status], preg_filter('/^/', TextFormat::RESET, $listedItem)));

            $this->getInventory()->setItem($key, $item);
		}
        for($i = count($listings); $i < 45; ++$i) {
            $this->getInventory()->setItem($i, Item::get(Item::AIR));
        }
		$this->setItems($this->page, $max, count(DataHolder::getExpiredListings()), $total);
	}

	public function handle(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool {
		if($action->getSlot() <= 44 && $itemClicked->getNamedTag()->hasTag("marketId")) {
			$id = $itemClicked->getNamedTag()->getLong("marketId");
			$listing = DataHolder::getListingById($id);

			if($listing == null) {
				Locale::getMessage($player, "listing-gone");
				return false;
			}
			$player->removeWindow($action->getInventory());
			AuctionHouse::getInstance()->getScheduler()->scheduleDelayedTask(new class($player, $listing) extends Task{
				private $player;
				private $listing;

				public function __construct(Player $player, Listing $listing) {
					$this->player = $player;
					$this->listing = $listing;
				}

				public function onRun(int $currentTick) {
					new ManageListingMenu($this->player, $this->listing);
				}
			}, 10);
		}
		return parent::handle($player, $itemClicked, $itemClickedWith, $action);
	}

	public function show(Player $player) {
		Utils::setViewingMenu($player, Utils::ADMIN_MENU);
		parent::show($player);
	}
}