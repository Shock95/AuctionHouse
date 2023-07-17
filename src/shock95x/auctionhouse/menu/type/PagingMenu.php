<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\menu\type;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;
use shock95x\auctionhouse\database\storage\DataStorage;
use shock95x\auctionhouse\utils\Pagination;
use shock95x\auctionhouse\utils\Utils;

abstract class PagingMenu extends AHMenu{

	protected int $page = 1;
	protected int $pages = 1;

	const INDEX_BACK = 48;
	const INDEX_REFRESH = 49;
	const INDEX_NEXT = 50;

	public function __construct(Player $player, bool $returnMain = false){
		parent::__construct($player, $returnMain);
	}

	public function getPage() : int{
		return $this->page;
	}

	public function getPages() : int{
		return $this->pages;
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot) : bool{
		switch($slot){
			case self::INDEX_BACK:
				return $this->handlePagination(Pagination::BACK);
			case self::INDEX_NEXT:
				return $this->handlePagination(Pagination::NEXT);
			case self::INDEX_REFRESH:
				return $this->handlePagination(Pagination::REFRESH);
		}
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}

	public function renderButtons() : void{
		parent::renderButtons();
		$back = Utils::getButtonItem($this->player, "previous", "previous-page");
		$next = Utils::getButtonItem($this->player, "next", "next-page");
		$this->getInventory()->setItem(self::INDEX_BACK, $back);
		$this->getInventory()->setItem(self::INDEX_NEXT, $next);
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
		}
		$this->init(DataStorage::getInstance());
		return true;
	}
}
