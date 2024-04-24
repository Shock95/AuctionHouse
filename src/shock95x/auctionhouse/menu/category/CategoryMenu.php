<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu\category;

use DateTime;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\category\ICategory;
use shock95x\auctionhouse\database\Database;
use shock95x\auctionhouse\menu\ConfirmPurchaseMenu;
use shock95x\auctionhouse\menu\type\PagingMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Pagination;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;
use SOFe\AwaitGenerator\Await;

class CategoryMenu extends PagingMenu {

	private ICategory $category;

	public function __construct(Player $player, ICategory $category) {
		$this->category = $category;
		$this->setName($category->getDisplayName());
		parent::__construct($player);
	}

	protected function init(Database $database): void {
		Await::f2c(function () use ($database) {
			$this->setListings(yield from Await::promise(fn($resolve) => $database->getActiveListings($resolve, 0, PHP_INT_MAX)));
			$this->setTotalCount(count($this->getListings()));
			parent::init($database);
		});
	}

	public function renderButtons(): void {
		parent::renderButtons();
		$stats = Utils::getButtonItem($this->player, "stats", "category-stats", ["{PAGE}", "{MAX}", "{TOTAL}", "{CATEGORY}"], [$this->getPage(), $this->getPageCount(), $this->getTotalCount(), $this->category->getDisplayName()]);
		$this->getInventory()->setItem(self::INDEX_REFRESH, $stats);
	}

	public function renderListings(): void {
		$listings = array_slice($this->getListings(), $this->getItemOffset(), 45);
		foreach($listings as $i => $listing) {
			$item = clone $listing->getItem();
			$endTime = (new DateTime())->diff((new DateTime())->setTimestamp($listing->getExpireTime()));

			$listedItem = Locale::get($this->player, "listed-item");
			$lore = str_ireplace(["{PRICE}", "{SELLER}", "{D}", "{H}", "{M}"], [$listing->getPrice(true, Settings::formatPrice()), $listing->getSeller(), $endTime->days, $endTime->h,  $endTime->i], preg_filter('/^/', TextFormat::RESET, $listedItem));
			$lore = Settings::allowLore() ? [...$item->getLore(), ...$lore] : $lore;

			$item->setLore($lore);
			$this->getInventory()->setItem($i, $item);
		}
		parent::renderListings();
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
		if(isset($this->getListings()[$slot])) {
			(new ConfirmPurchaseMenu($player, $this->getListings()[$slot]))->open();
			return true;
		}
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}

	protected function setListings(array $listings): void {
		$filtered = [];
		$listings = array_filter($listings, [$this->category, "sort"]);
		array_walk($listings, function($l) use(&$filtered) { if(!is_null($l)) $filtered[] = $l; });
		parent::setListings($filtered);
	}

	protected function handlePagination(int $action): bool {
		$page = $this->getPage();
		$pageCount = $this->getPageCount();
		switch($action) {
			case Pagination::BACK:
				if($page > 1) $this->setPage($page - 1);
				break;
			case Pagination::NEXT:
				if($page < $pageCount) $this->setPage($page + 1);
				break;
			default:
				$this->setPage(1);
				$this->init(AuctionHouse::getInstance()->getDatabase());
				return true;
		}
		parent::init(AuctionHouse::getInstance()->getDatabase());
		return true;
	}
}