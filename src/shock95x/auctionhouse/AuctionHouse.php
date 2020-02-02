<?php
namespace shock95x\auctionhouse;

use DateTime;
use Exception;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\menu\MenuHandler;
use shock95x\auctionhouse\database\Database;
use shock95x\auctionhouse\menu\MenuRenderer;
use shock95x\auctionhouse\utils\Pagination;
use shock95x\auctionhouse\utils\Settings;
use JackMD\ConfigUpdater\ConfigUpdater;
use JackMD\UpdateNotifier\UpdateNotifier;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\commands\AHCommand;
use shock95x\auctionhouse\economy\EconomyProvider;
use shock95x\auctionhouse\economy\EconomySProvider;
use shock95x\auctionhouse\utils\Utils;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;

class AuctionHouse extends PluginBase {

	/** @var EconomyProvider */
	public $economyProvider;
	/** @var AuctionHouse */
	public static $instance;
	/** @var Database */
	private $database;
	/** @var array */
	private $translation = [];
	/** @var MenuHandler */
	private $menuHandler;

	public function onLoad() {
		$this->saveDefaultConfig();
		UpdateNotifier::checkUpdate($this, $this->getDescription()->getName(), $this->getDescription()->getVersion());
		ConfigUpdater::checkUpdate($this, $this->getConfig(), "config-version", 2);
	}

	public function onEnable() : void {
		self::$instance = $this;
		$statements = [$this->getDataFolder() . "statements/", $this->getDataFolder() . "language/"];
		foreach ($statements as $directory) {
			if(!is_dir($directory)) mkdir($directory);
		}
		$this->saveDefaultConfig();
		$resources = ["statements/mysql.sql" => true, "statements/sqlite.sql" => true, "language/en_US.yml" => false];
		foreach ($resources as $file => $replace) {
			$this->saveResource($file, $replace);
		}
		foreach(glob($this->getDataFolder() . "language/*.yml") as $file) {
			$locale = new Config($file, Config::YAML);
			$localeCode = basename($file, ".yml");
			$this->translation[strtolower($localeCode)] = $locale->getAll();
			array_walk_recursive($this->translation[strtolower($localeCode)], function (&$element) {
				$element = str_replace("&", "\xc2\xa7", $element);
			});
		}
		if(empty($this->translation)) {
			$this->getServer()->getLogger()->error("No language file has been found, will now disable plugin");
			$this->getServer()->getPluginManager()->disablePlugin($this);
			self::$instance = null;
			return;
		}
		Settings::init($this->getConfig());

		if(!InvMenuHandler::isRegistered()) InvMenuHandler::register($this);
		$this->database = (new Database($this, $this->getConfig()))->connect();
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

		if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") !== null) {
			$this->setEconomyProvider(new EconomySProvider());
		} else {
			$this->economyProvider = null;
		}
		if($this->economyProvider == null) {
			$this->getLogger()->notice("No economy plugin has been found, will now disable.");
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		$this->getServer()->getCommandMap()->register($this->getDescription()->getName(), new AHCommand($this));
		$this->menuHandler = new MenuHandler($this);
	}

	public function onDisable() {
		$this->database->save();
	}

	/**
	 * @return AuctionHouse
	 */
	public static function getInstance() : AuctionHouse {
		return self::$instance;
	}

	/**
	 * @return Database
	 */
	public function getDatabase() : Database {
		return $this->database;
	}

	/**
	 * @param EconomyProvider $provider
	 */
	public function setEconomyProvider(EconomyProvider $provider) {
		$this->economyProvider = $provider;
	}

	/**
	 * @return EconomyProvider
	 */
	public function getEconomyProvider() : EconomyProvider {
		return $this->economyProvider;
	}

