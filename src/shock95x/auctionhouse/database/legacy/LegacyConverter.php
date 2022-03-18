<?php
namespace shock95x\auctionhouse\database\legacy;

use Exception;
use Generator;
use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\utils\SingletonTrait;
use Ramsey\Uuid\Uuid;
use shock95x\auctionhouse\database\Database;
use shock95x\auctionhouse\database\Query;

class LegacyConverter {

	private Database $database;
	private BigEndianNbtSerializer $nbt;

	use SingletonTrait;

	public function init(Database $database) {
		$this->database = $database;
		$this->nbt = new BigEndianNbtSerializer();
	}

	public function isLegacy(): Generator {
		$type = $this->database->getType();
		$rowCount = count(yield $this->database->asyncSelectRaw($type == "mysql" ?  "DESCRIBE auctions;" : "PRAGMA table_info(auctions);"));
		return $rowCount == 7;
	}

	public function convert(): Generator {
		yield $this->database->asyncGeneric(Query::INIT);
		yield $this->database->asyncGenericRaw("INSERT INTO listings (uuid, username, item, price, created, end_time, expired) SELECT uuid, username, nbt, price, id, end_time, expired FROM auctions;");
		$rows = yield $this->database->asyncSelectRaw("SELECT * from listings;");
		foreach ($rows as $listing) {
			try {
				$uuid = Uuid::fromBytes($listing['uuid']);
				$nbt = $this->nbt->read(zlib_decode($listing["item"]));
				$item = Item::nbtDeserialize($nbt->mustGetCompoundTag());
				yield $this->database->asyncGenericRaw("UPDATE listings SET uuid = :uuid, item = :item WHERE id = :id;", ["uuid" => $uuid->toString(), "item" => json_encode($item->jsonSerialize()), "id" => $listing['id']]);
			} catch (Exception) {}
		}
		yield $this->database->asyncChangeRaw("DROP TABLE auctions;");
	}
}