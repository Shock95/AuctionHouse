<?php

declare(strict_types=1);

namespace shock95x\auctionhouse;

use pocketmine\item\Item;
use shock95x\auctionhouse\utils\Settings;
use function number_format;

class AHListing{

	public function __construct(
		private int $id,
		private string $uuid,
		private int $price,
		private string $username,
		private int $created,
		private int $endTime,
		private bool $expired,
		private Item $item,
	){
	}

	public function getId() : int{
		return $this->id;
	}

	public function getItem() : Item{
		return $this->item;
	}

	public function getPrice(bool $currencySymbol = false, bool $formatted = false) : int|string{
		$price = $this->price;
		if($formatted) $price = number_format($price);
		if($currencySymbol) $price = Settings::getCurrencySymbol() . $price;
		return $price;
	}

	public function getSeller() : string{
		return $this->username;
	}

	public function getSellerUUID() : string{
		return $this->uuid;
	}

	public function getCreatedTime() : int{
		return $this->created;
	}

	public function setEndTime(int $time) : void{
		$this->endTime = $time;
	}

	public function getEndTime() : int{
		return $this->endTime;
	}

	public function setExpired(bool $expired = true) : void{
		$this->expired = $expired;
	}

	public function isExpired() : bool{
		return $this->expired;
	}
}
