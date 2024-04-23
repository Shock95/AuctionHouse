<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu\category;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\category\Category;
use shock95x\auctionhouse\category\ICategory;
use shock95x\auctionhouse\database\Database;
use shock95x\auctionhouse\menu\type\PagingMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Utils;

class CategoryListMenu extends PagingMenu {

	public function __construct(Player $player) {
		$this->setName(Locale::get($player, "category-menu-name"));
		parent::__construct($player);
	}

	protected function init(Database $database): void {
		$this->setTotalCount(count(Category::getAll()));
		$this->renderButtons();
	}

	public function renderButtons() : void {
		parent::renderButtons();
		$categories = array_slice(array_values(Category::getAll()), $this->getItemOffset(), 45);
		/** @var ICategory $category */
		foreach($categories as $index => $category) {
			$item = $category->getMenuItem();
			$item->setLore([TextFormat::RESET . TextFormat::GRAY . "Click to open category"]);
			$this->getInventory()->setItem($index, $item);
		}
		$stats = Utils::getButtonItem($this->player, "stats", "category-list-stats", ["{PAGE}", "{MAX}", "{TOTAL}"], [$this->getPage(), $this->getPageCount(), $this->getTotalCount()]);
		$this->getInventory()->setItem(49, $stats);
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
		$categories = array_values(Category::getAll());
		if($slot <= 44 && isset($categories[$slot])) {
			(new CategoryMenu($player, $categories[$slot]))->setReturnMenu($this)->open();
		}
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}
}