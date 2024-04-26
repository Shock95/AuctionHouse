<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\menu\type;

use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\muqsit\invmenu\InvMenu;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\muqsit\invmenu\InvMenuHandler;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\player\Player;
use pocketmine\world\sound\ClickSound;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\Database;
use shock95x\auctionhouse\event\MenuCloseEvent;

abstract class AHMenu extends InvMenu {

	/** @var AHListing[] */
	protected array $listings = [];

	protected ?Player $player;
	protected ?AHMenu $returnMenu = null;

	protected static string $inventoryType = InvMenu::TYPE_DOUBLE_CHEST;

	public function __construct(Player $player) {
		parent::__construct(InvMenuHandler::getTypeRegistry()->get(static::$inventoryType));
		$this->player = $player;
		$this->setListener(InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) use ($player) {
			$player->getWorld()->addSound($player->getPosition(), new ClickSound(), [$player]);
			$this->handle($transaction->getPlayer(), $transaction->getItemClicked(), $transaction->getAction()->getInventory(), $transaction->getAction()->getSlot());
		}));
	}

	protected function init(Database $database): void {
		$this->renderButtons();
		$this->renderListings();
	}

	abstract function renderButtons();
	abstract function renderListings();

	public function setReturnMenu(?AHMenu $returnMenu): self {
		$this->returnMenu = $returnMenu;
		return $this;
	}

	/**
	 * @param AHListing[] $listings
	 */
	protected function setListings(array $listings): void {
		$this->listings = $listings;
	}

	/**
	 * @return AHListing[]
	 */
	public function getListings(): array {
		return $this->listings;
	}

	public function open(): void {
		$this->init(AuctionHouse::getInstance()->getDatabase());
		$this->send($this->player);
	}

	public function onClose(Player $player): void {
		(new MenuCloseEvent($player, $this))->call();
		parent::onClose($player);
	}
}