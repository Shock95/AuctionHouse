<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\menu;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\database\storage\DataStorage;
use shock95x\auctionhouse\menu\type\PagingMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;
use SOFe\AwaitGenerator\Await;
use function ceil;
use function preg_filter;
use function str_ireplace;

class ExpiredMenu extends PagingMenu{

	private int $total;

	const INDEX_RETURN_ALL = 49;

	public function __construct(Player $player, bool $returnMain = true){
		$this->setName(Locale::get($player, "expired-menu-name"));
		parent::__construct($player, $returnMain);
	}

	protected function init(DataStorage $storage) : void{
		Await::f2c(function() use ($storage){
			$this->setListings(yield from Await::promise(fn($resolve) => $storage->getExpiredListingsByPlayer($resolve, $this->player, (45 * $this->page) - 45)));
			$this->total = yield from Await::promise(fn($resolve) => $storage->getExpiredCountByPlayer($this->player, $resolve));
			$this->pages = (int) ceil($this->total / 45);
			parent::init($storage);
		});
	}

	public function renderButtons() : void{
		parent::renderButtons();

		$stats = Utils::getButtonItem($this->player, "return_all", "expired-stats", ["{PAGE}", "{MAX}", "{TOTAL}"], [$this->page, $this->page, $this->total]);

		$this->getInventory()->setItem(53, Utils::getButtonItem($this->player, "info", "main-description"));
		$this->getInventory()->setItem(49, $stats);
	}

	public function renderListings() : void{
		foreach($this->getListings() as $index => $listing){
			$item = clone $listing->getItem();

			$expiredItem = Locale::get($this->player, "expired-item");

			$lore = str_ireplace(["{PRICE}"], [$listing->getPrice(true, Settings::formatPrice())], preg_filter('/^/', TextFormat::RESET, $expiredItem));
			$lore = Settings::allowLore() ? [...$item->getLore(), ...$lore] : $lore;
			$item->setLore($lore);

			$this->getInventory()->setItem($index, $item);
		}
		parent::renderListings();
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot) : bool{
		Await::f2c(function() use ($player, $slot, $itemClicked, $inventory){
			$storage = DataStorage::getInstance();
			if($slot <= 44 && isset($this->getListings()[$slot])){
				$id = $this->getListings()[$slot]->getId();
				$listing = yield from Await::promise(fn($resolve) => $storage->getListingById($id, $resolve));
				if($listing == null || $listing->getSellerUUID() != $player->getUniqueId()->toString()){
					return;
				}
				$item = $listing->getItem();
				if($player->getInventory()->canAddItem($item)){
					$storage->removeListing($listing);
					$inventory->setItem($slot, VanillaItems::AIR());
					$player->getInventory()->addItem($item);
					$player->sendMessage(str_ireplace(["{ITEM}", "{AMOUNT}"], [$item->getName(), $item->getCount()], Locale::get($player, "returned-item", true)));
				}
			}
			if($slot == self::INDEX_RETURN_ALL){
				if(Utils::getEmptySlotCount($player->getInventory()) < $this->total){
					Locale::sendMessage($player, "inventory-full");
					return;
				}
				foreach($this->getListings() as $index => $expired){
					if($player->getInventory()->canAddItem($expired->getItem())){
						$storage->removeListing($expired);
						$inventory->setItem($index, VanillaItems::AIR());
						$player->getInventory()->addItem($expired->getItem());
					}
				}
			}
			parent::handle($player, $itemClicked, $inventory, $slot);
		});
		return true;
	}
}
