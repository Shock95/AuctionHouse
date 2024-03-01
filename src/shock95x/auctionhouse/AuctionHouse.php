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
use pocketmine\utils\SingletonTrait;
use shock95x\auctionhouse\commands\AHCommand;
use shock95x\auctionhouse\database\Database;
use shock95x\auctionhouse\database\legacy\LegacyConverter;
use shock95x\auctionhouse\economy\BedrockEconomyProvider;
use shock95x\auctionhouse\economy\EconomyProvider;
use shock95x\auctionhouse\economy\EconomySProvider;
use shock95x\auctionhouse\task\CheckLegacyTask;
use shock95x\auctionhouse\tile\AHSign;
use shock95x\auctionhouse\utils\Locale;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;

class AuctionHouse extends PluginBase{

	use SingletonTrait;

	private Database $database;
	private ?EconomyProvider $economyProvider = null;

	public const FAKE_ENCH_ID = -1;
	private const RESOURCES = ["statements/mysql.sql" => true, "statements/sqlite.sql" => true, "language/en_US.yml" => false, "language/ru_RU.yml" => false, "language/de_DE.yml" => false];

	public function onLoad() : void{
		self::setInstance($this);
		$this->saveDefaultConfig();
		foreach(self::RESOURCES as $file => $r) $this->saveResource($file, $r);
		EnchantmentIdMap::getInstance()->register(self::FAKE_ENCH_ID, new Enchantment("", -1, 1, ItemFlags::ALL, ItemFlags::NONE));
		Utils::checkConfig($this, $this->getConfig(), "config-version", 5);
	}

	/**
	 * @throws HookAlreadyRegistered
	 */
	public function onEnable() : void{
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

		$pluginManager->registerEvents(new EventListener(), $this);

		if($pluginManager->getPlugin(EconomySProvider::getName()) !== null){
			$this->setEconomyProvider(new EconomySProvider());
		}elseif($pluginManager->getPlugin(BedrockEconomyProvider::getName()) !== null){
			$this->setEconomyProvider(new BedrockEconomyProvider());
		}
		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function(){
			if($this->economyProvider == null){
				$this->getLogger()->notice("No economy provider found, disabling plugin...");
				$this->disable();
				return;
			}
			Settings::setCurrencySymbol($this->economyProvider->getCurrencySymbol());
		}), 1);
		UpdateNotifier::checkUpdate($this->getDescription()->getName(), $this->getDescription()->getVersion());
		$this->getServer()->getCommandMap()->register($this->getDescription()->getName(), new AHCommand($this, "ah", "AuctionHouse command"));
		$this->getScheduler()->scheduleDelayedTask(new CheckLegacyTask($this), 1);
	}

	public function onDisable() : void{
		self::reset();
		$this->database?->close();
	}

	public function reload() : void{
		Locale::init($this);
		Settings::init($this->getConfig(), true);
	}

	public function disable() : void{
		$this->getServer()->getPluginManager()->disablePlugin($this);
	}

	public function getDatabase() : Database{
		return $this->database;
	}

	public function setEconomyProvider(EconomyProvider $provider) : void{
		$this->economyProvider = $provider;
	}

	public function getEconomyProvider() : ?EconomyProvider{
		return $this->economyProvider;
	}
}
