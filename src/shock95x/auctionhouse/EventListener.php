<?php
declare(strict_types=1);

namespace shock95x\auctionhouse;

use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\event\MenuCloseEvent;
use shock95x\auctionhouse\manager\MenuManager;
use shock95x\auctionhouse\menu\player\PlayerListingMenu;
use shock95x\auctionhouse\menu\ShopMenu;
use shock95x\auctionhouse\utils\AHSign;
use shock95x\auctionhouse\utils\Settings;

class EventListener implements Listener {

	private AuctionHouse $plugin;

	public function __construct(AuctionHouse $plugin) {
		$this->plugin = $plugin;
	}

	public function onPlayerQuit(PlayerQuitEvent $event) {
		MenuManager::remove($event->getPlayer());
	}

	public function onInventoryClose(InventoryCloseEvent $event): void {
		$player = $event->getPlayer();
		if($event->getInventory() instanceof InvMenuInventory) {
			if(MenuManager::getViewingMenu($player) != -1) {
				(new MenuCloseEvent($player, MenuManager::getViewingMenu($player)))->call();
				MenuManager::setViewingMenu($event->getPlayer(), -1);
			}
		}
	}

	public function onSignChange(SignChangeEvent $event): void {
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$tile = $event->getPlayer()->getLevel()->getTile($block);
		if(!$tile instanceof Sign) {
			return;
		}
		if(in_array($event->getLine(0), Settings::getSignTriggers())) {
			if($player->hasPermission("auctionhouse.sign.listings") && $event->getLine(1) == "player") {
				$event->setCancelled();

				$ahsign = new AHSign($block->getLevel(), AHSign::createTag($block, AHSign::TYPE_PLAYER, $player->getName()));
				$ahsign->setLine(0, "[" . TextFormat::GOLD . "AuctionHouse" . TextFormat::RESET . "]");
				$ahsign->setLine(2,  TextFormat::AQUA .  $player->getName());
				$tile->close();
			} else if($player->hasPermission("auctionhouse.sign.shop")) {
				$lines = $event->getLines();
				$event->setCancelled();

				$ahsign = new AHSign($block->getLevel(), AHSign::createTag($block, AHSign::TYPE_SHOP));
				$ahsign->setLine(0, "[" . TextFormat::GOLD . "AuctionHouse" . TextFormat::RESET . "]");
				$ahsign->setLine(1, $lines[1]);
				$ahsign->setLine(2, $lines[2]);
				$ahsign->setLine(3, $lines[3]);
				$tile->close();
				return;
			}
		}
	}

	public function onPlayerInteract(PlayerInteractEvent $event): void {
		$player = $event->getPlayer();
		$tile = $event->getPlayer()->getLevel()->getTile($event->getBlock());
		if($tile instanceof AHSign) {
			if($tile->getType() == AHSign::TYPE_SHOP) {
				new ShopMenu($player);
			} else if($tile->getType() == AHSign::TYPE_PLAYER) {
				new PlayerListingMenu($player, $tile->getValue());
			}
		}
	}
}
