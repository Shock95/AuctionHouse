<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu\category;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\category\Category;
use shock95x\auctionhouse\category\CategoryManager;
use shock95x\auctionhouse\menu\AHMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Utils;

class CategoryListMenu extends AHMenu {

	public function __construct(Player $player, bool $returnMain = true, int $page = 1) {
		$this->setName(Locale::getMessage($player, "category-menu-name"));
		$this->page = $page;
		parent::__construct($player, $returnMain, true);
	}

	public function setItems(int $page, int $max, int $total) : void {
		$stats = Utils::getButtonItem($this->getPlayer(), "stats", "category-list-stats");
		$stats->setLore(str_replace(["%page%", "%max%", "%total%"], [$page, $max, $total], $stats->getLore()));
		$this->getInventory()->setItem(49, $stats);
	}

	public function renderItems(): void {
		$total = count(CategoryManager::getCategories());
		$max = 0;
		for($i = 0; $i < $total; $i += 45) $max++;
		if($max == 0) $max = 1;

		$this->page < 1 ? $this->page = 1 : $this->page;
		$start = ($this->page - 1) * 45;
		$categories = array_slice(CategoryManager::getCategories(), $start, 45);

		if($this->page > $max) {
			$this->page = 1;
			$this->renderItems();
			return;
		}
		foreach($categories as $key => $category) {
			if(!$category instanceof Category) {
				return;
			}
			/** @var Category $category */
			$item = $category->getMenuItem();
			$tag = $item->hasCompoundTag() ? $item->getNamedTag() : new CompoundTag();
			$tag->setInt("category", $key);

			$item->setLore([TextFormat::RESET . TextFormat::GRAY . "Click to open category"]);
			$this->getInventory()->setItem($key, $item);
		}
		$this->setItems($this->page, $max, $total);
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
		if($slot <= 44 && $itemClicked->getNamedTag()->hasTag("category") && $itemClicked->getId() !== Item::AIR) {
			/** @var Category $category */
			$category = CategoryManager::getCategories()[$itemClicked->getNamedTag()->getInt("category")];
			new CategoryMenu($player, $category);
		}
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}

	public function show(Player $player): void {
		Utils::setViewingMenu($player, Utils::CATEGORY_LIST_MENU);
		parent::show($player);
	}
}