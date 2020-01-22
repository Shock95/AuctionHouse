<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\database\utils;

final class BinaryStringParser{

	public static function fromDatabase(string $type) : BinaryStringParserInstance{
		switch($type){
			case "mysql":
				return new MySQLBinaryStringParser();
			case "sqlite":
				return new SQLiteBinaryStringParser();
		}
	}
}