<?php

namespace shock95x\auctionhouse\menu\admin;

use pocketmine\block\VanillaBlocks;
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
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\SOFe\AwaitGenerator\Await;

class AdminListingsMenu extends PagingMenu {

	private string $username;
	protected int $expiredCount;

	const INDEX_RELIST_ALL = 51;
	const INDEX_RETURN_ALL = 52;
	const INDEX_DELETE_ALL = 53;

	public function __construct(Player $player, string $username) {
		$this->username = $username;
		$this->setName(str_ireplace("{player}", $username, Locale::get($player, "player-listing")));
		parent::__construct($player);
	}

	protected function init(Database $database): void {
		Await::f2c(function () use ($database) {
			$this->setListings(yield from Await::promise(fn($resolve) => $database->getListingsByUsername($resolve, $this->username, (45 * $this->getPage()) - 45)));
			$this->expiredCount = yield from Await::promise(fn($resolve) => $database->getExpiredCountByUsername($this->username, $resolve));
			$this->setTotalCount(yield from Await::promise(fn($resolve) => $database->getListingsCountByUsername($this->username, $resolve)));
			parent::init($database);
		});
	}

	public function renderButtons(): void {
		parent::renderButtons();
		$fakeEnchant = new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(AuctionHouse::FAKE_ENCH_ID));

		$stats = Utils::getButtonItem($this->player, "stats", "main-stats-admin", ["{PAGE}", "{MAX}", "{EXPIRED}", "{TOTAL}"], [$this->getPage(), $this->getPageCount(), $this->expiredCount, $this->getTotalCount()]);
		$deleteAll = VanillaBlocks::BARRIER()->asItem()->setCustomName(TextFormat::RESET . Locale::get($this->player, "delete-all"))
			->addEnchantment($fakeEnchant);
		$relistALl = VanillaItems::EMERALD()->setCustomName(TextFormat::RESET . Locale::get($this->player, "relist-all"))
			->addEnchantment($fakeEnchant);
		$returnAll = VanillaItems::POISONOUS_POTATO()->setCustomName(TextFormat::RESET . Locale::get($this->player, "return-all"))
			->addEnchantment($fakeEnchant);

		$this->getInventory()->setItem(self::INDEX_REFRESH, $stats);
		$this->getInventory()->setItem(self::INDEX_DELETE_ALL, $deleteAll);
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
				(new ManageListingMenu($player, $listing))->open();
				return;
			}
			$database = AuctionHouse::getInstance()->getDatabase();
			switch($slot) {
				case self::INDEX_RELIST_ALL:
					$expireTime = Utils::getExpireTime(time());
					yield from $database->getConnector()->asyncChange(Query::RELIST_USERNAME, ["username" => $this->username, "expires_at" => $expireTime]);
					$this->init($database);
					break;
				case self::INDEX_RETURN_ALL:
					yield from $database->getConnector()->asyncChange(Query::EXPIRE_USERNAME, ["username" => $this->username]);
					$this->init($database);
					break;
				case self::INDEX_DELETE_ALL:
					yield from $database->getConnector()->asyncChange(Query::DELETE_USERNAME, ["username" => $this->username]);
					$this->init($database);
					break;
			}
		});
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}
}