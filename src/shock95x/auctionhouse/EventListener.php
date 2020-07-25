<?php
namespace shock95x\auctionhouse;

use muqsit\invmenu\inventory\InvMenuInventory;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\Listener;
use shock95x\auctionhouse\utils\Utils;


class EventListener implements Listener{

	/** @var AuctionHouse */
	protected $loader;

	/**
	 * EventListener constructor.
	 *
	 * @param AuctionHouse $loader
	 */
	public function __construct(AuctionHouse $loader) {
		$this->loader = $loader;
	}

	public function onInventoryClose(InventoryCloseEvent $event) {
		if($event->getInventory() instanceof InvMenuInventory) {
			Utils::setViewingMenu($event->getPlayer(), -1);
		}
	}
}