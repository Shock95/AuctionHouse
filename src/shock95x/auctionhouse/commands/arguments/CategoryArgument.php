<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\commands\arguments;

use CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;
use shock95x\auctionhouse\category\CategoryManager;

class CategoryArgument extends StringEnumArgument {

	public function getEnumValues(): array {
		$values = [];
		foreach (CategoryManager::getCategories() as $category) {
			$values[] = strtolower($category->getName());
		}
		return $values;
	}

	public function getTypeName(): string {
		return "name";
	}

	public function parse(string $argument, CommandSender $sender) {
		return $argument;
	}
}