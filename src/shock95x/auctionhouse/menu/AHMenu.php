<?php
namespace shock95x\auctionhouse\menu;

use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\session\PlayerManager;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Pagination;

abstract class AHMenu extends InvMenu {

    public $page = 1;

	protected $inventoryType = InvMenu::TYPE_DOUBLE_CHEST;
	protected $player;

	protected $returnMain = false;
	protected $pagination = false;
	protected $newMenu = false;

	/** @var InvMenuInventory  */
	protected $inventory;
	
	public function __construct(Player $player, bool $returnMain = false, bool $pagination = false) {
		$type = InvMenuHandler::getMenuType($this->inventoryType);
		parent::__construct($type);
		$this->returnMain = $returnMain;
		$this->pagination = $pagination;

		// workaround for recursive menus
		if(PlayerManager::get($player)->getCurrentMenu() != null && !$this->newMenu) {
			$menu = PlayerManager::get($player)->getCurrentMenu();
			$menu->getInventory()->clearAll();
			$this->inventory = $menu->getInventory();
			$menu->setListener([$this, "handle"]);
		} else {
			$this->inventory = $type->createInventory();
			$this->setListener([$this, "handle"]);
		}
		$this->player = $player;

		$this->readonly();
		$this->renderPagination();
		$this->renderItems();
		$this->show($player);
	}

	abstract function renderItems();

	public function handle(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool {
		if($itemClicked->getNamedTag()->hasTag("pagination")) {
			$this->handlePagination($itemClicked->getNamedTag()->getInt("pagination"));
			return true;
		}
		if($itemClicked->getNamedTag()->hasTag("return")) {
			new MainMenu($player);
			return true;
		}
		return true;
	}

	public function renderPagination() {
		if($this->returnMain) {
			$return = Item::get(Item::PAPER)->setNamedTag(new CompoundTag("", [new IntTag("return", 1)]))->setCustomName(TextFormat::RESET . TextFormat::GOLD . "Back");
			$this->getInventory()->setItem(45, $return);
		}
		if($this->pagination) {
			$pagination = [Item::get(Item::PAPER), Item::get(Item::PAPER)];
			for ($x = 0; $x <= 1; $x++) {
				$pagination[$x]->setNamedTag(new CompoundTag("", [new IntTag("pagination", $x)]));
			}
			$previous = Locale::getMessage($this->player, "previous-page", true, false);
			$next = Locale::getMessage($this->player, "next-page", true, false);
			$pagination[0]->setCustomName(TextFormat::RESET . $previous["name"])->setLore((preg_filter('/^/', TextFormat::RESET, $previous["lore"])));
			$pagination[1]->setCustomName(TextFormat::RESET . $next["name"])->setLore((preg_filter('/^/', TextFormat::RESET, $next["lore"])));
			$items = [48 => $pagination[0], 50 => $pagination[1]];

			foreach ($items as $slot => $item) $this->getInventory()->setItem($slot, $item);
		}
	}

	public function show(Player $player) {
		if(PlayerManager::get($player)->getCurrentMenu() != null && !$this->newMenu) {
			return;
		} else {
			$this->send($player);
		}
	}

	public function handlePagination(int $page) {
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

	public function getPlayer() {
		return $this->player;
	}

	public function getInventory(): InvMenuInventory {
		return $this->inventory;
	}

	public function getInventoryForPlayer(Player $player): InvMenuInventory {
		return $this->getInventory();
	}
}