<?php
declare(strict_types=1);

namespace shock95x\auctionhouse;

use pocketmine\item\Item;
use shock95x\auctionhouse\utils\Settings;

class AHListing {

	private int $id;
	private string $uuid;
	private int $price;
	private string $username;
	private int $endTime;
	private bool $expired;
    private Item $item;

    public function __construct(int $id, string $uuid, int $price, string $username, int $endTime, bool $expired, Item $item) {
        $this->id = $id;
        $this->uuid = $uuid;
        $this->price = $price;
        $this->username = $username;
        $this->endTime = $endTime;
        $this->expired = $expired;
        $this->item = $item;
    }

	public function getId(): int {
		return $this->id;
	}

	public function getItem(): Item {
		return $this->item;
	}

	/**
	 * @param bool $monetaryUnit
	 * @param bool $formatted
	 *
	 * @return int|string
	 */
	public function getPrice(bool $monetaryUnit = false, bool $formatted = false) {
		$price = $this->price;
    	if($formatted) $price = number_format($price);
    	if($monetaryUnit) $price = Settings::getMonetaryUnit() . strval($price);
    	return $price;
	}

	public function getSeller(): string {
		return $this->username;
	}

	public function getSellerUUID() : string {
		return $this->uuid;
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