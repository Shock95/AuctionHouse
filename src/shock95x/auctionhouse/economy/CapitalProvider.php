<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\economy;

use pocketmine\player\Player;
use shock95x\auctionhouse\utils\Settings;
use SOFe\Capital\{Capital, CapitalException, LabelSet};

final class CapitalProvider implements EconomyProvider {

	private array $selectors;

	public function __construct() {
		Capital::api("0.1.0", function(Capital $api) {
			$this->selectors = [
				EconomyProvider::USAGE_LISTING_PRICE => $api->completeConfig(Settings::getCapitalListingSelector()),
				EconomyProvider::USAGE_PURCHASE_PRICE => $api->completeConfig(Settings::getCapitalPurchaseSelector()),
				EconomyProvider::USAGE_SALES_PRICE => $api->completeConfig(Settings::getCapitalSalesSelector()),
			];
		});
	}

	public function addMoney(Player $player, float $amount, array $labels, int $usage, callable $callback): void {
		Capital::api("0.1.0", function(Capital $api) use($player, $amount, $labels, $usage, $callback) {
			try {
				yield from $api->addMoney(
					oracleName: "AuctionHouse",
					player: $player,
					schema: $this->selectors[$usage],
					amount: (int) $amount,
					transactionLabels: new LabelSet($labels),
				);
				$callback(true);
			} catch (CapitalException $e) {
				$callback(false);
			}
		});
	}

	public function subtractMoney(Player $player, float $amount, array $labels, int $usage, callable $callback): void {
		Capital::api("0.1.0", function(Capital $api) use($player, $amount, $labels, $usage, $callback) {
			try {
				yield from $api->takeMoney(
					oracleName: "AuctionHouse",
					player: $player,
					schema: $this->selectors[$usage],
					amount: (int) $amount,
					transactionLabels: new LabelSet($labels),
				);
				$callback(true);
			} catch (CapitalException $e) {
				$callback(false);
			}
		});
	}

	/**
	 * Get currency symbol of economy provider
	 * @return string
	 */
	public function getCurrencySymbol(): string {
		return ""; // user should fill the currency in the messages config
	}

	/**
	 * Get name of economy provider
	 * @return string
	 */
	public static function getName() : string {
		return "Capital";
	}
}
