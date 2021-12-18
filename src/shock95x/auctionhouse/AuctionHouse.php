<?php
declare(strict_types=1);

namespace shock95x\auctionhouse;

use CortexPE\Commando\exception\HookAlreadyRegistered;
use CortexPE\Commando\PacketHooker;
use JackMD\UpdateNotifier\UpdateNotifier;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\block\tile\TileFactory;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use shock95x\auctionhouse\commands\AHCommand;
use shock95x\auctionhouse\database\Database;
use shock95x\auctionhouse\database\legacy\LegacyConverter;
use shock95x\auctionhouse\economy\EconomyProvider;
use shock95x\auctionhouse\economy\EconomySProvider;
use shock95x\auctionhouse\task\CheckLegacyTask;
use shock95x\auctionhouse\tile\AHSign;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;

class AuctionHouse extends PluginBase {

	public static ?AuctionHouse $instance;
	private ?EconomyProvider $economyProvider;
	private Database $database;

	public const FAKE_ENCH_ID = -1;
	private const RESOURCES = ["statements/mysql.sql" => true, "statements/sqlite.sql" => true, "language/en_US.yml" => false, "language/ru_RU.yml" => false, "language/de_DE.yml" => false];

	public function onLoad(): void {
		$this->saveDefaultConfig();
		foreach(self::RESOURCES as $file => $replace) $this->saveResource($file, $replace);
		EnchantmentIdMap::getInstance()->register(self::FAKE_ENCH_ID, new Enchantment("", -1, 1, ItemFlags::ALL, ItemFlags::NONE));
		Utils::checkConfig($this, $this->getConfig(), "config-version", 5);
	}

	/**
	 * @throws HookAlreadyRegistered
	 */
	public function onEnable(): void {
		self::$instance = $this;
		Settings::init($this->getConfig());
		Locale::init($this);

		if(!InvMenuHandler::isRegistered()) InvMenuHandler::register($this);
		if(!PacketHooker::isRegistered()) PacketHooker::register($this);

		TileFactory::getInstance()->register(AHSign::class, ["AHSign", "auctionhouse:sign"]);
		EnchantmentIdMap::getInstance()->register(self::FAKE_ENCH_ID, new Enchantment("Glow", 1, ItemFlags::ALL, ItemFlags::NONE, 1));

		$pluginManager = $this->getServer()->getPluginManager();

		$this->database = new Database($this, $this->getConfig());
		$this->database->connect();

		LegacyConverter::getInstance()->init($this->database);

		$pluginManager->registerEvents(new EventListener($this), $this);

		if($pluginManager->getPlugin(EconomySProvider::getName()) !== null) {
			$this->setEconomyProvider(new EconomySProvider());
		}
		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() {
			if($this->economyProvider == null) {
				$this->getLogger()->notice("Could not detect an economy provider, disabling plugin...");
				$this->disable();
				return;
			}
			Settings::setMonetaryUnit($this->economyProvider->getMonetaryUnit());
		}), 1);
		$this->getServer()->getCommandMap()->register($this->getDescription()->getName(), new AHCommand($this, "ah", "AuctionHouse command"));
		UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());
		$this->getScheduler()->scheduleDelayedTask(new CheckLegacyTask($this), 1);
	}

	public function onDisable(): void {
		$this->database?->close();
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
