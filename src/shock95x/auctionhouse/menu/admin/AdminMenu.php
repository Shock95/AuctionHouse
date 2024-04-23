<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu\admin;

use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\inventory\Inventory;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\Database;
use shock95x\auctionhouse\database\Query;
use shock95x\auctionhouse\menu\type\PagingMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;
use SOFe\AwaitGenerator\Await;

class AdminMenu extends PagingMenu {

	protected int $expiredCount;

	const INDEX_RELIST_ALL = 52;
	const INDEX_RETURN_ALL = 53;

	public function __construct(Player $player) {
		$this->setName(Locale::get($player, "admin-menu-name"));
		parent::__construct($player);
	}

	protected function init(Database $database): void {
		Await::f2c(function () use ($database) {
			$this->setListings(yield from Await::promise(fn($resolve) => $database->getListings($resolve, (45 * $this->getPage()) - 45)));
			$this->expiredCount = yield from Await::promise(fn($resolve) => $database->getExpiredCount($resolve));
			$this->setTotalCount(yield from Await::promise(fn($resolve) => $database->getListingsCount($resolve)));
			parent::init($database);
		});
	}
	
	public function renderButtons(): void {
		parent::renderButtons();
		$fakeEnchant = new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(AuctionHouse::FAKE_ENCH_ID));

		$stats = Utils::getButtonItem($this->player, "stats", "main-stats-admin", ["{PAGE}", "{MAX}", "{EXPIRED}", "{TOTAL}"], [$this->getPage(), $this->getPageCount(), $this->expiredCount, $this->getTotalCount()])
			->addEnchantment($fakeEnchant);
		$relistALl = VanillaItems::EMERALD()->setCustomName(TextFormat::RESET . Locale::get($this->player, "relist-all"))
			->addEnchantment($fakeEnchant);
		$returnAll = VanillaItems::POISONOUS_POTATO()->setCustomName(TextFormat::RESET . Locale::get($this->player, "return-all"))
			->addEnchantment($fakeEnchant);

		$this->getInventory()->setItem(self::INDEX_REFRESH, $stats);
		$this->getInventory()->setItem(self::INDEX_RELIST_ALL, $relistALl);
		$this->getInventory()->setItem(self::INDEX_RETURN_ALL, $returnAll);
	}
	
	public function renderListings(): void {
        foreach($this->getListings() as $index => $listing) {
			$item = clone $listing->getItem();
			$status =  Locale::get($this->player, $listing->isExpired() ? "status-expired" : "status-active");
			$listedItem = Locale::get($this->player, "listed-item-admin");
			$item->setLore(str_ireplace(["{PRICE}", "{SELLER}", "{STATUS}"], [$listing->getPrice(true, Settings::formatPrice()), $listing->getSeller(), $status], preg_filter('/^/', TextFormat::RESET, $listedItem)));
            $this->getInventory()->setItem($index, $item);
		}
       	parent::renderListings();
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
		Await::f2c(function() use ($player, $slot){
			if($slot <= 44 && isset($this->getListings()[$slot])) {
				$listing = $this->getListings()[$slot];
				(new ManageListingMenu($player, $listing))->setReturnMenu($this)->open();
				return;
			}
			$database = AuctionHouse::getInstance()->getDatabase();
			switch($slot) {
				case self::INDEX_RELIST_ALL:
					$expireTime = Utils::getExpireTime(time());
					yield from $database->getConnector()->asyncChange(Query::RELIST_ALL, ["expires_at" => $expireTime]);
					$this->init($database);
					break;
				case self::INDEX_RETURN_ALL:
					yield from $database->getConnector()->asyncChange(Query::EXPIRE_ALL);
					$this->init($database);
					break;
			}
		});
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}
}