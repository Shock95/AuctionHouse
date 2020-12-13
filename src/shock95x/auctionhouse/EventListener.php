<?php
declare(strict_types=1);

namespace shock95x\auctionhouse;

use muqsit\invmenu\inventory\InvMenuInventory;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\event\MenuCloseEvent;
use shock95x\auctionhouse\menu\MainMenu;
use shock95x\auctionhouse\menu\player\PlayerListingMenu;
use shock95x\auctionhouse\utils\AHSign;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;

class EventListener implements Listener{

	/** @var AuctionHouse */
	private $plugin;

	/**
	 * EventListener constructor.
	 *
	 * @param AuctionHouse $plugin
	 */
	public function __construct(AuctionHouse $plugin) {
		$this->plugin = $plugin;
	}

	public function onInventoryClose(InventoryCloseEvent $event): void {
		$player = $event->getPlayer();
		if($event->getInventory() instanceof InvMenuInventory) {
			(new MenuCloseEvent($player, Utils::getViewingMenu($player)))->call();
			Utils::setViewingMenu($event->getPlayer(), -1);
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
			if($event->getLine(1) == "shop" && $player->hasPermission("auctionhouse.sign.shop")) {
				$event->setCancelled();

				$ahsign = new AHSign($block->getLevel(), AHSign::createTag($block, AHSign::TYPE_SHOP));
				$ahsign->setLine(0, "[" . TextFormat::GOLD . "AuctionHouse" . TextFormat::RESET . "]");
				$ahsign->setLine(2,  TextFormat::GREEN . "[Shop]");
				$tile->close();
				return;
			}
			if($player->hasPermission("auctionhouse.sign.listings")) {
				$event->setCancelled();

				$ahsign = new AHSign($block->getLevel(), AHSign::createTag($block, AHSign::TYPE_PLAYER, $player->getName()));
				$ahsign->setLine(0, "[" . TextFormat::GOLD . "AuctionHouse" . TextFormat::RESET . "]");
				$ahsign->setLine(2,  TextFormat::AQUA .  $player->getName());
				$tile->close();
			}
		}
	}

	public function onPlayerInteract(PlayerInteractEvent $event): void {
		$player = $event->getPlayer();
		$tile = $event->getPlayer()->getLevel()->getTile($event->getBlock());
		if($tile instanceof AHSign) {
			if($tile->getType() == AHSign::TYPE_SHOP) {
				new MainMenu($player);
			} else if($tile->getType() == AHSign::TYPE_PLAYER) {
				new PlayerListingMenu($player, $tile->getValue());
			}
		}
	}
}