<?php
namespace shock95x\auctionhouse\menu\category;

use ArrayObject;
use DateTime;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\auction\Listing;
use shock95x\auctionhouse\category\Category;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\menu\AHMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;

class CategoryMenu extends AHMenu {

	/** @var Listing[] */
	private $listings;
	/** @var Category  */
	private $category;

	public function __construct(Player $player, Category $category, int $page = 1) {
		$this->setName($category->getName());
		$this->setListings($category);
		$this->page = $page;
		$this->category = $category;

		parent::__construct($player, true, true);
	}

	public function setItems(int $page, int $max, int $total) : void {
		$stats = Utils::getButtonItem($this->getPlayer(), "stats", "category-stats");
		$stats->setCustomName(str_replace(["%category%"], [$this->category->getDisplayName()], $stats->getCustomName()));
		$stats->setLore(str_replace(["%page%", "%max%", "%total%", "%category%"], [$page, $max, $total, $this->category->getDisplayName()], $stats->getLore()));
		$this->getInventory()->setItem(49, $stats);
	}

	public function renderItems(): void {
		$total = count($this->getListings());
		$max = 0;
		for($i = 0; $i < $total; $i += 45) $max++;
		if($max == 0) $max = 1;

		$this->page < 1 ? $this->page = 1 : $this->page;
		$start = ($this->page - 1) * 45;
		$listings = array_slice($this->getListings(), $start, 45);

		if($this->page > $max) {
			$this->page = 1;
			$this->renderItems();
			return;
		}
		foreach($listings as $key => $auction) {
			$item = clone $auction->getItem();
			$endTime = (new DateTime())->diff((new DateTime())->setTimestamp($auction->getEndTime()));
			$tag = $item->hasCompoundTag() ? $item->getNamedTag() : new CompoundTag();
			$tag->setLong("marketId", $auction->getMarketId());

			$listedItem = Locale::getMessage($this->getPlayer(), "listed-item");
			$lore = str_replace(["%price%", "%seller%", "{D}", "{H}", "{M}"], [$auction->getPrice(true, Settings::formatPrice()), $auction->getSeller(), $endTime->days, $endTime->h,  $endTime->i], preg_filter('/^/', TextFormat::RESET, $listedItem));
			$lore = Settings::allowLore() ? array_merge($item->getLore(), $lore) : $lore;

			$item->setNamedTag($tag)->setCustomName(TextFormat::RESET . $item->getName())->setLore($lore);
			$this->getInventory()->setItem($key, $item);
		}
		for($i = count($listings); $i < 45; ++$i) {
			$this->getInventory()->setItem($i, Item::get(Item::AIR));
		}
		$this->setItems($this->page, $max, $total);
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
		if($itemClicked->getNamedTag()->hasTag("return")) {
			new CategoryListMenu($player, false);
			return true;
		}
		$this->checkPurchase($slot, $itemClicked);
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}

	private function setListings(Category $category): void {
		$listings = new ArrayObject(DataHolder::getListings());
		$this->listings = array_filter($listings->getArrayCopy(), [$category, "sort"]);
	}

	private function getListings(): array {
		return $this->listings;
	}

	public function show(Player $player): void {
		Utils::setViewingMenu($player, Utils::CATEGORY_MENU);
		parent::show($player);
	}
}