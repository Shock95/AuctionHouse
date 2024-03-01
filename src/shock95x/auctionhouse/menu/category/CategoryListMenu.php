<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\menu\category;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\category\Category;
use shock95x\auctionhouse\category\ICategory;
use shock95x\auctionhouse\database\storage\DataStorage;
use shock95x\auctionhouse\menu\type\PagingMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Utils;
use function array_slice;
use function array_values;
use function ceil;
use function count;

class CategoryListMenu extends PagingMenu{

	private int $total;

	public function __construct(Player $player, bool $returnMain = true){
		$this->setName(Locale::get($player, "category-menu-name"));
		parent::__construct($player, $returnMain);
	}

	protected function init(DataStorage $storage) : void{
		$this->total = count(Category::getAll());
		$this->pages = (int) ceil($this->total / 45);
		parent::init($storage);
	}

	public function renderButtons() : void{
		parent::renderButtons();
		$categories = array_slice(array_values(Category::getAll()), ($this->page - 1) * 45, 45);
		/** @var ICategory $category */
		foreach($categories as $index => $category){
			$item = $category->getMenuItem();
			$item->setLore([TextFormat::RESET . TextFormat::GRAY . "Click to open category"]);
			$this->getInventory()->setItem($index, $item);
		}
		$stats = Utils::getButtonItem($this->player, "stats", "category-list-stats", ["{PAGE}", "{MAX}", "{TOTAL}"], [$this->page, $this->pages, $this->total]);
		$this->getInventory()->setItem(49, $stats);
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot) : bool{
		$categories = array_values(Category::getAll());
		if($slot <= 44 && isset($categories[$slot])){
			self::open(new CategoryMenu($player, $categories[$slot]), false);
		}
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}
}
