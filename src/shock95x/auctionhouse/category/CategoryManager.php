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

	private static array $categoryList;

	public static function register(Category $class): void {
		self::$categoryList[] = $class;
	}

	public static function getCategories(): array {
		return self::$categoryList;
	}

	public static function getCategoryByName(string $name): ?Category {
		foreach (self::getCategories() as $category) {
			if(strtolower($category->getName()) == $name) {
				return $category;
			}
		}
		return null;
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