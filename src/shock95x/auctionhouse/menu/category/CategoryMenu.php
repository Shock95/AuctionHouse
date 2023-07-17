<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\menu\category;

use DateTime;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\category\ICategory;
use shock95x\auctionhouse\database\storage\DataStorage;
use shock95x\auctionhouse\menu\type\PagingMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Pagination;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;
use SOFe\AwaitGenerator\Await;
use function array_filter;
use function array_slice;
use function array_walk;
use function ceil;
use function count;
use function is_null;
use function preg_filter;
use function str_ireplace;
use const PHP_INT_MAX;

class CategoryMenu extends PagingMenu{

	private int $total;
	private ICategory $category;

	public function __construct(Player $player, ICategory $category){
		$this->category = $category;
		$this->setName($category->getDisplayName());
		parent::__construct($player, true);
	}

	protected function init(DataStorage $storage) : void{
		Await::f2c(function() use ($storage){
			$this->setListings(yield from Await::promise(fn($resolve) => $storage->getListings($resolve, 0, PHP_INT_MAX)));
			$this->total = count($this->getListings());
			$this->pages = (int) ceil($this->total / 45);
			parent::init($storage);
		});
	}

	public function renderButtons() : void{
		parent::renderButtons();
		$stats = Utils::getButtonItem($this->player, "stats", "category-stats", ["{PAGE}", "{MAX}", "{TOTAL}", "{CATEGORY}"], [$this->page, $this->pages, $this->total, $this->category->getDisplayName()]);
		$this->getInventory()->setItem(self::INDEX_REFRESH, $stats);
	}

	public function renderListings() : void{
		$listings = array_slice($this->getListings(), ($this->page - 1) * 45, 45);
		foreach($listings as $key => $auction){
			$item = clone $auction->getItem();
			$endTime = (new DateTime())->diff((new DateTime())->setTimestamp($auction->getEndTime()));

			$listedItem = Locale::get($this->player, "listed-item");
			$lore = str_ireplace(["{PRICE}", "{SELLER}", "{D}", "{H}", "{M}"], [$auction->getPrice(true, Settings::formatPrice()), $auction->getSeller(), $endTime->days, $endTime->h, $endTime->i], preg_filter('/^/', TextFormat::RESET, $listedItem));
			$lore = Settings::allowLore() ? [...$item->getLore(), ...$lore] : $lore;

			$item->setLore($lore);
			$this->getInventory()->setItem($key, $item);
		}
		parent::renderListings();
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot) : bool{
		if($slot == self::INDEX_RETURN){
			self::open(new CategoryListMenu($player, $this->returnMain), false);
			return true;
		}
		$this->openListing($slot);
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}

	protected function setListings(array $listings) : void{
		$filtered = [];
		$listings = array_filter($listings, [$this->category, "sort"]);
		array_walk($listings, function($l) use (&$filtered){ if(!is_null($l)) $filtered[] = $l; });
		$this->listings = $filtered;
	}

	protected function handlePagination(int $action) : bool{
		switch($action){
			case Pagination::BACK:
				if(($this->page - 1) <= 0) break;
				$this->page--;
				break;
			case Pagination::NEXT:
				if(($this->page + 1) > $this->pages) break;
				$this->page++;
				break;
			default:
				$this->page = 1;
				$this->init(DataStorage::getInstance());
				return true;
		}
		parent::init(DataStorage::getInstance());
		return true;
	}
}
