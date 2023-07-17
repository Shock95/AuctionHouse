<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\database;

use Generator;
use pocketmine\utils\Config;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;
use poggit\libasynql\SqlThread;
use shock95x\auctionhouse\AHListing;
use shock95x\auctionhouse\AuctionHouse;
use shock95x\auctionhouse\database\storage\DataStorage;
use shock95x\auctionhouse\task\ListingExpireTask;
use shock95x\auctionhouse\utils\Utils;
use SOFe\AwaitGenerator\Await;
use function boolval;
use function json_decode;

class Database{

	private string $type;

	private AuctionHouse $plugin;
	private Config $config;
	private DataConnector $connector;

	public function __construct(AuctionHouse $plugin, Config $config){
		$this->plugin = $plugin;
		$this->config = $config;
	}

	public function connect() : self{
		try{
			$this->type = $this->config->getNested("database.type");
			$this->connector = libasynql::create($this->plugin, $this->config->get("database"), [
				"sqlite" => "statements/sqlite.sql",
				"mysql" => "statements/mysql.sql"
			], true);

			$this->connector->executeGeneric(Query::INIT);
		}catch(SqlError $error){
			$this->plugin->getLogger()->error($error->getMessage());
		}finally{
			$this->connector->waitAll();
			DataStorage::getInstance()->init($this);
			$this->plugin->getScheduler()->scheduleRepeatingTask(new ListingExpireTask($this), 1200);
		}
		return $this;
	}

	public function fetchAll() : Generator{
		$this->connector->executeSelect(Query::FETCH_ALL, [], yield, yield Await::REJECT);
		return yield Await::ONCE;
	}

	public function setExpired(int $id, bool $expired = true, ?callable $onSuccess = null, ?callable $onError = null) : void{
		$this->connector->executeGeneric(Query::SET_EXPIRED, ["id" => $id, "expired" => $expired]);
	}

	public function delete(int $id, ?callable $onSuccess = null, ?callable $onError = null) : void{
		$this->connector->executeGeneric(Query::DELETE, ["id" => $id], $onSuccess, $onError);
	}

	public function close() : void{
		$this->connector?->waitAll();
		$this->connector?->close();
	}

	public function getConnector() : DataConnector{
		return $this->connector;
	}

	public function getType() : string{
		return $this->type;
	}

	public function asyncSelectRaw(string $query, array $args = []) : Generator{
		return yield from Await::promise(function($resolve, $reject) use ($query, $args){
			$this->connector->executeImplRaw([$query], [$args], [SqlThread::MODE_SELECT], $resolve, $reject);
		});
	}

	public function asyncGenericRaw(string $query, array $args = []) : Generator{
		return yield from Await::promise(function($resolve, $reject) use ($query, $args){
			$this->connector->executeImplRaw([$query], [$args], [SqlThread::MODE_GENERIC], $resolve, $reject);
		});
	}

	public function asyncChangeRaw(string $query, array $args = []) : Generator{
		return yield from Await::promise(function($resolve, $reject) use ($query, $args){
			$this->connector->executeImplRaw([$query], [$args], [SqlThread::MODE_CHANGE], $resolve, $reject);
		});
	}

	public function createListingFromRows(array $rows) : AHListing{
		return new AHListing(
			$rows["id"],
			$rows["uuid"],
			$rows["price"],
			$rows["username"],
			$rows["created"],
			$rows["end_time"],
			boolval($rows["expired"]),
			Utils::itemDeserialize(json_decode($rows["item"], true))
		);
	}
}
