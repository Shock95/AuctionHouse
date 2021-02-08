<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu;

use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\metadata\MenuMetadata;
use muqsit\invmenu\session\PlayerManager;
use muqsit\invmenu\transaction\InvMenuTransaction;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\task\CannotPurchaseTask;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Pagination;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;

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

		// workaround for recursive menus & menu bug
		if(PlayerManager::get($player) !== null) {
			$menu = PlayerManager::get($player)->getCurrentMenu();
			if($menu !== null && $menu instanceof AHMenu) {
				if($menu->newMenu) {
					$player->removeWindow($menu->getInventory());
				} else {
					$menu->getInventory()->clearAll();
					$this->inventory = $menu->getInventory();
					$this->setListeners($menu);
				}
			}
		}
		if($this->inventory == null) $this->createNewInventory($type);
		$this->setListeners($this);

		$this->player = $player;

		$this->renderPagination();
		$this->renderItems();
		$this->show($player);
	}

	abstract function renderItems(): void;

	public function createNewInventory(MenuMetadata $type) {
		$this->inventory = $type->createInventory();
	}

	public function setListeners(InvMenu $menu) {
		$menu->setListener(InvMenu::readonly(function(InvMenuTransaction $transaction) {
			Utils::sendLevelEvent($this->getPlayer(), LevelEventPacket::EVENT_SOUND_CLICK);
			$this->handle($transaction->getPlayer(), $transaction->getItemClicked(), $transaction->getAction()->getInventory(), $transaction->getAction()->getSlot());
		}));
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
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
			$return = Item::fromString(Settings::getButtons()["back"])->setNamedTag(new CompoundTag("", [new IntTag("return", 1)]))->setCustomName(TextFormat::RESET . TextFormat::GOLD . "Back");
			$this->getInventory()->setItem(45, $return);
		}
		if($this->pagination) {
			$pagination = [Item::fromString(Settings::getButtons()["previous"]), Item::fromString(Settings::getButtons()["next"])];
			for ($x = 0; $x <= 1; $x++) {
				$pagination[$x]->setNamedTag(new CompoundTag("", [new IntTag("pagination", $x)]));
			}
			$previous = Locale::getMessage($this->player, "previous-page");
			$next = Locale::getMessage($this->player, "next-page");

			$pagination[0]->setCustomName(TextFormat::RESET . $previous["name"])->setLore((preg_filter('/^/', TextFormat::RESET, $previous["lore"])));
			$pagination[1]->setCustomName(TextFormat::RESET . $next["name"])->setLore((preg_filter('/^/', TextFormat::RESET, $next["lore"])));
			$items = [48 => $pagination[0], 50 => $pagination[1]];

			foreach ($items as $slot => $item) $this->getInventory()->setItem($slot, $item);
		}
	}

	public function show(Player $player): void {
		if(!(PlayerManager::get($player)->getCurrentMenu() != null && !$this->newMenu)) {
			$this->send($player);
		}
	}

	public function handlePagination(int $page): void {
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

	public function checkPurchase(int $slot, Item $itemClicked): bool {
		if($slot <= 44 && $itemClicked->getNamedTag()->hasTag("marketId")) {
			$marketId = $itemClicked->getNamedTag()->getLong("marketId");
			$listing = DataHolder::getListingById($marketId);
			if($listing == null) {
				return false;
			}
			if(!Utils::canAfford($this->getPlayer(), $listing)) {
				$this->getInventory()->setItem($slot, Item::get(-161)->setCustomName(TextFormat::RESET . Locale::getMessage($this->getPlayer(), "cannot-afford")));
				AuctionHouse::getInstance()->getScheduler()->scheduleDelayedTask(new CannotPurchaseTask($this->getInventory(), $itemClicked, $slot), 40);
				return false;
			}
			$this->getPlayer()->removeWindow($this->getInventory());
			new ConfirmPurchaseMenu($this->player, $listing);
		}
		return true;
	}

	public function getPlayer(): Player {
		return $this->player;
	}

	public function getInventory(): InvMenuInventory {
		return $this->inventory;
	}
}