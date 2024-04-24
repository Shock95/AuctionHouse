<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu\type;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\utils\Pagination;
use shock95x\auctionhouse\utils\Utils;

abstract class PagingMenu extends AHMenu {

	const INDEX_RETURN = 45;
	const INDEX_BACK = 48;
	const INDEX_REFRESH = 49;
	const INDEX_NEXT = 50;

	private int $page = 1;
	private int $pageCount = 1;
	private int $totalCount = 0;
	protected int $itemsPerPage = 45;

	public function __construct(Player $player) {
		parent::__construct($player);
	}

	public function setPage(int $page) : void{
		$this->page = $page;
	}

	public function setTotalCount(int $itemCount): void {
		$this->totalCount = $itemCount;
		$this->pageCount = (int) (ceil($itemCount / $this->itemsPerPage)) ?: 1;
	}

	public function getPage(): int {
		return $this->page;
	}

	public function getPageCount(): int {
		return $this->pageCount;
	}

	public function getTotalCount(): int {
		return $this->totalCount;
	}

	public function getItemOffset(): int {
		return ($this->page - 1) * $this->itemsPerPage;
	}

	public function renderListings(): void {
		for($i = count($this->listings); $i < $this->itemsPerPage; ++$i) {
			$this->getInventory()->setItem($i, VanillaItems::AIR());
		}
	}

	public function renderButtons(): void {
		$showPrevious = ($this->page > 1);
		$showNext = ($this->page < $this->pageCount);
		$this->getInventory()->setItem(self::INDEX_BACK, ($showPrevious ? Utils::getButtonItem($this->player, "previous", "previous-page") : VanillaItems::AIR()));
		$this->getInventory()->setItem(self::INDEX_NEXT, ($showNext ? Utils::getButtonItem($this->player, "next", "next-page") : VanillaItems::AIR()));
		if($this->returnMenu) $this->getInventory()->setItem(self::INDEX_RETURN, Utils::getButtonItem($this->player, "back", "back-button"));
	}

	protected function handlePagination(int $action): bool {
		switch($action) {
			case Pagination::BACK:
				if($this->page > 1) {
					$this->page--;
				}
				break;
			case Pagination::NEXT:
				if($this->page < $this->pageCount) {
					$this->page++;
				}
				break;
		}
		$this->init(AuctionHouse::getInstance()->getDatabase());
		return true;
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
		switch ($slot) {
			case self::INDEX_RETURN:
				$this->returnMenu?->send($player);
				break;
			case self::INDEX_BACK:
				return $this->handlePagination(Pagination::BACK);
			case self::INDEX_NEXT:
				return $this->handlePagination(Pagination::NEXT);
			case self::INDEX_REFRESH:
				return $this->handlePagination(Pagination::REFRESH);
		}
		return true;
	}
}