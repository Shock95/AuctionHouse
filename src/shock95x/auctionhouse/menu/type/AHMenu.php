<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu\type;

use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\session\PlayerManager;
use muqsit\invmenu\transaction\InvMenuTransaction;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\level\sound\ClickSound;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\menu\ConfirmPurchaseMenu;
use shock95x\auctionhouse\menu\ShopMenu;
use shock95x\auctionhouse\task\CannotPurchaseTask;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Utils;

abstract class AHMenu extends InvMenu {

	protected Player $player;

	protected bool $newMenu = false;
	protected bool $returnMain = false;

	/** @var ?InvMenuInventory  */
	protected $inventory;

	protected static string $inventoryType = InvMenu::TYPE_DOUBLE_CHEST;
	
	public function __construct(Player $player, bool $returnMain = false) {
		parent::__construct(InvMenuHandler::getMenuType(static::$inventoryType));
		$this->player = $player;
		$this->returnMain = $returnMain;

		$this->initialize();
		$this->renderItems();
		$this->show($player);
	}

	private function initialize(): void {
		// workaround for recursive menus & menu bug
		$manager = PlayerManager::get($this->getPlayer());
		if($manager !== null) {
			$menu = $manager->getCurrentMenu();
			if($menu instanceof AHMenu) {
				if($menu->newMenu) {
					$this->getPlayer()->removeWindow($menu->getInventory());
				} else {
					$menu->getInventory()->clearAll();
					$this->inventory = $menu->getInventory();
					$this->setMenuListener($menu);
					return;
				}
			}
		}
		$this->inventory ??= $this->type->createInventory();

		$this->setMenuListener($this);
	}

	public function renderItems(): void {
		if($this->returnMain) {
			$return = Utils::getButtonItem($this->getPlayer(), "back", "back-button");
			$return->setNamedTagEntry(new ByteTag("return", 1));
			$this->getInventory()->setItem(45, $return);
		}
	}

	private function setMenuListener(self $menu) {
		$menu->setListener(InvMenu::readonly(function(InvMenuTransaction $transaction) {
			$this->getPlayer()->getLevel()->addSound(new ClickSound($this->getPlayer()), [$this->getPlayer()]);
			$this->handle($transaction->getPlayer(), $transaction->getItemClicked(), $transaction->getAction()->getInventory(), $transaction->getAction()->getSlot());
		}));
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
		if($itemClicked->getNamedTag()->hasTag("return")) {
			new ShopMenu($player);
		}
		return true;
	}

	public function show(Player $player): void {
		if(!(PlayerManager::get($player)->getCurrentMenu() != null && !$this->newMenu)) {
			$this->send($player);
		}
	}

	protected function checkPurchase(int $slot, Item $itemClicked): bool {
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
}