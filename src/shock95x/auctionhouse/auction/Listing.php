<?php
namespace shock95x\auctionhouse\auction;

use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\utils\BinaryStringParserInstance;
use shock95x\auctionhouse\utils\Utils;
use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;

class Listing {

	private $data;
	private $item;

	public function __construct(array $data, BinaryStringParserInstance $parser) {
		$this->data = $data;
		$nbt = (new BigEndianNBTStream())->readCompressed($parser->decode($data["nbt"]));
		assert($nbt instanceof CompoundTag);
		$this->item = Item::nbtDeserialize($nbt);
	}


	public function getItem() : Item {
		return $this->item;
	}

	public function getPrice(bool $monetaryUnit = false) {
		return $monetaryUnit ? AuctionHouse::getInstance()->economyProvider->getMonetaryUnit() . $this->data["price"] : $this->data["price"];
	}

	public function getSeller(bool $uuid = false) : string {
		return $uuid ? $this->data["uuid"] : $this->data["username"];
	}

	public function getMarketId() : int {
		return $this->data["id"];
	}

	public function setEndTime() {
		$this->data["end_time"] = Utils::getEndTime();
	}

	public function getEndTime() : int {
		return $this->data["end_time"];
	}

	public function setExpired() {
		$this->data["expired"] = true;
	}

	public function isExpired() {
		return $this->data["expired"];
	}
}