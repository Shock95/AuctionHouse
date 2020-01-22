<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\database\utils;

class SQLiteBinaryStringParser implements BinaryStringParserInstance{

	public function encode(string $string) : string{
		return bin2hex($string);
	}

	public function decode(string $string) : string{
		return hex2bin($string);
	}
}