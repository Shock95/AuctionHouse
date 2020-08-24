<?php
namespace shock95x\auctionhouse\menu;

use DateTime;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\menu\admin\AdminMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;

class MainMenu extends AHMenu {

	public function __construct(Player $player, int $page = 1) {
		$this->setName(Locale::getMessage($player, "menu-name", true, false));
		$this->page = $page;
		$this->pagination = true;
		parent::__construct($player, false, true);
	}

	public function setItems(int $page, int $max, int $total, int $selling, int $expiredNum): void {
		$stats = Locale::getMessage($this->getPlayer(), "main-stats", true, false);
		$chest = Item::get($stats["id"])->setNamedTag(new CompoundTag("", [new IntTag("pagination", 2)]))->setCustomName((TextFormat::RESET . $stats["name"]))->setLore(str_replace(["%page%", "%max%", "%total%"], [$page, $max, $total], preg_filter('/^/', TextFormat::RESET, $stats["lore"])));

		$main = Locale::getMessage($this->getPlayer(), "main-description", true, false);
		$mainItem = Item::get($main["id"])->setCustomName(TextFormat::RESET . $main["name"])->setLore(preg_filter('/^/', TextFormat::RESET, $main["lore"]));

		$how = Locale::getMessage($this->getPlayer(), "sell-description", true, false);
		$howItem = Item::get($how["id"])->setCustomName(TextFormat::RESET . $how["name"])->setLore(preg_filter('/^/', TextFormat::RESET, $how["lore"]));

		$listings = Locale::getMessage($this->getPlayer(), "view-listed-items", true, false);
		$listingsItem = Item::get($listings["id"])->setNamedTag(new CompoundTag("", [new ByteTag("listings", 1)]))->setCustomName(TextFormat::RESET . $listings["name"])->setLore(str_replace("%selling%", $selling, preg_filter('/^/', TextFormat::RESET, $listings["lore"])));

		$expired = Locale::getMessage($this->getPlayer(), "view-expired-items", true, false);
		$expiredItem = Item::get($expired["id"])->setNamedTag(new CompoundTag("", [new ByteTag("expired", 1)]))->setCustomName(TextFormat::RESET . $expired["name"])->setLore(str_replace("%expired%", $expiredNum, preg_filter('/^/', TextFormat::RESET, $expired["lore"])));

		$array = [49 => $chest, 45 => $listingsItem, 46 => $expiredItem, 52 => $howItem, 53 => $mainItem];

		if($this->getPlayer()->hasPermission("auctionhouse.command.admin")) {
			$adminMenu = Locale::getMessage($this->getPlayer(), "view-admin-menu", true, false);
			$adminItem = Item::get($adminMenu["id"])->setNamedTag(new CompoundTag("", [new ByteTag("admin", 1), new ListTag("ench", [], NBT::TAG_Compound)]))->setCustomName(TextFormat::RESET . $adminMenu["name"])->setLore(preg_filter('/^/', TextFormat::RESET, $adminMenu["lore"]));
			$array[47] = $adminItem; $array[51] = $adminItem;
		}

		foreach ($array as $slot => $item) $this->getInventory()->setItem($slot, $item);
	}

	public function renderItems() {
        $total = count(DataHolder::getListings());
        $max = 0;
        for($i = 0; $i < $total; $i += 45) $max++;
        if($max == 0) $max = 1;

        $this->page < 1 ? $this->page = 1 : $this->page;
        $start = ($this->page - 1) * 45;
        $listings = array_slice(DataHolder::getListings(), $start, 45);

        if($this->page > $max) {
            $this->page = 1;
            $this->renderItems();
            return false;
        }
        foreach($listings as $key => $auction) {
			$item = clone $auction->getItem();
			$endTime = (new DateTime())->diff((new DateTime())->setTimestamp($auction->getEndTime()));
			$tag = $item->hasCompoundTag() ? $item->getNamedTag() : new CompoundTag();
			$tag->setLong("marketId", $auction->getMarketId());

			$listedItem = Locale::getMessage($this->getPlayer(), "listed-item", true, false);
			$lore = str_replace(["%price%", "%seller%", "%time%"], [$auction->getPrice(true, Settings::formatPrice()), $auction->getSeller(), ($endTime->days * 24 + $endTime->h) . ":" . $endTime->i], preg_filter('/^/', TextFormat::RESET, $listedItem));
			$lore = Settings::allowLore() ? array_merge($item->getLore(), $lore) : $lore;

			$item->setNamedTag($tag)->setCustomName(TextFormat::RESET . $item->getName())->setLore($lore);
			$this->getInventory()->setItem($key, $item);
		}
        for($i = count($listings); $i < 45; ++$i) {
            $this->getInventory()->setItem($i, Item::get(Item::AIR));
        }
		$this->setItems($this->page, $max, $total, count(DataHolder::getListingsByPlayer($this->getPlayer())), count(DataHolder::getListingsByPlayer($this->getPlayer(), true)));
		return true;
	}

	public function handle(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action) : bool {
		if($itemClicked->getNamedTag()->hasTag("listings")) {
			new ListingsMenu($this->getPlayer());
			return false;
		}
		if($itemClicked->getNamedTag()->hasTag("expired")) {
			new ExpiredMenu($this->getPlayer());
			return false;
		}
		if($itemClicked->getNamedTag()->hasTag("admin")) {
			new AdminMenu($this->getPlayer());
			return false;
		}
		if($action->getSlot() <= 44 && $itemClicked->getNamedTag()->hasTag("marketId")) {
			$player->removeWindow($action->getInventory());
			AuctionHouse::getInstance()->getScheduler()->scheduleDelayedTask(new class($player, clone $itemClicked) extends Task{
				private $player;
				private $item;

				public function __construct(Player $player, Item $item) {
					$this->player = $player;
					$this->item = $item;
				}

				public function onRun(int $currentTick) {
					new ConfirmPurchaseMenu($this->player, $this->item);
				}
			}, 10);
		}
		return parent::handle($player, $itemClicked, $itemClickedWith, $action);
	}

	public function show(Player $player) {
		Utils::setViewingMenu($player, Utils::AUCTION_MENU);
		parent::show($player);
	}
}