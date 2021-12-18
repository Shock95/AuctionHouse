<?php
declare(strict_types=1);

namespace shock95x\auctionhouse;

use pocketmine\block\utils\SignText;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\menu\player\PlayerListingMenu;
use shock95x\auctionhouse\menu\ShopMenu;
use shock95x\auctionhouse\menu\type\AHMenu;
use shock95x\auctionhouse\tile\AHSign;
use shock95x\auctionhouse\utils\Settings;

class EventListener implements Listener {

	public function __construct(private AuctionHouse $plugin) {}

	public function onSignChange(SignChangeEvent $event): void {
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$text = $event->getNewText();
		$tile = $block->getPosition()->getWorld()->getTile($block->getPosition());
		if(in_array($text->getLine(0), Settings::getSignTriggers())) {
			if($player->hasPermission("auctionhouse.sign.listings") && $text->getLine(1) == "player") {
				$event->cancel();

				$sign = new AHSign($player->getWorld(), $block->getPosition());
				$sign->setType(AHSign::TYPE_PLAYER);
				$sign->setValue($player->getName());
				$sign->setText(new SignText(["[" . TextFormat::GOLD . "AuctionHouse" . TextFormat::RESET . "]", "", TextFormat::AQUA .  $player->getName()]));

				$tile->close();
				$block->getPosition()->getWorld()->addTile($sign);

			} else if($player->hasPermission("auctionhouse.sign.shop")) {
				$event->cancel();

				$sign = new AHSign($player->getWorld(), $block->getPosition());
				$sign->setType(AHSign::TYPE_SHOP);

				$sign->setText(new SignText(["[" . TextFormat::GOLD . "AuctionHouse" . TextFormat::RESET . "]", $text->getLine(1), $text->getLine(2), $text->getLine(3)]));

				$tile->close();
				$block->getPosition()->getWorld()->addTile($sign);
			}
		}
	}

	public function onPlayerInteract(PlayerInteractEvent $event): void {
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$tile = $block->getPosition()->getWorld()->getTile($block->getPosition());
		if($tile instanceof AHSign) {
			if($tile->getType() == AHSign::TYPE_SHOP) {
				AHMenu::open(new ShopMenu($player));
			} else if($tile->getType() == AHSign::TYPE_PLAYER) {
				AHMenu::open(new PlayerListingMenu($player, $tile->getValue()));
			}
		}
	}
}