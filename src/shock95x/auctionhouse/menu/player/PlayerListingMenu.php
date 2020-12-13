<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu\player;

use DateTime;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\menu\AHMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;

class PlayerListingMenu extends AHMenu {

	private $username;

	public function __construct(Player $player, string $username, int $page = 1) {
		$this->setName(str_replace("{player}", $username, Locale::getMessage($player, "player-listing")));
		$this->page = $page;
		$this->username = $username;
		parent::__construct($player, false, true);
	}

	public function setItems(int $page, int $max, int $total) : void {
		$stats = Utils::getButtonItem($this->getPlayer(), "stats", "listings-stats");
		$stats->setLore(str_replace(["%page%", "%max%", "%total%"], [$page, $max, $total], $stats->getLore()));
		$this->getInventory()->setItem(49, $stats);
	}

	public function renderItems(): void {
        $total = count(DataHolder::getListingsByUsername($this->username));
        $max = 0;
        for($i = 0; $i < $total; $i += 45) $max++;
        if($max == 0) $max = 1;

        $this->page < 1 ? $this->page = 1 : $this->page;
        $start = ($this->page - 1) * 45;
        $listings = array_slice(DataHolder::getListingsByUsername($this->username), $start, 45);

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
			$lore = str_replace(["%price%", "%seller%", "{H}", "{M}"], [$auction->getPrice(true, Settings::formatPrice()), $auction->getSeller(), ($endTime->days * 24 + $endTime->h), $endTime->i], preg_filter('/^/', TextFormat::RESET, $listedItem));
			$lore = Settings::allowLore() ? array_merge($item->getLore(), $lore) : $lore;

			$item->setNamedTag($tag)->setCustomName(TextFormat::RESET . $item->getName())->setLore($lore);
            $this->getInventory()->setItem($key, $item);
		}
        for($i = count($listings); $i < 45; ++$i) {
            $this->getInventory()->setItem($i, Item::get(Item::AIR));
        }
        $this->setItems($this->page, $max, $total);
	}

	public function handle(Player $player, Item $itemClicked, Inventory $inventory, int $slot): bool {
		$this->checkPurchase($slot, $itemClicked);
		return parent::handle($player, $itemClicked, $inventory, $slot);
	}

	public function show(Player $player): void {
		Utils::setViewingMenu($player, Utils::PLAYER_LISTINGS_MENU);
		parent::show($player);
	}
}