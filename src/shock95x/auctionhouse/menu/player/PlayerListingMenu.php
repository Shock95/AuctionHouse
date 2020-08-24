<?php
namespace shock95x\auctionhouse\menu\player;

use DateTime;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\menu\AHMenu;
use shock95x\auctionhouse\menu\ConfirmPurchaseMenu;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;

class PlayerListingMenu extends AHMenu {

	private $username;

	public function __construct(Player $player, string $username, int $page = 1) {
		$this->setName(str_replace("{player}", $username, Locale::getMessage($player, "player-listing", true, false)));
		$this->page = $page;
		$this->username = $username;
		parent::__construct($player, false, true);
	}

	public function setItems(int $page, int $max, int $total) : void {
		$stats = Locale::getMessage($this->getPlayer(), "listings-stats", true, false);
		$this->getInventory()->setItem(49, Item::get($stats["id"])->setCustomName(TextFormat::RESET . $stats["name"])->setLore(str_replace(["%page%", "%max%", "%total%"], [$page, $max, $total], preg_filter('/^/', TextFormat::RESET, $stats["lore"]))));
	}

	public function renderItems() {
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
        $this->setItems($this->page, $max, $total);
        return true;
	}

	public function handle(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action) : bool {
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
		Utils::setViewingMenu($player, Utils::PLAYER_LISTINGS_MENU);
		parent::show($player);
	}
}