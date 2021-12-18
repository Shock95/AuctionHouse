<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu\type;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\session\InvMenuInfo;
use muqsit\invmenu\transaction\InvMenuTransaction;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\ClickSound;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\storage\DataStorage;
use shock95x\auctionhouse\event\MenuCloseEvent;
use shock95x\auctionhouse\menu\ConfirmPurchaseMenu;
use shock95x\auctionhouse\menu\ShopMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Utils;
use SOFe\AwaitGenerator\Await;

abstract class AHMenu extends InvMenu {

	protected ?Player $player;
	protected Inventory $inventory;

	protected bool $returnMain = false;

	/** @var AHListing[] */
	protected array $listings = [];

	protected static string $inventoryType = InvMenu::TYPE_DOUBLE_CHEST;

	const INDEX_RETURN = 45;

	public function __construct(Player $player, bool $returnMain = false) {
		parent::__construct(InvMenuHandler::getTypeRegistry()->get(static::$inventoryType));
		$this->player = $player;
		$this->returnMain = $returnMain;

		$this->setListener(InvMenu::readonly(function(InvMenuTransaction $transaction) {
			$this->player->getWorld()->addSound($this->player->getPosition(), new ClickSound(), [$this->player]);
			$this->handle($transaction->getPlayer(), $transaction->getItemClicked(), $transaction->getAction()->getInventory(), $transaction->getAction()->getSlot());
		}));

		$this->init(DataStorage::getInstance());
	}

	protected function init(DataStorage $storage): void {
		$this->renderButtons();
		$this->renderListings();
	}

	public function renderButtons(): void {
		if($this->returnMain) {
			$this->getInventory()->setItem(self::INDEX_RETURN, Utils::getButtonItem($this->player, "back", "back-button"));
		}
	}

	public function renderListings(): void {}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
		if($this->returnMain && $slot == self::INDEX_RETURN) {
			self::open(new ShopMenu($player), false);
		}
		return true;
	}

	protected function openListing(int $slot, Item $itemClicked): bool {
		if($slot <= 44 && isset($this->getListings()[$slot])) {
			Await::f2c(function () use ($itemClicked, $slot) {
				$listing = $this->getListings()[$slot];
				$balance = yield AuctionHouse::getInstance()->getEconomyProvider()->getMoney($this->player, yield) => Await::ONCE;
				if($balance < $listing->getPrice()) {
					$this->getInventory()->setItem($slot, ItemFactory::getInstance()->get(-161)->setCustomName(TextFormat::RESET . Locale::get($this->player, "cannot-afford")));
					AuctionHouse::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($itemClicked, $slot) {
						$this->inventory?->setItem($slot, $itemClicked);
					}), 40);
				} else {
					$this->player->removeCurrentWindow();
					self::open(new ConfirmPurchaseMenu($this->player, $listing));
				}
			});
		}
		return true;
	}

	/**
	 * @param AHListing[] $listings
	 */
	protected function setListings(array $listings): void {
		$this->listings = $listings;
	}

	/**
	 * @return AHListing[]
	 */
	public function getListings(): array {
		return $this->listings;
	}

	public function getPlayer(): Player {
		return $this->player;
	}

	public function onClose(Player $player): void {
		(new MenuCloseEvent($player, $this))->call();
		parent::onClose($player);
	}

	public static function open(self $menu, bool $newWindow = true): void {
		$session = InvMenuHandler::getPlayerManager()->get($menu->player);
		if($session->getCurrent() == null) {
			$menu->send($menu->player);
			return;
		}
		$currentMenu = $session->getCurrent()?->menu;
		if($newWindow && $currentMenu instanceof AHMenu) {
			$currentMenu->player->removeCurrentWindow();
			$menu->send($menu->player);
			return;
		}
		$session->setCurrentMenu(new InvMenuInfo($menu, $session->getCurrent()->graphic));
	}
}