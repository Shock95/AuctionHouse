<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu;

use DateTime;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\inventory\Inventory;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\Database;
use shock95x\auctionhouse\menu\admin\AdminMenu;
use shock95x\auctionhouse\menu\type\PagingMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;
use SOFe\AwaitGenerator\Await;

class ShopMenu extends PagingMenu {

	const INDEX_LISTINGS = 45;
	const INDEX_EXPIRED = 46;
	const INDEX_STATS = 49;

	private int $sellingCount = 0;
	private int $expiredCount = 0;

	public function __construct(Player $player) {
		$this->setName(Locale::get($player, "menu-name"));
		parent::__construct($player);
	}

	protected function init(Database $database): void {
		Await::f2c(function() use ($database) {
			$this->setListings(yield from Await::promise(fn($resolve) => $database->getActiveListings($resolve, $this->getItemOffset())));
			$this->setTotalCount(yield from Await::promise(fn($resolve) => $database->getActiveListingsCount($resolve)));
			$this->sellingCount = yield from Await::promise(fn($resolve) => $database->getActiveCountByPlayer($this->player->getUniqueId(), $resolve));
			$this->expiredCount = yield from Await::promise(fn($resolve) => $database->getExpiredCountByPlayer($this->player->getUniqueId(), $resolve));
			parent::init($database);
		});
	}

	public function renderButtons(): void {
		parent::renderButtons();
		$stats = Utils::getButtonItem($this->player, "stats", "main-stats", ["{PAGE}", "{MAX}", "{TOTAL}"], [$this->getPage(), $this->getPageCount(), $this->getTotalCount()]);
		$howto = Utils::getButtonItem($this->player, "howto", "sell-description");
		$info = Utils::getButtonItem($this->player, "info", "main-description");
		$listings = Utils::getButtonItem($this->player, "player_listings", "view-listed-items", ["{SELLING}"], [$this->sellingCount]);
		$expired = Utils::getButtonItem($this->player, "expired_listings", "view-expired-items", ["{EXPIRED}"], [$this->expiredCount]);

		$this->getInventory()->setItem(self::INDEX_STATS, $stats);
		$this->getInventory()->setItem(self::INDEX_LISTINGS, $listings);
		$this->getInventory()->setItem(self::INDEX_EXPIRED, $expired);
		$this->getInventory()->setItem(52, $howto);
		$this->getInventory()->setItem(53, $info);

		if($this->player->hasPermission("auctionhouse.command.admin")) {
			$admin = Utils::getButtonItem($this->player, "admin_menu", "view-admin-menu");
			$admin->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(AuctionHouse::FAKE_ENCH_ID)));
			$this->getInventory()->setItem(47, $admin);
			$this->getInventory()->setItem(51, $admin);
		}
	}

	public function renderListings(): void {
		foreach($this->getListings() as $index => $listing) {
			$item = clone $listing->getItem();
			$endTime = (new DateTime())->diff((new DateTime())->setTimestamp($listing->getExpireTime()));

			$listedItem = Locale::get($this->player, "listed-item");
			$lore = str_ireplace(["{PRICE}", "{SELLER}", "{D}","{H}", "{M}"], [$listing->getPrice(true, Settings::formatPrice()), $listing->getSeller(), $endTime->days, $endTime->h,  $endTime->i], preg_filter('/^/', TextFormat::RESET, $listedItem));
			$lore = Settings::allowLore() ? [...$item->getLore(), ...$lore] : $lore;
			$item->setLore($lore);
			$this->getInventory()->setItem($index, $item);
		}
		parent::renderListings();
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
		switch ($slot) {
			case self::INDEX_LISTINGS:
				(new ListingsMenu($player))->setReturnMenu($this)->open();
				break;
			case self::INDEX_EXPIRED:
				(new ExpiredMenu($player))->setReturnMenu($this)->open();
				break;
			case 47: case 51:
				if($player->hasPermission("auctionhouse.command.admin")) {
					(new AdminMenu($player))->setReturnMenu($this)->open();
					break;
				}
			return true;
		}
		if(isset($this->getListings()[$slot])) {
			(new ConfirmPurchaseMenu($player, $this->getListings()[$slot]))->open();
			return true;
		}
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}
}