	/**
	 * Gets messages from lang files
	 *
	 * @param Player|null $sender
	 * @param $key
	 * @param bool $return
	 * @param bool $prefix
	 * @return mixed|string
	 */
	public function getMessage(?Player $sender, $key, bool $return = false, $prefix = true) {
		$locale = Settings::getDefaultLang();
		if(isset($this->translation[strtolower($sender->getLocale())])) {
			$locale = $sender->getLocale();
		} else if(isset($this->translation[strtolower(Settings::getDefaultLang())])) {
			$locale = Settings::getDefaultLang();
		}
		if(!isset($this->translation[strtolower($locale)][$key])) {
			$sender->sendMessage(Utils::prefixMessage("Key '" . $key . "' could not be found in the '" . $locale . "'' language file, please contact the server administrator."));
			return false;
		}
		$message = $prefix ? Utils::prefixMessage($this->translation[strtolower($locale)][$key]) : $this->translation[strtolower($locale)][$key];
		if($return) return $message;
		if($sender != null) $sender->sendMessage($message);
		return "";
	}

	/**
	 * Sends the Auction House menu to specified player
	 *
	 * @param Player $player
	 * @param Inventory $currentMenu
	 * @param int $n
	 * @return bool
	 * @throws Exception
	 */
	public function sendAHMenu(Player $player, $currentMenu = null, int $n = 1) {
		MenuHandler::setViewingMenu($player, MenuHandler::AUCTION_MENU);
		$list = DataHolder::getListings();
		if($n < 1) {
			$size = count($list);
            $n = $size / 45;
            if ($size % 45 > 0) {
            	++$n;
            }
        }
		$size2 = count($list);
        $n2 = ($n - 1) * 45;
        $n3 = ($n2 + 44 >= $size2) ? ($size2 - 1) : ($n * 45 - 1);
        if ($n3 - $n2 + 1 < 1 && $n != 1) {
        	$this->sendAHMenu($player, null, 1);
	        return false;
        }
        $n4 = 0;
        for($i = 0; $i < 45; ++$i) {
			if($currentMenu != null) $currentMenu->setItem($i, Item::get(Item::AIR));
        }
		$menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST)
			->readonly()
			->sessionize()
			->setListener([$this->menuHandler, "handleItemSelection"]);
		for($j = $n2; $j <= $n3; ++$j) {
			++$n4;
			if(!isset($list[$j])) return false;
			$auction = $list[$j];
			$item = clone $auction->getItem();

			$endTime = (new DateTime())->diff((new DateTime())->setTimestamp($auction->getEndTime()));
			$tag = $item->hasCompoundTag() ? $item->getNamedTag() : new CompoundTag();
			$tag->setLong("marketId", $auction->getMarketId());
			$item->setCompoundTag($tag)->setCustomName(TextFormat::RESET . $item->getName() . "\n" . TextFormat::GRAY . str_repeat("-", 25) . "\n" . TextFormat::GREEN . "Click here to purchase.\n\n" . TextFormat::BLUE . "Price: " . TextFormat::YELLOW . $this->economyProvider->getMonetaryUnit() . $auction->getPrice() . "\n" . TextFormat::BLUE . "Seller: " . TextFormat::YELLOW . $auction->getSeller() . "\n" . TextFormat::BLUE . "Expires: " . TextFormat::YELLOW . ($endTime->days * 24 + $endTime->h) . ":" . $endTime->i . "\n" . TextFormat::GRAY . str_repeat("-", 25));
			$menu->getInventory($player)->addItem($item);
		}
		Pagination::setPage($player, $n);
		$total = count($list);
		$max = 0;
		for($i = 0; $i < $total; $i += 45) $max++;
		if($max == 0) $max = 1;
		$p = Pagination::getPage($player);
		MenuRenderer::setAuctionItems($player, $menu->getInventory($player), $p, $max, $total, count((array) DataHolder::getListingsByPlayer($player)), count((array)DataHolder::getListingsByPlayer($player, true)));
		$menu->send($player, $this->getMessage($player, "menu-name", true, false));
		return true;
	}

	/**
	 * @param Player $player
	 * @param Inventory|null $currentMenu
	 * @param int $n
	 */
	public function sendExpired(Player $player, Inventory $currentMenu = null, int $n = 1) : void {
		MenuHandler::setViewingMenu($player, MenuHandler::EXPIRED_MENU);
		$list = DataHolder::getListingsByPlayer($player, true);
		if($n < 1) {
			$size = count($list);
			$n = $size / 45;
			if ($size % 45 > 0) ++$n;
		}
		$size2 = count($list);
		$n2 = ($n - 1) * 45;
		$n3 = ($n2 + 44 >= $size2) ? ($size2 - 1) : ($n * 45 - 1);
		if($n3 - $n2 + 1 < 1 && $n != 1) {
			$this->sendExpired($player, null, 1);
			return;
		}
		$n4 = 0;
		for($i = 0; $i < 45; ++$i) {
			if($currentMenu != null) $currentMenu->setItem($i, Item::get(Item::AIR));
		}
		$menu = InvMenu::createSessionized(InvMenu::TYPE_DOUBLE_CHEST)
			->readonly()
			->setListener([$this->menuHandler, "handleExpired"]);
		for($j = $n2; $j <= $n3; ++$j) {
			++$n4;
			if(!isset($list[$j])) return;
			$auction = $list[$j];
			$item = clone $auction->getItem();
			$tag = $item->hasCompoundTag() ? $item->getNamedTag() : new CompoundTag();
			$tag->setLong("marketId", $auction->getMarketId());
			$item->setCompoundTag($tag)->setCustomName(TextFormat::RESET . $item->getName() . "\n" . TextFormat::GRAY . str_repeat("-", 25) . "\n" . TextFormat::GREEN . "Click here to receive item.\n\n" . TextFormat::BLUE . "Price: " . TextFormat::YELLOW . $this->economyProvider->getMonetaryUnit() . $auction->getPrice() . "\n" . TextFormat::GRAY . str_repeat("-", 25));
			$menu->getInventory($player)->addItem($item);
		}
		Pagination::setPage($player, $n);
		$total = count($list);
		$max = 0;
		for($i = 0; $i < $total; $i += 45) $max++;
		if($max == 0) $max = 1;
		$p = Pagination::getPage($player);
		menu\MenuRenderer::setExpiredItems($player, $menu->getInventory($player), $p, $max, $total);
		$menu->send($player, $this->getMessage($player, "expired-menu-name", true, false));
	}

	/**
	 * @param Player $player
	 * @param Inventory|null $currentMenu
	 * @param int $n
	 */
	public function sendListings(Player $player, Inventory $currentMenu = null, int $n = 1) : void {
		MenuHandler::setViewingMenu($player, MenuHandler::LISTINGS_MENU);
		$list = DataHolder::getListingsByPlayer($player);
		if($n < 1) {
			$size = count($list);
			$n = $size / 45;
			if ($size % 45 > 0) ++$n;
		}
		$size2 = count($list);
		$n2 = ($n - 1) * 45;
		$n3 = ($n2 + 44 >= $size2) ? ($size2 - 1) : ($n * 45 - 1);
		if($n3 - $n2 + 1 < 1 && $n != 1) {
			$this->sendListings($player, null, 1);
			return;
		}
		$n4 = 0;

		for($i = 0; $i < 45; ++$i) {
			if($currentMenu != null) $currentMenu->setItem($i, Item::get(Item::AIR));
		}

		$menu = InvMenu::createSessionized(InvMenu::TYPE_DOUBLE_CHEST)
			->readonly()
			->setListener([$this->menuHandler, "handleListings"]);
		for($j = $n2; $j <= $n3; ++$j) {
			++$n4;
			if(!isset($list[$j])) return;
			$auction = $list[$j];
			$item = clone $auction->getItem();
			$tag = $item->hasCompoundTag() ? $item->getNamedTag() : new CompoundTag();
			$tag->setLong("marketId", $auction->getMarketId());
			$item->setCompoundTag($tag)->setCustomName(TextFormat::RESET . $item->getName() . "\n" . TextFormat::GRAY . str_repeat("-", 25) . "\n" . TextFormat::RED . "Click here to remove listing\n\n" . TextFormat::BLUE . "Price: " . TextFormat::YELLOW . $this->economyProvider->getMonetaryUnit() . $auction->getPrice() . "\n" . TextFormat::GRAY . str_repeat("-", 25));
			$menu->getInventory($player)->addItem($item);
		}
		Pagination::setPage($player, $n);
		$total = count($list);
		$max = 0;
		for($i = 0; $i < $total; $i += 45) $max++;
		if($max == 0) $max = 1;
		$p = Pagination::getPage($player);
		MenuRenderer::setListingItems($player, $menu->getInventory($player), $p, $max, $total);
		$menu->send($player, $this->getMessage($player, "listings-menu-name", true, false));
	}
}
