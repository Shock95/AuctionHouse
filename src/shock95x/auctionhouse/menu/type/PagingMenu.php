<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu\type;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\Player;
use shock95x\auctionhouse\utils\Pagination;
use shock95x\auctionhouse\utils\Utils;

abstract class PagingMenu extends AHMenu {

	public int $page = 1;

	public function __construct(Player $player, int $page = 1, bool $returnMain = false) {
		parent::__construct($player, $returnMain);
		$this->page = $page;
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
		if($itemClicked->getNamedTag()->hasTag("page")) {
			$this->handlePagination($itemClicked->getNamedTag()->getByte("page"));
			return true;
		}
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}

	public function renderItems(): void {
		parent::renderItems();
		$back = Utils::getButtonItem($this->getPlayer(), "previous", "previous-page");
		$next = Utils::getButtonItem($this->getPlayer(), "next", "next-page");

		$back->setNamedTagEntry(new ByteTag("page", Pagination::BACK));
		$next->setNamedTagEntry(new ByteTag("page", Pagination::NEXT));

		$this->getInventory()->setItem(48, $back);
		$this->getInventory()->setItem(50, $next);
	}

	public function getPage() : int {
		return $this->page;
	}

	protected function handlePagination(int $page): void {
		switch($page) {
			case Pagination::BACK:
				$this->page--;
				break;
			case Pagination::NEXT:
				$this->page++;
				break;
		}
		$this->renderItems();
	}

	protected function checkLastPage(int $max): bool {
		if($this->page > $max) {
			$this->page = 1;
			$this->renderItems();
			return true;
		}
		return false;
	}
}