<?php
declare(strict_types=1);

namespace shock95x\auctionhouse;

use pocketmine\item\Item;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use shock95x\auctionhouse\utils\Settings;
use shock95x\auctionhouse\utils\Utils;

class AHListing {

	public function __construct(
		private int $id,
		private UuidInterface $seller,
		private int $price,
		private string $username,
		private int $createTime,
		private int $expireTime,
		private bool $expired,
		private Item $item,
	) {}

	public function getId(): int {
		return $this->id;
	}

	public function getItem(): Item {
		return $this->item;
	}

	public function getPrice(bool $currencySymbol = false, bool $formatted = false): int|string {
		$price = $this->price;
    	if($formatted) $price = number_format($price);
    	if($currencySymbol) $price = Settings::getCurrencySymbol() . $price;
    	return $price;
	}

	public function getSeller(): string {
		return $this->username;
	}

	public function getSellerUUID() : UuidInterface {
		return $this->seller;
	}

	public function getCreatedTime(): int {
		return $this->createTime;
	}

	public function getExpireTime(): int {
		return $this->expireTime;
	}

	public function isExpired(): bool {
		return $this->expired;
	}

	public static function fromRow(array $row): self {
		return new self(
			$row["id"],
			Uuid::fromBytes($row["player_uuid"]),
			$row["price"],
			$row["username"],
			$row["created_at"],
			$row["expires_at"],
			boolval($row["expired"]),
			Utils::deserializeItem($row["item"])
		);
	}
}