<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\category;

use shock95x\auctionhouse\category\defaults\ArmorCategory;
use shock95x\auctionhouse\category\defaults\BlockCategory;
use shock95x\auctionhouse\category\defaults\EnchantedCategory;
use shock95x\auctionhouse\category\defaults\FoodCategory;
use shock95x\auctionhouse\category\defaults\PotionCategory;
use shock95x\auctionhouse\category\defaults\ToolCategory;

class CategoryManager {

	/** @var array */
	private static $categories;

	public static function register(Category $class): void {
		self::$categories[] = $class;
	}

	public static function getCategories(): array {
		return self::$categories;
	}

	public static function init(): void {
		self::register(new BlockCategory());
		self::register(new ArmorCategory());
		self::register(new FoodCategory());
		self::register(new ToolCategory());
		self::register(new EnchantedCategory());
		self::register(new PotionCategory());
	}
}