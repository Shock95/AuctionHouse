<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\menu\type;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\world\sound\ClickSound;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\database\storage\DataStorage;
use shock95x\auctionhouse\event\MenuCloseEvent;
use shock95x\auctionhouse\menu\ConfirmPurchaseMenu;
use shock95x\auctionhouse\menu\ShopMenu;
use shock95x\auctionhouse\utils\Utils;
use function count;

abstract class AHMenu extends InvMenu{

	protected ?Player $player;
	protected Inventory $inventory;

	protected bool $returnMain = false;

	/** @var AHListing[] */
	protected array $listings = [];

	protected static string $inventoryType = InvMenu::TYPE_DOUBLE_CHEST;

	const INDEX_RETURN = 45;

	public function __construct(Player $player, bool $returnMain = false){
		parent::__construct(InvMenuHandler::getTypeRegistry()->get(static::$inventoryType));
		$this->player = $player;
		$this->returnMain = $returnMain;

		$this->setListener(InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction){
			$this->player->getWorld()->addSound($this->player->getPosition(), new ClickSound(), [$this->player]);
			$this->handle($transaction->getPlayer(), $transaction->getItemClicked(), $transaction->getAction()->getInventory(), $transaction->getAction()->getSlot());
		}));

		$this->init(DataStorage::getInstance());
	}

	protected function init(DataStorage $storage) : void{
		$this->renderButtons();
		$this->renderListings();
	}

	public function renderButtons() : void{
		if($this->returnMain){
			$this->getInventory()->setItem(self::INDEX_RETURN, Utils::getButtonItem($this->player, "back", "back-button"));
		}
	}

	public function renderListings() : void{
		for($i = count($this->listings); $i < 45; ++$i){
			$this->getInventory()->setItem($i, VanillaItems::AIR());
		}
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot) : bool{
		if($this->returnMain && $slot == self::INDEX_RETURN){
			self::open(new ShopMenu($player), false);
		}
		return true;
	}

	protected function openListing(int $slot) : bool{
		if($slot <= 44 && isset($this->getListings()[$slot])){
			$listing = $this->getListings()[$slot];
			$this->player->removeCurrentWindow();
			self::open(new ConfirmPurchaseMenu($this->player, $listing));
		}
		return true;
	}

	/**
	 * @param AHListing[] $listings
	 */
	protected function setListings(array $listings) : void{
		$this->listings = $listings;
	}

	/**
	 * @return AHListing[]
	 */
	public function getListings() : array{
		return $this->listings;
	}

	public function getPlayer() : Player{
		return $this->player;
	}

	public function onClose(Player $player) : void{
		(new MenuCloseEvent($player, $this))->call();
		parent::onClose($player);
	}

	public static function open(self $menu, bool $newWindow = true) : void{
		$session = InvMenuHandler::getPlayerManager()->get($menu->player);
		$currentMenu = $session->getCurrent()?->menu;
		if($session->getCurrent() == null || ($newWindow && $currentMenu instanceof AHMenu)){
			$menu->send($menu->player);
			return;
		}
		$currentMenu->getInventory()->clearAll();
		$currentMenu->setInventory($menu->getInventory());
		$currentMenu->setListener($menu->listener);
	}
}
