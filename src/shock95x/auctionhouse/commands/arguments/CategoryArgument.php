<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\commands\arguments;

use CortexPE\Commando\args\StringEnumArgument;
use pocketmine\command\CommandSender;
use shock95x\auctionhouse\category\Category;
use shock95x\auctionhouse\category\ICategory;
use function array_keys;
use function array_map;

class CategoryArgument extends StringEnumArgument{

	public function getEnumValues() : array{
		$names = array_keys(Category::getAll());
		return array_map('strtolower', $names);
	}

	public function getTypeName() : string{
		return "name";
	}

	public function parse(string $argument, CommandSender $sender) : ICategory{
		return Category::get($argument);
	}
}
