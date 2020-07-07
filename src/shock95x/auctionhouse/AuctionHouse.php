<?php
namespace shock95x\auctionhouse;

use DateTime;
use Exception;
use muqsit\invmenu\session\PlayerManager;
use shock95x\auctionhouse\database\DataHolder;
use shock95x\auctionhouse\menu\MenuHandler;
use shock95x\auctionhouse\database\Database;
use shock95x\auctionhouse\menu\MenuRenderer;
use shock95x\auctionhouse\utils\Pagination;
use shock95x\auctionhouse\utils\Settings;
use JackMD\UpdateNotifier\UpdateNotifier;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use shock95x\auctionhouse\commands\AHCommand;
use shock95x\auctionhouse\economy\EconomyProvider;
use shock95x\auctionhouse\economy\EconomySProvider;
use shock95x\auctionhouse\utils\ConfigUpdater;
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
		ConfigUpdater::checkUpdate($this, $this->getConfig(), "config-version", 3);
	}

	public function onEnable() : void {
		self::$instance = $this;
		$this->saveDefaultConfig();
		$statements = [$this->getDataFolder() . "statements/", $this->getDataFolder() . "language/"];
		foreach ($statements as $directory) {
			if(!is_dir($directory)) mkdir($directory);
		}

		$resources = ["statements/mysql.sql" => true, "statements/sqlite.sql" => true, "language/en_US.yml" => false, "language/ru_RU.yml" => false, "language/de_DE.yml" => false]; //todo
		foreach ($resources as $file => $replace) {
			$this->saveResource($file, $replace);
		}

		$defaultLang = new Config($this->getDataFolder() . "language/en_US.yml", Config::YAML);
		ConfigUpdater::checkUpdate($this, $defaultLang, "lang-version", 1);

		$this->loadLanguages();
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
		if($this->getServer()->getPluginManager()->getPlugin("InvCrashFix") == null) {
			$this->getLogger()->warning("InvCrashFix is required to fix client crashes on 1.16, download it here: https://poggit.pmmp.io/ci/Muqsit/InvCrashFix");
		}
		$this->getServer()->getCommandMap()->register($this->getDescription()->getName(), new AHCommand($this));
		$this->menuHandler = new MenuHandler($this);
	}

	public function onDisable() {
		$this->database->save();
		$this->database->close();
	}

	public function loadLanguages() {
		foreach(glob($this->getDataFolder() . "language/*.yml") as $file) {
			$locale = new Config($file, Config::YAML);
			$localeCode = basename($file, ".yml");
			$this->translation[strtolower($localeCode)] = $locale->getAll();
			array_walk_recursive($this->translation[strtolower($localeCode)], function (&$element) {
				$element = str_replace("&", "\xc2\xa7", $element);
			});
		}
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
			$this->getLogger()->warning("Key '" . $key . "' could not be found in the '" . $locale . "' language file, add this key to the language file or update the file by deleting it and restarting the server.");
			//$sender->sendMessage(Utils::prefixMessage("Key '" . $key . "' could not be found in the '" . $locale . "'' language file, please contact the server administrator."));
			return false;
		}
		$message = $prefix ? Utils::prefixMessage($this->translation[strtolower($locale)][$key]) : $this->translation[strtolower($locale)][$key];
		if($return) return $message;
		if($sender != null) $sender->sendMessage($message);
		return "";
	}

	public function sendAHMenu(Player $player, int $n = 1) {
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
			$this->sendAHMenu($player, 1);
			return false;
		}
		$n4 = 0;

		$menu = null;
		$newMenu = false;
		if(PlayerManager::get($player)->getCurrentMenu() == null) {
			$menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST)->readonly();
			$newMenu = true;
		} else {
			$menu = PlayerManager::get($player)->getCurrentMenu();
			$menu->getInventoryForPlayer($player)->clearAll();
		}
		$menu->setListener([$this->menuHandler, "handleItemSelection"]);
		$inventory = $menu->getInventoryForPlayer($player);

		for($j = $n2; $j <= $n3; ++$j) {
			++$n4;
			if(!isset($list[$j])) return false;
			$auction = $list[$j];
			$item = clone $auction->getItem();
			$endTime = (new DateTime())->diff((new DateTime())->setTimestamp($auction->getEndTime()));
			$tag = $item->hasCompoundTag() ? $item->getNamedTag() : new CompoundTag();
			$tag->setLong("marketId", $auction->getMarketId());

			$listedItem = $this->getMessage($player, "listed-item", true, false);
			$item->setCompoundTag($tag)->setCustomName(TextFormat::RESET . $item->getName())->setLore(str_replace(["%price%", "%seller%", "%time%"], [$auction->getPrice(true), $auction->getSeller(), ($endTime->days * 24 + $endTime->h) . ":" . $endTime->i], preg_filter('/^/', TextFormat::RESET, $listedItem)));

			$inventory->addItem($item);
		}
		Pagination::setPage($player, $n);
		$total = count($list);
		$max = 0;
		for($i = 0; $i < $total; $i += 45) $max++;
		if($max == 0) $max = 1;
		$p = Pagination::getPage($player);
		MenuRenderer::setAuctionItems($player, $inventory, $p, $max, $total, count((array) DataHolder::getListingsByPlayer($player)), count((array)DataHolder::getListingsByPlayer($player, true)));

		if($newMenu) $menu->send($player, $this->getMessage($player, "menu-name", true, false));
		return true;
	}

	/**
	 * @param Player $player
	 * @param int $n
	 */
	public function sendExpired(Player $player, int $n = 1) : void {
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
			$this->sendExpired($player, 1);
			return;
		}
		$n4 = 0;

		$menu = null;
		$newMenu = false;
		if(PlayerManager::get($player)->getCurrentMenu() == null) {
			$menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST)->readonly();
			$newMenu = true;
		} else {
			$menu = PlayerManager::get($player)->getCurrentMenu();
			$menu->getInventoryForPlayer($player)->clearAll();
		}

		$menu->setListener([$this->menuHandler, "handleExpired"]);
		$inventory = $menu->getInventoryForPlayer($player);
		for($j = $n2; $j <= $n3; ++$j) {
			++$n4;
			if(!isset($list[$j])) return;
			$auction = $list[$j];
			$item = clone $auction->getItem();
			$tag = $item->hasCompoundTag() ? $item->getNamedTag() : new CompoundTag();
			$tag->setLong("marketId", $auction->getMarketId());

			$expiredItem = $this->getMessage($player, "expired-item", true, false);
			$item->setCompoundTag($tag)->setCustomName(TextFormat::RESET . $item->getName())->setLore(str_replace(["%price%"], [$auction->getPrice(true)], preg_filter('/^/', TextFormat::RESET, $expiredItem)));

			$inventory->addItem($item);
		}
		Pagination::setPage($player, $n);
		$total = count($list);
		$max = 0;
		for($i = 0; $i < $total; $i += 45) $max++;
		if($max == 0) $max = 1;
		$p = Pagination::getPage($player);
		MenuRenderer::setExpiredItems($player, $inventory, $p, $max, $total);

		if($newMenu) $menu->send($player, $this->getMessage($player, "expired-menu-name", true, false));
	}

	/**
	 * @param Player $player
	 * @param int $n
	 * @throws Exception
	 */
	public function sendListings(Player $player,  int $n = 1) : void {
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
			$this->sendListings($player,  1);
			return;
		}
		$n4 = 0;

		$menu = null;
		$newMenu = false;
		if(PlayerManager::get($player)->getCurrentMenu() == null) {
			$menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST)->readonly();
			$newMenu = true;
		} else {
			$menu = PlayerManager::get($player)->getCurrentMenu();
			$menu->getInventoryForPlayer($player)->clearAll();
			/*for($i = 0; $i < 45; ++$i) {
				$menu->getInventory($player)>setItem($i, Item::get(Item::AIR));
			}*/
		}
		$menu->setListener([$this->menuHandler, "handleListings"]);
		$inventory = $menu->getInventoryForPlayer($player);
		
		for($j = $n2; $j <= $n3; ++$j) {
			++$n4;
			if(!isset($list[$j])) return;
			$auction = $list[$j];
			$item = clone $auction->getItem();
			$tag = $item->hasCompoundTag() ? $item->getNamedTag() : new CompoundTag();
			$tag->setLong("marketId", $auction->getMarketId());

			$endTime = (new DateTime())->diff((new DateTime())->setTimestamp($auction->getEndTime()));

			$listedItem = $this->getMessage($player, "your-listed-item", true, false);
			$item->setCompoundTag($tag)->setCustomName(TextFormat::RESET . $item->getName())->setLore(str_replace(["%price%", "%time%"], [$auction->getPrice(true), ($endTime->days * 24 + $endTime->h) . ":" . $endTime->i], preg_filter('/^/', TextFormat::RESET, $listedItem)));

			$inventory->addItem($item);
		}
		Pagination::setPage($player, $n);
		$total = count($list);
		$max = 0;
		for($i = 0; $i < $total; $i += 45) $max++;
		if($max == 0) $max = 1;
		$p = Pagination::getPage($player);
		MenuRenderer::setListingItems($player, $inventory, $p, $max, $total);
		if($newMenu) $menu->send($player, $this->getMessage($player, "listings-menu-name", true, false));
	}

	/*protected function getPlayerMenu(Player $player) : InvMenu {
	}*/
}
