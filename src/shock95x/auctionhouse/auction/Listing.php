<?php
namespace shock95x\auctionhouse\auction;

use pocketmine\item\Item;
use shock95x\auctionhouse\utils\Settings;

class Listing {

	private $id;
	private $uuid;
	private $price;
	private $username;
	private $endTime;
	private $expired;

    private $item;

    public function __construct(int $id, string $uuid, int $price, string $username, int $endTime, bool $expired, Item $item) {
        $this->id = $id;
        $this->uuid = $uuid;
        $this->price = $price;
        $this->username = $username;
        $this->endTime = $endTime;
        $this->expired = $expired;
        $this->item = $item;
    }

	public function getItem(): Item {
		return $this->item;
	}

	public function getPrice(bool $monetaryUnit = false, bool $formatted = false) {
		$price = $this->price;
    	if($formatted) {
    		$price = number_format($price);
	    }
    	if($monetaryUnit) {
    		$price = Settings::getMonetaryUnit() . $price;
	    }
    	return $price;
	}

	public function getSeller(bool $uuid = false): string {
		return $uuid ? $this->uuid : $this->username;
	}

	public function getMarketId(): int {
		return $this->id;
	}

	public function setEndTime(int $time): void {
		$this->endTime = $time;
	}

	public function getEndTime(): int {
		return $this->endTime;
	}

	public function setExpired(bool $expired = true): void {
		$this->expired = $expired;
	}

	public function isExpired(): bool{
		return $this->expired;
	}
}