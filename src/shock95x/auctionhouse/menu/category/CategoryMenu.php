<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu\category;

use ArrayObject;
use DateTime;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\category\Category;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\manager\MenuManager;
use shock95x\auctionhouse\menu\type\PagingMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;

class CategoryMenu extends PagingMenu {

	/** @var AHListing[] */
	private $listings;
	/** @var Category  */
	private $category;

	public function __construct(Player $player, Category $category, int $page = 1) {
		$this->setName($category->getDisplayName());
		$this->setListings($category);
		$this->category = $category;
		parent::__construct($player, $page, true);
	}

	public function renderButtons(int $page, int $max, int $total) : void {
		$stats = Utils::getButtonItem($this->getPlayer(), "stats", "category-stats", ["%page%", "%max%", "%total%", "%category%"], [$page, $max, $total, $this->category->getDisplayName()]);
		$this->getInventory()->setItem(49, $stats);
	}

	public function renderItems(): void {
		$total = count($this->getListings());
		$max = 0;
		for($i = 0; $i < $total; $i += 45) $max++;
		if($max == 0) $max = 1;

		$this->page > 1 ?: $this->page = 1;
		$start = ($this->page - 1) * 45;
		$listings = array_slice($this->getListings(), $start, 45);

		if($this->checkLastPage($max)) return;

		foreach($listings as $key => $auction) {
			$item = clone $auction->getItem();
			$endTime = (new DateTime())->diff((new DateTime())->setTimestamp($auction->getEndTime()));
			$tag = $item->hasCompoundTag() ? $item->getNamedTag() : new CompoundTag();
			$tag->setLong("marketId", $auction->getId());

			$listedItem = Locale::getMessage($this->getPlayer(), "listed-item");
			$lore = str_replace(["%price%", "%seller%", "{D}", "{H}", "{M}"], [$auction->getPrice(true, Settings::formatPrice()), $auction->getSeller(), $endTime->days, $endTime->h,  $endTime->i], preg_filter('/^/', TextFormat::RESET, $listedItem));
			$lore = Settings::allowLore() ? array_merge($item->getLore(), $lore) : $lore;

			$item->setNamedTag($tag)->setCustomName(TextFormat::RESET . $item->getName())->setLore($lore);
			$this->getInventory()->setItem($key, $item);
		}
		for($i = count($listings); $i < 45; ++$i) {
			$this->getInventory()->setItem($i, Item::get(Item::AIR));
		}
		$this->renderButtons($this->page, $max, $total);
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
		if($itemClicked->getNamedTag()->hasTag("return")) {
			new CategoryListMenu($player);
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
		MenuManager::setViewingMenu($player, MenuManager::CATEGORY_MENU);
		parent::show($player);
	}
}