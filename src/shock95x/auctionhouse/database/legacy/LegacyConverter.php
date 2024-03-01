<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\database\legacy;

use Exception;
use Generator;
use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\utils\SingletonTrait;
use Ramsey\Uuid\Uuid;
use shock95x\auctionhouse\database\Database;
use shock95x\auctionhouse\database\Query;
use shock95x\auctionhouse\utils\Utils;
use function bin2hex;
use function count;
use function json_encode;
use function zlib_decode;

class LegacyConverter{

	private Database $database;
	private BigEndianNbtSerializer $nbt;

	use SingletonTrait;

	public function init(Database $database){
		$this->database = $database;
		$this->nbt = new BigEndianNbtSerializer();
	}

	public function isLegacy() : Generator{
		$type = $this->database->getType();
		$rowCount = count(yield from $this->database->asyncSelectRaw($type == "mysql" ? "DESCRIBE auctions;" : "PRAGMA table_info(auctions);"));
		return $rowCount == 7;
	}

	public function convert() : Generator{
		yield from $this->database->getConnector()->asyncGeneric(Query::INIT);
		yield from $this->database->asyncGenericRaw("INSERT INTO listings (uuid, username, item, price, created, end_time, expired) SELECT uuid, username, nbt, price, id, end_time, expired FROM auctions;");
		$rows = yield from $this->database->asyncSelectRaw("SELECT * from listings;");
		foreach($rows as $listing){
			try{
				$uuid = Uuid::fromBytes($listing['uuid']);
				$nbt = $this->nbt->read(zlib_decode($listing["item"]));
				$item = Item::nbtDeserialize($nbt->mustGetCompoundTag());
				yield from $this->database->asyncGenericRaw("UPDATE listings SET uuid = :uuid, item = :item WHERE id = :id;", ["uuid" => $uuid->toString(), "item" => json_encode(bin2hex(Utils::ItemSerialize($item))), "id" => $listing['id']]);
			}catch(Exception){
			}
		}
		yield from $this->database->asyncChangeRaw("DROP TABLE auctions;");
	}
}
