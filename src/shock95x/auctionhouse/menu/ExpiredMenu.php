<?php
namespace shock95x\auctionhouse\menu;

use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;

class ExpiredMenu extends AHMenu {

	public function __construct(Player $player, bool $returnMain = true, int $page = 1) {
		$this->setName(Locale::getMessage($player, "expired-menu-name", true, false));
		$this->page = $page;
		parent::__construct($player, $returnMain, true);
	}

	public function setItems(int $page, int $max, int $total) : void {
		$description = Locale::getMessage($this->getPlayer(), "expired-description", true, false);
		$stats = Locale::getMessage($this->getPlayer(), "expired-stats", true, false);
		$this->getInventory()->setItem(53, Item::get($description["id"])->setCustomName(TextFormat::RESET . $description["name"])->setLore(preg_filter('/^/', TextFormat::RESET, $description["lore"])));
		$this->getInventory()->setItem(49, Item::get($stats["id"])->setNamedTag(new CompoundTag("", [new ByteTag("getAll", 1)]))->setCustomName(TextFormat::RESET . $stats["name"])->setLore(str_replace(["%page%", "%max%", "%total%"], [$page, $max, $total], preg_filter('/^/', TextFormat::RESET, $stats["lore"]))));
	}

	public function renderItems() {
        $total = count(DataHolder::getListingsByPlayer($this->getPlayer(), true));
        $max = 0;
        for($i = 0; $i < $total; $i += 45) $max++;
        if($max == 0) $max = 1;

        $this->page < 1 ? $this->page = 1 : $this->page;
        $start = ($this->page - 1) * 45;
        $listings = array_slice( DataHolder::getListingsByPlayer($this->getPlayer(), true), $start, 45);

        if($this->page > $max) {
            $this->page = 1;
            $this->renderItems();
            return false;
        }
        foreach($listings as $key => $auction) {
			$item = clone $auction->getItem();
			$tag = $item->hasCompoundTag() ? $item->getNamedTag() : new CompoundTag();
			$tag->setLong("marketId", $auction->getMarketId());

			$expiredItem = Locale::getMessage($this->getPlayer(), "expired-item", true, false);

			$lore = str_replace(["%price%"], [$auction->getPrice(true, Settings::formatPrice())], preg_filter('/^/', TextFormat::RESET, $expiredItem));
			$lore = Settings::allowLore() ? array_merge($item->getLore(), $lore) : $lore;
			$item->setNamedTag($tag)->setCustomName(
				TextFormat::RESET . $item->getName())->setLore($lore);

            $this->getInventory()->setItem($key, $item);
        }
        for($i = count($listings); $i < 45; ++$i) {
            $this->getInventory()->setItem($i, Item::get(Item::AIR));
        }
		$this->setItems($this->page, $max, $total);
		return true;
	}

	public function handle(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action) : bool {
		$inventory = $action->getInventory();
		if($action->getSlot() <= 44 && $itemClicked->getNamedTag()->hasTag("marketId") && $itemClicked->getId() !== Item::AIR) {
			$marketId = $itemClicked->getNamedTag()->getLong("marketId");
			$auction = DataHolder::getListingById($marketId);
			if($auction == null || $auction->getSeller(true) != $player->getRawUniqueId()) {
				return false;
			}
			$item = $auction->getItem();
			if($player->getInventory()->canAddItem($item) && $auction->getSeller(true) == $player->getRawUniqueId()) {
				DataHolder::removeAuction($auction);
				$inventory->setItem($action->getSlot(), Item::get(Item::AIR));
				$player->getInventory()->addItem($item);
				$player->sendMessage(str_replace(["@item", "@amount"], [$item->getName(), $item->getCount()], Locale::getMessage($player, "returned-item", true)));
			}
		}
		return parent::handle($player, $itemClicked, $itemClickedWith, $action);
	}

	public function show(Player $player) {
		Utils::setViewingMenu($player, Utils::EXPIRED_MENU);
		parent::show($player);
	}
}