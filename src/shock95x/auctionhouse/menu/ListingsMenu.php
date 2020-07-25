<?php


namespace shock95x\auctionhouse\menu;

use DateTime;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\event\AuctionEndEvent;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Pagination;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;

class ListingsMenu extends AHMenu {

	private $page;

	public function __construct(Player $player, int $page = 1) {
		$this->setName(Locale::getMessage($player, "listings-menu-name", true, false));
		$this->page = $page;
		$this->returnMain = true;
		$this->pagination = true;
		parent::__construct($player);
	}

	public function setItems(int $page, int $max, int $total) : void {
		$description = Locale::getMessage($this->getPlayer(), "listings-description", true, false);
		$stats = Locale::getMessage($this->getPlayer(), "listings-stats", true, false);
		$this->getInventory()->setItem(53, Item::get($description["id"])->setCustomName(TextFormat::RESET . $description["name"])->setLore(preg_filter('/^/', TextFormat::RESET, $description["lore"])));
		$this->getInventory()->setItem(49, Item::get($stats["id"])->setCustomName(TextFormat::RESET . $stats["name"])->setLore(str_replace(["%page%", "%max%", "%total%"], [$page, $max, $total], preg_filter('/^/', TextFormat::RESET, $stats["lore"]))));
	}

	public function renderItems() {
		parent::renderItems();
		$list = DataHolder::getListingsByPlayer($this->getPlayer());
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

			$endTime = (new DateTime())->diff((new DateTime())->setTimestamp($auction->getEndTime()));
			$listedItem = Locale::getMessage($this->getPlayer(), "your-listed-item", true, false);

			$lore = str_replace(["%price%", "%time%"], [$auction->getPrice(true), ($endTime->days * 24 + $endTime->h) . ":" . $endTime->i], preg_filter('/^/', TextFormat::RESET, $listedItem));
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
			$auction = DataHolder::getListingById($itemClicked->getNamedTag()->getLong("marketId"));
			if($auction == null) {
				return false;
			}
			$auction->setExpired();
			(new AuctionEndEvent($auction, AuctionEndEvent::CANCELLED, $player))->call();
			$inventory->removeItem($itemClicked);
		}
		return parent::handle($player, $itemClicked, $itemClickedWith, $action);
	}

	public function handlePagination(int $page) {
		switch($page) {
			case Pagination::BACK:
				new ListingsMenu($this->getPlayer(), $this->page - 1);
				break;
			case Pagination::NEXT:
				new ListingsMenu($this->getPlayer(), $this->page + 1);
				break;
			case Pagination::REFRESH:
				new ListingsMenu($this->getPlayer(), $this->page);
				break;
		}
	}

	public function show(Player $player) {
		Utils::setViewingMenu($player, Utils::LISTINGS_MENU);
		parent::show($player);
	}
}