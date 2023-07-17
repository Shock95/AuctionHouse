<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\menu;

use muqsit\invmenu\InvMenu;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\FizzSound;
use Ramsey\Uuid\Uuid;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\storage\DataStorage;
use shock95x\auctionhouse\event\AuctionEndEvent;
use shock95x\auctionhouse\event\ItemPurchasedEvent;
use shock95x\auctionhouse\menu\type\AHMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;
use SOFe\AwaitGenerator\Await;
use function in_array;
use function preg_filter;
use function str_ireplace;

class ConfirmPurchaseMenu extends AHMenu{

	const INDEX_CONFIRM = [9, 10, 11, 12];
	const INDEX_CANCEL = [14, 15, 16, 17];

	protected static string $inventoryType = InvMenu::TYPE_CHEST;

	public function __construct(Player $player, AHListing $listing){
		$this->setListings([$listing]);
		$this->setName(Locale::get($player, "purchase-menu-name"));
		parent::__construct($player);
	}

	public function renderButtons() : void{
		foreach(self::INDEX_CONFIRM as $x){
			$confirmItem = Utils::getButtonItem($this->player, "confirm_purchase", "purchase-confirm");
			$this->getInventory()->setItem($x, $confirmItem);
		}
		foreach(self::INDEX_CANCEL as $x){
			$cancelItem = Utils::getButtonItem($this->player, "cancel_purchase", "purchase-cancel");
			$this->getInventory()->setItem($x, $cancelItem);
		}
	}

	public function renderListings() : void{
		if(!isset($this->getListings()[0])) return;
		$listing = $this->getListings()[0];

		$item = clone $listing->getItem();

		$info = Locale::get($this->player, "purchase-item");
		$lore = str_ireplace(["{PRICE}", "{SELLER}"], [$listing->getPrice(true, Settings::formatPrice()), $listing->getSeller()], preg_filter('/^/', TextFormat::RESET, $info["lore"]));
		$lore = Settings::allowLore() ? [...$item->getLore(), ...$lore] : $lore;

		$this->getInventory()->setItem(13, $item->setLore($lore));
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot) : bool{
		if(in_array($slot, self::INDEX_CANCEL, true)){
			$player->removeCurrentWindow();
			Locale::sendMessage($player, "cancelled-purchase");
			return false;
		}
		if(!in_array($slot, self::INDEX_CONFIRM, true)) return false;

		Await::f2c(function() use ($player){
			$storage = DataStorage::getInstance();
			/** @var ?AHListing $listing */
			$listing = yield from Await::promise(fn($resolve) => $storage->getListingById($this->getListings()[0]?->getId(), $resolve));
			if($listing == null || $listing->isExpired()){
				Locale::sendMessage($player, "listing-gone");
				return;
			}
			if($listing->getSellerUUID() == $player->getUniqueId()){
				$player->removeCurrentWindow();
				Locale::sendMessage($player, "self-purchase");
				return;
			}
			$economy = AuctionHouse::getInstance()->getEconomyProvider();
			$item = $listing->getItem();
			if(!$player->getInventory()->canAddItem($item)){
				$player->removeCurrentWindow();
				Locale::sendMessage($player, "not-enough-space");
				return;
			}
			$event = new ItemPurchasedEvent($player, $listing);
			$event->call();
			if($event->isCancelled()) return;

			$storage->removeListing($listing);

			$res = yield from Await::promise(fn($resolve) => $economy->subtractMoney($player, $listing->getPrice(), $resolve));
			if(!$res){
				Locale::sendMessage($player, "cannot-afford");
				return;
			}
			$res = yield from Await::promise(fn($resolve) => $economy->addMoney($listing->getSeller(), $listing->getPrice(), $resolve));
			if(!$res){
				yield from Await::promise(fn($resolve) => $economy->addMoney($player, $listing->getPrice(), $resolve));
				Locale::sendMessage($player, "purchase-economy-error");
				return;
			}
			$player->removeCurrentWindow();
			$player->getInventory()->addItem($item);
			$player->sendMessage(str_ireplace(["{PLAYER}", "{ITEM}", "{PRICE}", "{AMOUNT}"], [$player->getName(), $item->getName(), $listing->getPrice(true, Settings::formatPrice()), $item->getCount()], Locale::get($player, "purchased-item", true)));

			$seller = AuctionHouse::getInstance()->getServer()->getPlayerByUUID(Uuid::fromString($listing->getSellerUUID()));
			$seller?->getWorld()->addSound($seller?->getPosition(), new FizzSound(), [$seller]);
			$seller?->sendMessage(str_ireplace(["{PLAYER}", "{ITEM}", "{PRICE}", "{AMOUNT}"], [$player->getName(), $item->getName(), $listing->getPrice(true, Settings::formatPrice()), $item->getCount()], Locale::get($player, "seller-message", true)));
			(new AuctionEndEvent($listing, AuctionEndEvent::PURCHASED, $player))->call();
		});
		return true;
	}
}
