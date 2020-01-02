<?php

declare(strict_types=1);

namespace AuctionHouse\database\utils;

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