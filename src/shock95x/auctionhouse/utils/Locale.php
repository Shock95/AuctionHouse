<?php

namespace shock95x\auctionhouse\utils;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use shock95x\auctionhouse\AuctionHouse;

class Locale {

	/** @var array */
	public static $translation;

	public static function init(AuctionHouse $plugin) {
		self::loadLanguages($plugin->getDataFolder());
		if(empty(self::$translation)) {
			$plugin->getLogger()->error("No language file has been found, disabling plugin...");
			$plugin->disablePlugin();
			return;
		}
		if(!isset(self::$translation[strtolower(Settings::getDefaultLang())])) {
			$plugin->getLogger()->error("Default language file could not be found, disabling plugin...");
			$plugin->disablePlugin();
			return;
		}
	}
	
	public static function loadLanguages(String $dataFolder) {
		foreach(glob($dataFolder . "language/*.yml") as $file) {
			$locale = new Config($file, Config::YAML);
			$localeCode = basename($file, ".yml");
			self::$translation[strtolower($localeCode)] = $locale->getAll();
			array_walk_recursive(self::$translation[strtolower($localeCode)], function (&$element) {
				$element = str_replace("&", "\xc2\xa7", $element);
			});
		}
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
	public static function getMessage(?Player $sender, $key, bool $return = false, $prefix = true) {
		$locale = Settings::getDefaultLang();
		if(isset(self::$translation[strtolower($sender->getLocale())])) {
			$locale = $sender->getLocale();
		}
		if(!isset(self::$translation[strtolower($locale)][$key])) {
			Server::getInstance()->getLogger()->warning("Key '" . $key . "' could not be found in the '" . $locale . "' language file, add this key to the language file or update the file by deleting it and restarting the server.");
			return false;
		}
		$message = $prefix ? Utils::prefixMessage(self::$translation[strtolower($locale)][$key]) : self::$translation[strtolower($locale)][$key];
		if($return) return $message;
		if($sender != null) $sender->sendMessage($message);
		return "";
	}
}