<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\category;

use pocketmine\utils\RegistryTrait;
use shock95x\auctionhouse\category\defaults\ArmorCategory;
use shock95x\auctionhouse\category\defaults\BlockCategory;
use shock95x\auctionhouse\category\defaults\EnchantedCategory;
use shock95x\auctionhouse\category\defaults\FoodCategory;
use shock95x\auctionhouse\category\defaults\PotionCategory;
use shock95x\auctionhouse\category\defaults\ToolCategory;
use function strtolower;

class Category{

	use RegistryTrait;

	protected static function setup() : void{
		self::register(new BlockCategory());
		self::register(new ArmorCategory());
		self::register(new FoodCategory());
		self::register(new ToolCategory());
		self::register(new EnchantedCategory());
		self::register(new PotionCategory());
	}

	public static function register(ICategory $member) : void{
		self::_registryRegister($member->getName(), $member);
	}

	public static function get(string $name) : ICategory{
		/** @var ICategory $result */
		$result = self::_registryFromString(strtolower($name));
		return $result;
	}

	public static function getAll() : array{
		/**
		 * @var ICategory[]                      $result
		 * @phpstan-var array<string, ICategory> $result
		 */
		$result = self::_registryGetAll();
		return $result;
	}
}
