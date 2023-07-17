<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\menu;

use DateTime;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\database\storage\DataStorage;
use shock95x\auctionhouse\event\AuctionEndEvent;
use shock95x\auctionhouse\menu\type\PagingMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;
use SOFe\AwaitGenerator\Await;
use function ceil;
use function preg_filter;
use function str_ireplace;

class ListingsMenu extends PagingMenu{

	private int $total;

	public function __construct(Player $player, bool $returnMain = true){
		$this->setName(Locale::get($player, "listings-menu-name"));
		parent::__construct($player, $returnMain);
	}

	protected function init(DataStorage $storage) : void{
		Await::f2c(function() use ($storage){
			$this->setListings(yield from Await::promise(fn($resolve) => $storage->getActiveListingsByPlayer($resolve, $this->player, (45 * $this->page) - 45)));
			$this->total = yield from Await::promise(fn($resolve) => $storage->getActiveCountByPlayer($this->player, $resolve));
			$this->pages = (int) ceil($this->total / 45);
			parent::init($storage);
		});
	}

	public function renderButtons() : void{
		parent::renderButtons();
		$info = Utils::getButtonItem($this->player, "info", "listings-description");
		$stats = Utils::getButtonItem($this->player, "stats", "listings-stats", ["{PAGE}", "{MAX}", "{TOTAL}"], [$this->page, $this->pages, $this->total]);

		$this->getInventory()->setItem(53, $info);
		$this->getInventory()->setItem(49, $stats);
	}

	public function renderListings() : void{
		foreach($this->getListings() as $index => $listing){
			$item = clone $listing->getItem();

			$endTime = (new DateTime())->diff((new DateTime())->setTimestamp($listing->getEndTime()));
			$listedItem = Locale::get($this->player, "your-listed-item");

			$lore = str_ireplace(["{PRICE}", "{D}", "{H}", "{M}"], [$listing->getPrice(true, Settings::formatPrice()), $endTime->days, $endTime->h, $endTime->i], preg_filter('/^/', TextFormat::RESET, $listedItem));
			$lore = Settings::allowLore() ? [...$item->getLore(), ...$lore] : $lore;
			$item->setLore($lore);
			$this->getInventory()->setItem($index, $item);
		}
		parent::renderListings();
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot) : bool{
		if($slot <= 44 && isset($this->getListings()[$slot])){
			$listing = $this->getListings()[$slot];
			DataStorage::getInstance()->setExpired($listing, true, function() use ($itemClicked, $player, $listing, $slot, $inventory){
				$inventory->setItem($slot, VanillaItems::AIR());
				(new AuctionEndEvent($listing, AuctionEndEvent::CANCELLED, $player))->call();
			});
		}
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}
}
