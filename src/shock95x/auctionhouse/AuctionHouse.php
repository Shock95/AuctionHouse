<?php
namespace shock95x\auctionhouse;

use CortexPE\Commando\PacketHooker;
use JackMD\UpdateNotifier\UpdateNotifier;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use shock95x\auctionhouse\commands\AHCommand;
use shock95x\auctionhouse\database\Database;
use shock95x\auctionhouse\economy\EconomyProvider;
use shock95x\auctionhouse\economy\EconomySProvider;
use shock95x\auctionhouse\utils\ConfigUpdater;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;

class AuctionHouse extends PluginBase {

	/** @var EconomyProvider */
	public $economyProvider;
	/** @var AuctionHouse */
	public static $instance;
	/** @var Database */
	private $database;

	public function onLoad() {
		$this->saveDefaultConfig();
		UpdateNotifier::checkUpdate($this, $this->getDescription()->getName(), $this->getDescription()->getVersion());
		ConfigUpdater::checkUpdate($this, $this->getConfig(), "config-version", 4);
	}

	public function onEnable() : void {
		self::$instance = $this;
		$this->saveDefaultConfig();
		Settings::init($this->getConfig());

		$resources = ["statements/mysql.sql" => true, "statements/sqlite.sql" => true, "language/en_US.yml" => false, "language/ru_RU.yml" => false, "language/de_DE.yml" => false];
		foreach ($resources as $file => $replace) {
			$this->saveResource($file, $replace);
		}

		$defaultLang = new Config($this->getDataFolder() . "language/en_US.yml", Config::YAML);
		ConfigUpdater::checkUpdate($this, $defaultLang, "lang-version", 2);

		Locale::init($this);

		if(!InvMenuHandler::isRegistered()) InvMenuHandler::register($this);
		if(!PacketHooker::isRegistered()) PacketHooker::register($this);

		$this->database = (new Database($this, $this->getConfig()))->connect();
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

		if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") !== null) {
			$this->setEconomyProvider(new EconomySProvider());
		}
		if(!isset($this->economyProvider)) {
			$this->getLogger()->notice("No economy plugin has been found, disabling plugin...");
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		Settings::setMonetaryUnit($this->getEconomyProvider()->getMonetaryUnit());
		if($this->getServer()->getPluginManager()->getPlugin("InvCrashFix") == null) {
			$this->getLogger()->warning("InvCrashFix is required to fix client crashes on 1.16, download it here: https://poggit.pmmp.io/ci/Muqsit/InvCrashFix");
		}
		$this->getServer()->getCommandMap()->register($this->getDescription()->getName(), new AHCommand($this, "ah", "AuctionHouse command"));
	}

	public function onDisable() {
		if(isset($this->database)) {
            $this->database->close();
        }
	}

	public function reload() {
		Locale::init($this);
		Settings::init($this->getConfig());
		$this->getLogger()->info("Configuration files reloaded");
	}

	public function disablePlugin() {
		self::$instance = null;
		$this->getServer()->getPluginManager()->disablePlugin($this);
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
}
