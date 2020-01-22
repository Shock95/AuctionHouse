<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\database\utils;

interface BinaryStringParserInstance{

	public function encode(string $string) : string;

	public function decode(string $string) : string;
}