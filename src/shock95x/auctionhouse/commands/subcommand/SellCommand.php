<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\commands\subcommand;

use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\CortexPE\Commando\args\IntegerArgument;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\CortexPE\Commando\BaseSubCommand;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\CortexPE\Commando\constraint\InGameRequiredConstraint;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\CortexPE\Commando\exception\ArgumentOrderException;
use DateTime;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\Database;
use shock95x\auctionhouse\economy\EconomyProvider;
use shock95x\auctionhouse\event\ItemListedEvent;
use shock95x\auctionhouse\manager\CooldownManager;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\SOFe\AwaitGenerator\Await;

class SellCommand extends BaseSubCommand {

	/**
	 * @throws ArgumentOrderException
	 */
	protected function prepare(): void {
		$this->setPermission("auctionhouse.command.sell");
		$this->registerArgument(0, new IntegerArgument("price"));
		$this->addConstraint(new InGameRequiredConstraint($this));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		assert($sender instanceof Player);
		Await::f2c(function () use ($sender, $args) {
			/** @var Database $database */
			$database = $this->plugin->getDatabase();
			$item = $sender->getInventory()->getItemInHand();
			if($item->isNull()) {
				Locale::sendMessage($sender, "no-item");
				return;
			}
			if($sender->isCreative() && !Settings::allowCreativeSale()) {
				Locale::sendMessage($sender, "in-creative");
				return;
			}
			if(Utils::isBlacklisted($item)) {
				Locale::sendMessage($sender, "item-blacklisted");
				return;
			}
			if(!isset($args["price"]) || !is_numeric($args["price"])) {
				Locale::sendMessage($sender, "invalid-price");
				return;
			}
			$price = $args["price"];
			$listingCount = yield from Await::promise(fn($result) => $database->getActiveCountByPlayer($sender->getUniqueId(), $result));
			if($listingCount >= (Utils::getMaxListings($sender))) {
				$sender->sendMessage(str_ireplace(["{MAX}"], [Utils::getMaxListings($sender)], Locale::get($sender, "max-listings", true)));
				return;
			}
			if($price < Settings::getMinPrice() || ($price > Settings::getMaxPrice() && Settings::getMaxPrice() != -1)) {
				$sender->sendMessage(str_ireplace(["{MIN}", "{MAX}"], [Settings::getMinPrice(), Settings::getMaxPrice()], Locale::get($sender, "price-range", true)));
				return;
			}
			if(Settings::getListingCooldown() != 0) {
				if(CooldownManager::inCooldown($sender)) {
					$cooldown = CooldownManager::getCooldown($sender);
					$endTime = (new DateTime())->diff((new DateTime())->setTimestamp($cooldown));
					$sender->sendMessage(str_ireplace(["{M}", "{S}"], [$endTime->i, $endTime->s], Locale::get($sender, "in-cooldown", true)));
					return;
				}
				if(CooldownManager::setCooldown($sender)) {
					$uuid = $sender->getUniqueId();
					$this->getOwningPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($uuid) {
						CooldownManager::removeCooldown($uuid->toString());
					}), Settings::getListingCooldown() * 20);
				}
			}

			$listingPrice = Settings::getListingPrice();
			if($listingPrice > 0) {
				$subtractMoneyOk = yield from $this->getEconomy()->subtractMoneyAsync($sender, $listingPrice);
				if(!$subtractMoneyOk) {
					return;
				}
			}

			$event = new ItemListedEvent($sender, $item, $price);
			$event->call();
			if($event->isCancelled()) {
				// refund the listing price
				$addMoneyOk = yield from $this->getEconomy()->addMoneyAsync($sender, $listingPrice);
				if(!$addMoneyOk) {
					// TODO we failed to refund; what now?
				}

				return;
			}

			$count = $item->getCount();
			Utils::removeItem($sender, $item);
			/** @var AHListing $listing */
			$listing = yield from $database->createListingAsync($sender, $item->setCount($count), (int) $price);
			$sender->sendMessage(str_ireplace(["{PLAYER}", "{ITEM}", "{PRICE}", "{AMOUNT}"], [$sender->getName(), $item->getName(), $listing->getPrice(true, Settings::formatPrice()), $count], Locale::get($sender, "item-listed", true)));
		});
	}

	public function getEconomy(): ?EconomyProvider {
		/** @var AuctionHouse $plugin */
		$plugin = $this->getOwningPlugin();
		return $plugin->getEconomyProvider();
	}
}