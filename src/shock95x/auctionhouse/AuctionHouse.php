<?php
declare(strict_types=1);

namespace shock95x\auctionhouse;

use CortexPE\Commando\exception\HookAlreadyRegistered;
use CortexPE\Commando\PacketHooker;
use JackMD\UpdateNotifier\UpdateNotifier;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Tile;
use ReflectionException;
use shock95x\auctionhouse\category\CategoryManager;
use shock95x\auctionhouse\commands\AHCommand;
use shock95x\auctionhouse\database\Database;
use shock95x\auctionhouse\economy\EconomyProvider;
use shock95x\auctionhouse\economy\EconomySProvider;
use shock95x\auctionhouse\utils\AHSign;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;

class AuctionHouse extends PluginBase {

	public static ?AuctionHouse $instance;
	public ?EconomyProvider $economyProvider;
	private Database $database;

	private array $resources = ["statements/mysql.sql" => true, "statements/sqlite.sql" => true, "language/en_US.yml" => false, "language/ru_RU.yml" => false, "language/de_DE.yml" => false];

	public function onLoad(): void {
		$this->saveDefaultConfig();
		UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());
		Utils::checkConfig($this, $this->getConfig(), "config-version", 5);
	}

	/**
	 * @throws HookAlreadyRegistered
	 * @throws ReflectionException
	 */
	public function onEnable(): void {
		self::$instance = $this;
		$this->saveDefaultConfig();
		Settings::init($this->getConfig());

		foreach($this->resources as $file => $replace)
			$this->saveResource($file, $replace);

		Locale::init($this);
		CategoryManager::init();

		if(!InvMenuHandler::isRegistered()) InvMenuHandler::register($this);
		if(!PacketHooker::isRegistered()) PacketHooker::register($this);

		Tile::registerTile(AHSign::class, ["AHSign", "auctionhouse:sign"]);

		$plManager = $this->getServer()->getPluginManager();
		$this->database = (new Database($this->getConfig()))->connect();

		$plManager->registerEvents(new EventListener($this), $this);

		if($plManager->getPlugin(EconomySProvider::getName()) !== null) {
			$this->setEconomyProvider(new EconomySProvider());
		}
		if(!isset($this->economyProvider)) {
			$this->getLogger()->notice("No economy plugin has been found, disabling plugin...");
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		Settings::setMonetaryUnit($this->getEconomyProvider()->getMonetaryUnit());
		if($plManager->getPlugin("InvCrashFix") == null) {
			$this->getLogger()->warning("InvCrashFix is required to fix inventory issues on 1.16 and above, download it here: https://poggit.pmmp.io/ci/Muqsit/InvCrashFix");
		}
		$this->getServer()->getCommandMap()->register($this->getDescription()->getName(), new AHCommand($this, "ah", "AuctionHouse command"));
	}

	public function onDisable(): void {
		if(isset($this->database)) $this->database->close();
	}

	public static function getInstance(): self {
		return self::$instance;
	}

	public function reload(): void {
		Locale::init($this);
		Settings::init($this->getConfig(), true);
	}

	public function disable(): void {
		self::$instance = null;
		$this->getServer()->getPluginManager()->disablePlugin($this);
	}

	public function getDatabase(): Database {
		return $this->database;
	}

	public function setEconomyProvider(EconomyProvider $provider): void {
		$this->economyProvider = $provider;
	}

	public function getEconomyProvider(): ?EconomyProvider {
		return $this->economyProvider;
	}
}
