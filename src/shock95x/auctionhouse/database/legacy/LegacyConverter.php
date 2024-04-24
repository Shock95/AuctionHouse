<?php
namespace shock95x\auctionhouse\database\legacy;

use Generator;
use pocketmine\item\Item;
use pocketmine\utils\SingletonTrait;
use poggit\libasynql\result\SqlSelectResult;
use Ramsey\Uuid\Uuid;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\Database;
use shock95x\auctionhouse\database\DatabaseType;
use shock95x\auctionhouse\database\Query;
use shock95x\auctionhouse\utils\Utils;

class LegacyConverter {

	private Database $database;

	use SingletonTrait;

	public function init(Database $database) {
		$this->database = $database;
	}

	public function convert(): Generator {
		$type = $this->database->getType();
		/** @var SqlSelectResult[] $columns */
		$columns = yield from $this->database->asyncSelectRaw($type == DatabaseType::MySQL ? "DESCRIBE listings" : "pragma table_info(listings)");
		foreach($columns[0]->getRows() as $column) {
			$name = $column[$type == DatabaseType::MySQL ? "Field" : "name"];
			$columnType = strtolower($column[$type == DatabaseType::MySQL ? "Type" : "type"]);
			if($name == "item" && ($columnType == "text" || $columnType == "json")) {
				AuctionHouse::getInstance()->getLogger()->notice("Migrating to new database schema");
				yield from $this->database->asyncGenericRaw("CREATE TABLE IF NOT EXISTS temp AS SELECT * FROM listings;");
				yield from $this->database->asyncGenericRaw("DROP TABLE listings;");
				yield from $this->database->getConnector()->asyncGeneric(Query::INIT);
				/** @var SqlSelectResult[] $listings */
				$listings = yield from $this->database->asyncSelectRaw("SELECT * from temp;");
				foreach($listings[0]->getRows() as $listing) {
					$jsonItem = json_decode($listing["item"], true);
					if(is_array($jsonItem)) {
						$item = Item::legacyJsonDeserialize($jsonItem);
						$listing["item"] = Utils::serializeItem($item);
					} else {
						$item = trim($listing["item"],'"');
						$listing["item"] = hex2bin($item);
					}
					$listing["created_at"] = $listing["created"];
					$listing["expires_at"] = $listing["end_time"];
					$listing["player_uuid"] = Uuid::fromString($listing["uuid"])->getBytes();
					yield from $this->database->getConnector()->asyncInsert(Query::INSERT, $listing);
				}
				yield from $this->database->asyncGenericRaw("DROP TABLE temp;");
				AuctionHouse::getInstance()->getLogger()->notice("Migration successfully completed");
				break;
			}
		}
	}
}