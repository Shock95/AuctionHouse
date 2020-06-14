<?php
namespace shock95x\auctionhouse;

use shock95x\auctionhouse\menu\MenuHandler;
use shock95x\auctionhouse\utils\Pagination;
use muqsit\invmenu\inventory\InvMenuInventory;
use pocketmine\event\Listener;
use pocketmine\event\inventory\InventoryCloseEvent;


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
			Pagination::setPage($event->getPlayer(), 1);
			MenuHandler::setViewingMenu($event->getPlayer(), -1);
		}
	}
}