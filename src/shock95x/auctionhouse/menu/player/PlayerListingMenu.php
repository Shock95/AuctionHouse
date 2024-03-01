<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\menu\player;

use DateTime;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\database\storage\DataStorage;
use shock95x\auctionhouse\menu\type\PagingMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;
use SOFe\AwaitGenerator\Await;
use function array_merge;
use function ceil;
use function preg_filter;
use function str_ireplace;

class PlayerListingMenu extends PagingMenu{

	private int $total;
	private string $username;

	public function __construct(Player $player, string $username){
		$this->username = $username;
		$this->setName(str_ireplace("{player}", $username, Locale::get($player, "player-listing")));
		parent::__construct($player, true);
	}

	protected function init(DataStorage $storage) : void{
		Await::f2c(function() use ($storage){
			$this->setListings(yield from Await::promise(fn($resolve) => $storage->getActiveListingsByUsername($resolve, $this->username, (45 * $this->page) - 45)));
			$this->total = yield from Await::promise(fn($resolve) => $storage->getActiveCountByUsername($this->username, $resolve));
			$this->pages = (int) ceil($this->total / 45);
		}, fn() => parent::init($storage));
	}

	public function renderButtons() : void{
		$stats = Utils::getButtonItem($this->player, "stats", "listings-stats", ["{PAGE}", "{MAX}", "{TOTAL}"], [$this->page, $this->pages, $this->total]);
		$this->getInventory()->setItem(49, $stats);
	}

	public function renderListings() : void{
		foreach($this->getListings() as $index => $listing){
			$item = clone $listing->getItem();
			$endTime = (new DateTime())->diff((new DateTime())->setTimestamp($listing->getEndTime()));

			$listedItem = Locale::get($this->player, "listed-item");
			$lore = str_ireplace(["{PRICE}", "{SELLER}", "{D}", "{H}", "{M}"], [$listing->getPrice(true, Settings::formatPrice()), $listing->getSeller(), $endTime->days, $endTime->h, $endTime->i], preg_filter('/^/', TextFormat::RESET, $listedItem));
			$lore = Settings::allowLore() ? array_merge($item->getLore(), $lore) : $lore;
			$item->setLore($lore);

			$this->getInventory()->setItem($index, $item);
		}
		parent::renderListings();
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot) : bool{
		$this->openListing($slot);
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}
}
