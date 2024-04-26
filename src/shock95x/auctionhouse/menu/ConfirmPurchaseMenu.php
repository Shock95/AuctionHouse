<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu;

use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\muqsit\invmenu\InvMenu;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\FizzSound;
use Ramsey\Uuid\Uuid;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\event\AuctionEndEvent;
use shock95x\auctionhouse\event\ItemPurchasedEvent;
use shock95x\auctionhouse\menu\type\AHMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\SOFe\AwaitGenerator\Await;
use function PHPUnit\Framework\equalTo;

class ConfirmPurchaseMenu extends AHMenu {

	const INDEX_CONFIRM = [9, 10, 11, 12];
	const INDEX_CANCEL = [14, 15, 16, 17];

	protected static string $inventoryType = InvMenu::TYPE_CHEST;

	public function __construct(Player $player, AHListing $listing) {
		$this->setListings([$listing]);
		$this->setName(Locale::get($player, "purchase-menu-name"));
		parent::__construct($player);
	}

	public function renderButtons(): void {
		$confirm = Utils::getButtonItem($this->player, "confirm_purchase", "purchase-confirm");
		$cancel = Utils::getButtonItem($this->player, "cancel_purchase", "purchase-cancel");
		foreach(self::INDEX_CONFIRM as $x) {
			$this->getInventory()->setItem($x, $confirm);
		}
		foreach(self::INDEX_CANCEL as $x) {
			$this->getInventory()->setItem($x, $cancel);
		}
	}

	public function renderListings(): void {
		if(!isset($this->getListings()[0])) return;
		$listing = $this->getListings()[0];

		$item = clone $listing->getItem();

		$info =  Locale::get($this->player, "purchase-item");
		$lore = str_ireplace(["{PRICE}", "{SELLER}"], [$listing->getPrice(true, Settings::formatPrice()), $listing->getSeller()], preg_filter('/^/', TextFormat::RESET, $info["lore"]));
		$lore = Settings::allowLore() ? [...$item->getLore(), ...$lore] : $lore;

		$this->getInventory()->setItem(13, $item->setLore($lore));
	}
	
	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
		if(in_array($slot, self::INDEX_CANCEL)) {
			$player->removeCurrentWindow();
			Locale::sendMessage($player, "cancelled-purchase");
			return false;
		}
		if (!in_array($slot, self::INDEX_CONFIRM)) return false;

		Await::f2c(function () use ($player) {
			$database = AuctionHouse::getInstance()->getDatabase();
			/** @var ?AHListing $listing */
			$listing = yield from Await::promise(fn($resolve) => $database->getListingById($this->getListings()[0]?->getId(), $resolve));
			if($listing == null || $listing->isExpired()) {
				$player->removeCurrentWindow();
				Locale::sendMessage($player, "listing-gone");
				return;
			}
			if ($listing->getSellerUUID()->equals($player->getUniqueId())) {
				$player->removeCurrentWindow();
				Locale::sendMessage($player, "self-purchase");
				return;
			}
			$economy = AuctionHouse::getInstance()->getEconomyProvider();
			$item = $listing->getItem();
			if (!$player->getInventory()->canAddItem($item)) {
				$player->removeCurrentWindow();
				Locale::sendMessage($player, "not-enough-space");
				return;
			}
			$event = new ItemPurchasedEvent($player, $listing);
			$event->call();
			if($event->isCancelled()) return;

			$sub = yield from $economy->subtractMoneyAsync($player, $listing->getPrice());
			if(!$sub) {
				Locale::sendMessage($player, "cannot-afford");
				return;
			}
			$add = yield from $economy->addMoneyAsync($listing->getSeller(), $listing->getPrice());
			if(!$add) {
				yield from $economy->addMoneyAsync($player, $listing->getPrice());
				Locale::sendMessage($player, "purchase-economy-error");
				return;
			}
			$res = yield from $database->removeListingAsync($listing->getId());
			if(!$res) {
				$player->removeCurrentWindow();
				Locale::sendMessage($player, "listing-gone");
				return;
			}
			$player->removeCurrentWindow();
			$player->getInventory()->addItem($item);
			$player->sendMessage(str_ireplace(["{PLAYER}", "{ITEM}", "{PRICE}", "{AMOUNT}"], [$player->getName(), $item->getName(), $listing->getPrice(true, Settings::formatPrice()), $item->getCount()], Locale::get($player, "purchased-item", true)));

			$seller = AuctionHouse::getInstance()->getServer()->getPlayerByUUID($listing->getSellerUUID());
			$seller?->getWorld()->addSound($seller->getPosition(), new FizzSound(), [$seller]);
			$seller?->sendMessage(str_ireplace(["{PLAYER}", "{ITEM}", "{PRICE}", "{AMOUNT}"], [$player->getName(), $item->getName(), $listing->getPrice(true, Settings::formatPrice()), $item->getCount()], Locale::get($seller, "seller-message", true)));
			(new AuctionEndEvent($listing, AuctionEndEvent::PURCHASED, $player))->call();
		});
		return true;
	}
}