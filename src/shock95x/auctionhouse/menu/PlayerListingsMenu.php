<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu\player;

use DateTime;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\database\Database;
use shock95x\auctionhouse\menu\ConfirmPurchaseMenu;
use shock95x\auctionhouse\menu\type\PagingMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;
use SOFe\AwaitGenerator\Await;

class PlayerListingsMenu extends PagingMenu {

	private string $username;

	public function __construct(Player $player, string $username) {
		$this->username = $username;
		$this->setName(str_ireplace("{player}", $username, Locale::get($player, "player-listing")));
		parent::__construct($player);
	}

	protected function init(Database $database): void {
		Await::f2c(function () use ($database) {
			$this->setListings(yield from Await::promise(fn($resolve) => $database->getActiveListingsByUsername($resolve, $this->username, $this->getItemOffset())));
			$this->setTotalCount(yield from Await::promise(fn($resolve) => $database->getActiveCountByUsername($this->username, $resolve)));
			parent::init($database);
		});
	}

	public function renderButtons(): void {
		parent::renderButtons();
		$stats = Utils::getButtonItem($this->player, "stats", "listings-stats", ["{PAGE}", "{MAX}", "{TOTAL}"], [$this->getPage(), $this->getPageCount(), $this->getTotalCount()]);
		$this->getInventory()->setItem(49, $stats);
	}

	public function renderListings(): void {
        foreach($this->getListings() as $index => $listing) {
			$item = clone $listing->getItem();
			$endTime = (new DateTime())->diff((new DateTime())->setTimestamp($listing->getExpireTime()));

			$listedItem = Locale::get($this->player, "listed-item");
			$lore = str_ireplace(["{PRICE}", "{SELLER}", "{D}", "{H}", "{M}"], [$listing->getPrice(true, Settings::formatPrice()), $listing->getSeller(), $endTime->days, $endTime->h,  $endTime->i], preg_filter('/^/', TextFormat::RESET, $listedItem));
			$lore = Settings::allowLore() ? array_merge($item->getLore(), $lore) : $lore;
			$item->setLore($lore);

            $this->getInventory()->setItem($index, $item);
		}
		parent::renderListings();
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
		if(isset($this->getListings()[$slot])) {
			(new ConfirmPurchaseMenu($player, $this->getListings()[$slot]))->open();
			return true;
		}
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}
}