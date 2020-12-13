<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu;

use DateTime;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\menu\admin\AdminMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;

class MainMenu extends AHMenu {

	public function __construct(Player $player, int $page = 1) {
		$this->setName(Locale::getMessage($player, "menu-name"));
		$this->page = $page;
		$this->pagination = true;
		parent::__construct($player, false, true);
	}

	public function setItems(int $page, int $max, int $total, int $selling, int $expiredNum): void {
		$chest = Utils::getButtonItem($this->getPlayer(), "stats", "main-stats");
		$chest->setLore(str_replace(["%page%", "%max%", "%total%"], [$page, $max, $total], $chest->getLore()))->getNamedTag()->setInt("pagination", 2);

		$info = Utils::getButtonItem($this->getPlayer(), "info", "main-description");
		$howto = Utils::getButtonItem($this->getPlayer(), "howto", "sell-description");

		$listings = Utils::getButtonItem($this->getPlayer(), "player_listings", "view-listed-items");
		$listings->setLore(str_replace("%selling%", $selling, $listings->getLore()))->getNamedTag()->setInt("listings", 1);

		$expired = Utils::getButtonItem($this->getPlayer(), "expired_listings", "view-expired-items");
		$expired->setLore(str_replace("%expired%", $expiredNum, $expired->getLore()))->getNamedTag()->setByte("expired", 1);

		$array = [49 => $chest, 45 => $listings, 46 => $expired, 52 => $howto, 53 => $info];

		if($this->getPlayer()->hasPermission("auctionhouse.command.admin")) {
			$admin = Utils::getButtonItem($this->getPlayer(), "admin_menu", "view-admin-menu");
			$admin->getNamedTag()->setByte("admin", 1);
			$admin->setNamedTagEntry(new ListTag("ench"));

			$array[47] = $admin; $array[51] = $admin;
		}

		foreach ($array as $slot => $item) $this->getInventory()->setItem($slot, $item);
	}

	public function renderItems(): void {
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
            return;
        }
        foreach($listings as $key => $auction) {
			$item = clone $auction->getItem();
			$endTime = (new DateTime())->diff((new DateTime())->setTimestamp($auction->getEndTime()));
			$tag = $item->hasCompoundTag() ? $item->getNamedTag() : new CompoundTag();
			$tag->setLong("marketId", $auction->getMarketId());

			$listedItem = Locale::getMessage($this->getPlayer(), "listed-item");
			$lore = str_replace(["%price%", "%seller%", "{H}", "{M}"], [$auction->getPrice(true, Settings::formatPrice()), $auction->getSeller(), ($endTime->days * 24 + $endTime->h),  $endTime->i], preg_filter('/^/', TextFormat::RESET, $listedItem));
			$lore = Settings::allowLore() ? array_merge($item->getLore(), $lore) : $lore;

			$item->setNamedTag($tag)->setCustomName(TextFormat::RESET . $item->getName())->setLore($lore);
			$this->getInventory()->setItem($key, $item);
		}
        for($i = count($listings); $i < 45; ++$i) {
            $this->getInventory()->setItem($i, Item::get(Item::AIR));
        }
		$this->setItems($this->page, $max, $total, count(DataHolder::getListingsByPlayer($this->getPlayer())), count(DataHolder::getListingsByPlayer($this->getPlayer(), true)));
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
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
		$this->checkPurchase($slot, $itemClicked);
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}

	public function show(Player $player): void {
		Utils::setViewingMenu($player, Utils::AUCTION_MENU);
		parent::show($player);
	}
}