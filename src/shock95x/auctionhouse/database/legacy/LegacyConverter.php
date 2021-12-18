<?php
namespace shock95x\auctionhouse\database\legacy;

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
		$rowCount = 0;
		switch($this->database->getType()) {
			case 'mysql':
				$rowCount = count(yield $this->database->asyncSelectRaw("DESCRIBE auctions;"));
				break;
			case 'sqlite':
				$rowCount = count(yield $this->database->asyncSelectRaw("PRAGMA table_info(auctions);"));
				break;
		};
		return $rowCount == 7;
	}

	public function convert(): Generator {
		$type = $this->database->getType();
		if($type == 'mysql') {
			yield $this->database->asyncChangeRaw("
				ALTER TABLE auctions
   				DROP PRIMARY KEY,
   				RENAME COLUMN id TO created,
   				RENAME COLUMN nbt TO item,
   				ADD COLUMN id INTEGER PRIMARY KEY AUTO_INCREMENT FIRST,
				MODIFY COLUMN username VARCHAR(36),
   				MODIFY COLUMN uuid CHAR(36);");

			yield $this->database->asyncChangeRaw("ALTER TABLE auctions MODIFY COLUMN item JSON;");
		} else if ($type == 'sqlite') {
			yield $this->database->asyncChangeRaw("ALTER TABLE auctions RENAME TO auctions_old;");
			yield $this->database->asyncGeneric(Query::INIT);
			yield $this->database->asyncGenericRaw("INSERT INTO auctions (uuid, username, item, price, created, end_time, expired) SELECT uuid, username, nbt, price, id, end_time, expired FROM auctions_old;");
		}
		$rows = yield $this->database->asyncSelectRaw("SELECT * from auctions;");
		foreach ($rows as $listing) {
			$uuid = Uuid::fromBytes($listing['uuid']);
			$root = $this->nbt->read(zlib_decode($listing["item"]));
			$item = Item::nbtDeserialize($root->mustGetCompoundTag());
			yield $this->database->asyncGenericRaw("UPDATE auctions SET uuid = :uuid, item = :item WHERE id = :id;", ["uuid" => $uuid->toString(), "item" => json_encode($item->jsonSerialize()), "id" => $listing['id']]);
		}
	}
}