<?php
namespace shock95x\auctionhouse\menu;

use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Pagination;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;

class ExpiredMenu extends AHMenu {

	private $page;

	public function __construct(Player $player, int $page = 1) {
		$this->setName(Locale::getMessage($player, "expired-menu-name", true, false));
		$this->page = $page;
		$this->returnMain = true;
		$this->pagination = true;
		parent::__construct($player);
	}

	public function setItems(int $page, int $max, int $total) : void {
		$description = Locale::getMessage($this->getPlayer(), "expired-description", true, false);
		$stats = Locale::getMessage($this->getPlayer(), "expired-stats", true, false);
		$this->getInventory()->setItem(53, Item::get($description["id"])->setCustomName(TextFormat::RESET . $description["name"])->setLore(preg_filter('/^/', TextFormat::RESET, $description["lore"])));
		$this->getInventory()->setItem(49, Item::get($stats["id"])->setNamedTag(new CompoundTag("", [new ByteTag("getAll", 1)]))->setCustomName(TextFormat::RESET . $stats["name"])->setLore(str_replace(["%page%", "%max%", "%total%"], [$page, $max, $total], preg_filter('/^/', TextFormat::RESET, $stats["lore"]))));
	}

	public function renderItems() {
		parent::renderItems();
		$list = DataHolder::getListingsByPlayer($this->getPlayer(), true);
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
		for($j = $n2; $j <= $n3; ++$j) {
			++$n4;
			if(!isset($list[$j])) break;
			$auction = $list[$j];
			$item = clone $auction->getItem();
			$tag = $item->hasCompoundTag() ? $item->getNamedTag() : new CompoundTag();
			$tag->setLong("marketId", $auction->getMarketId());

			$expiredItem = Locale::getMessage($this->getPlayer(), "expired-item", true, false);

			$lore = str_replace(["%price%"], [$auction->getPrice(true)], preg_filter('/^/', TextFormat::RESET, $expiredItem));
			$lore = Settings::allowLore() ? array_merge($item->getLore(), $lore) : $lore;
			$item->setNamedTag($tag)->setCustomName(
				TextFormat::RESET . $item->getName())->setLore($lore);

			$this->getInventory()->addItem($item);
		}

		$total = count($list);
		$max = 0;
		for($i = 0; $i < $total; $i += 45) $max++;
		if($max == 0) $max = 1;

		$this->setItems($this->page, $max, $total);
	}

	public function handle(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action) : bool {
		$inventory = $action->getInventory();
		if($action->getSlot() <= 44 && $itemClicked->getNamedTag()->hasTag("marketId") && $itemClicked->getId() !== Item::AIR) {
			$marketId = $itemClicked->getNamedTag()->getLong("marketId");
			$auction = DataHolder::getListingById($marketId);
			if($auction == null || $auction->getSeller(true) != $player->getRawUniqueId()) {
				return false;
			}
			$item = $auction->getItem();
			if($player->getInventory()->canAddItem($item) && $auction->getSeller(true) == $player->getRawUniqueId()) {
				DataHolder::removeAuction($auction);
				$inventory->setItem($action->getSlot(), Item::get(Item::AIR));
				$player->getInventory()->addItem($item);
				$player->sendMessage(str_replace(["@item", "@amount"], [$item->getName(), $item->getCount()], Locale::getMessage($player, "returned-item", true)));
			}
		}
		return parent::handle($player, $itemClicked, $itemClickedWith, $action);
	}

	public function handlePagination(int $page) {
		switch($page) {
			case Pagination::BACK:
				new ExpiredMenu($this->getPlayer(), $this->page - 1);
				break;
			case Pagination::NEXT:
				new ExpiredMenu($this->getPlayer(), $this->page + 1);
				break;
			case Pagination::REFRESH:
				new ExpiredMenu($this->getPlayer(), $this->page);
				break;
		}
	}

	public function show(Player $player) {
		Utils::setViewingMenu($player, Utils::EXPIRED_MENU);
		parent::show($player);
	}
}