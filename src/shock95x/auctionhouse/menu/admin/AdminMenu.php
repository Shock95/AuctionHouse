<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu\admin;

use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\inventory\Inventory;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\storage\DataStorage;
use shock95x\auctionhouse\manager\MenuManager;
use shock95x\auctionhouse\menu\type\PagingMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;
use SOFe\AwaitGenerator\Await;

class AdminMenu extends PagingMenu {

	protected int $expired;
	protected int $total;

	public function __construct(Player $player, bool $returnMain = true) {
		$this->setName(Locale::get($player, "admin-menu-name"));
		parent::__construct($player, $returnMain);
	}

	protected function init(DataStorage $storage): void {
		Await::f2c(function () use ($storage) {
			$this->setListings(yield $storage->getListings(yield, (45 * $this->page) - 45) => Await::ONCE);
			$this->expired = yield $storage->getExpiredCount(yield) => Await::ONCE;
			$this->total = yield $storage->getListingCount(yield) => Await::ONCE;
			$this->pages = (int) ceil($this->total / 45);
		}, function () use ($storage) {
			parent::init($storage);
		});
	}
	
	public function renderButtons(): void {
		parent::renderButtons();
		$stats = Utils::getButtonItem($this->player, "stats", "main-stats-admin", ["%page%", "%max%", "%expired%", "%total%"], [$this->page, $this->pages, $this->expired, $this->total]);
		$stats->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(AuctionHouse::FAKE_ENCH_ID)));
		$this->getInventory()->setItem(self::INDEX_REFRESH, $stats);
	}
	
	public function renderListings(): void {
        foreach($this->getListings() as $index => $listing) {
			$item = clone $listing->getItem();
			$status =  Locale::get($this->player, $listing->isExpired() ? "status-expired" : "status-active");
			$listedItem = Locale::get($this->player, "listed-item-admin");
			$item->setLore(str_replace(["%price%", "%seller%", "%status%"], [$listing->getPrice(true, Settings::formatPrice()), $listing->getSeller(), $status], preg_filter('/^/', TextFormat::RESET, $listedItem)));

            $this->getInventory()->setItem($index, $item);
		}
        for($i = count($this->getListings()); $i < 45; ++$i) {
            $this->getInventory()->setItem($i, ItemFactory::air());
        }
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
		if($slot <= 44 && isset($this->getListings()[$slot])) {
			$listing = $this->getListings()[$slot];
			$player->removeCurrentWindow();
			self::open(new ManageListingMenu($player, $listing));
		}
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}
}