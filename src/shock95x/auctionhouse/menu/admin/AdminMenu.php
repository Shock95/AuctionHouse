<?php
namespace shock95x\auctionhouse\menu\admin;

use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\menu\AHMenu;
use shock95x\auctionhouse\task\MenuDelayTask;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Pagination;
use shock95x\auctionhouse\utils\Utils;

class AdminMenu extends AHMenu {
	
	private $page;
	
	public function __construct(Player $player, int $page = 1) {
		$this->setName(Locale::getMessage($player, "admin-menu-name", true, false));

		$this->page = $page;
		$this->returnMain = true;
		$this->pagination = true;
		parent::__construct($player);
	}
	
	public function setItems(int $page, int $max, int $expiredCount, int $listingCount) {
		$stats = Locale::getMessage($this->getPlayer(), "main-stats-admin", true, false);

		$this->getInventory()->setItem(49, Item::get($stats["id"])
			->setNamedTag(new CompoundTag("", [new IntTag("pagination", 2), new ListTag("ench", [], NBT::TAG_Compound)]))
			->setCustomName(TextFormat::RESET . $stats["name"])
			->setLore(str_replace(["%page%", "%max%", "%expired%", "%total%"], [$page, $max, $expiredCount, $listingCount], preg_filter('/^/', TextFormat::RESET, $stats["lore"]))));
	}
	
	public function renderItems() {
		parent::renderItems();
		$list = DataHolder::getListings(true);
		if($this->page < 1) {
			$size = count($list);
			$this->page = $size / 45;
			if ($size % 45 > 0) {
				++$this->page;
			}
		}
		$size2 = count($list);
		$n2 = ($this->page - 1) * 45;
		$n3 = ($n2 + 44 >= $size2) ? ($size2 - 1) : ($this->page * 45 - 1);
		if ($n3 - $n2 + 1 < 1 && $this->page != 1) {
			$this->page = 1;
			$this->renderItems();
			return false;
		}
		$n4 = 0;
		
		$inventory = $this->getInventory();
		$player = $this->getPlayer();

		for($j = $n2; $j <= $n3; ++$j) {
			++$n4;
			if(!isset($list[$j])) break;
			$auction = $list[$j];
			$item = clone $auction->getItem();
			$tag = $item->hasCompoundTag() ? $item->getNamedTag() : new CompoundTag();
			$tag->setLong("marketId", $auction->getMarketId());
			if($auction->isExpired()) {
				$status = Locale::getMessage($player, "status-expired", true, false);
			} else {
				$status = Locale::getMessage($player, "status-active", true, false);
			}
			$listedItem = Locale::getMessage($player, "listed-item-admin", true, false);
			$item->setNamedTag($tag)->setCustomName(TextFormat::RESET . $item->getName())->setLore(str_replace(["%price%", "%seller%", "%status%"], [$auction->getPrice(true), $auction->getSeller(), $status], preg_filter('/^/', TextFormat::RESET, $listedItem)));

			$inventory->addItem($item);
		}

		$total = count($list);
		$max = 0;
		for($i = 0; $i < $total; $i += 45) $max++;
		if($max == 0) $max = 1;
		
		$this->setItems($this->page, $max, $total, count((array) DataHolder::getListings(true)));
	}

	public function handle(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool {
		if($action->getSlot() <= 44 && $itemClicked->getNamedTag()->hasTag("marketId")) {
			$id = $itemClicked->getNamedTag()->getLong("marketId");
			$listing = DataHolder::getListingById($id);

			if($listing == null) {
				Locale::getMessage($player, "listing-gone");
				return false;
			}
			AuctionHouse::getInstance()->getScheduler()->scheduleDelayedTask(new MenuDelayTask($player, new ManageListingMenu($player, $listing)), 10);
		}
		return parent::handle($player, $itemClicked, $itemClickedWith, $action);
	}

	public function handlePagination(int $page) {
		switch($page) {
			case Pagination::BACK:
				new AdminMenu($this->getPlayer(), $this->page - 1);
				break;
			case Pagination::NEXT:
				new AdminMenu($this->getPlayer(), $this->page + 1);
				break;
			case Pagination::REFRESH:
				new AdminMenu($this->getPlayer(), $this->page);
				break;
		}
	}

	public function show(Player $player) {
		Utils::setViewingMenu($player, Utils::ADMIN_MENU);
		parent::show($player);
	}
}