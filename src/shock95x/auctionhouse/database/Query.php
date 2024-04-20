<?php
declare(strict_types=1);

namespace shock95x\auctionhouse\database;

class Query {

	const INIT = "auctionhouse.init.tables";

	const INSERT = "auctionhouse.insert";

	const EXPIRE_ALL = "auctionhouse.expire.all";
	const EXPIRE_ID = "auctionhouse.expire.id";
	const EXPIRE_USERNAME = "auctionhouse.expire.username";

	const RELIST_ALL = "auctionhouse.relist.all";
	const RELIST_USERNAME = "auctionhouse.relist.username";

	const COUNT_ALL = "auctionhouse.count.all";
	const COUNT_USERNAME = "auctionhouse.count.username";
	const COUNT_ACTIVE = "auctionhouse.count.active.all";
	const COUNT_ACTIVE_UUID = "auctionhouse.count.active.uuid";
	const COUNT_ACTIVE_USERNAME = "auctionhouse.count.active.username";
	const COUNT_EXPIRED = "auctionhouse.count.expired.all";
	const COUNT_EXPIRED_UUID = "auctionhouse.count.expired.uuid";
	const COUNT_EXPIRED_USERNAME = "auctionhouse.count.expired.username";

	const DELETE_ID = "auctionhouse.delete.id";
	const DELETE_USERNAME = "auctionhouse.delete.username";

	const FETCH_ID = "auctionhouse.fetch.id";
	const FETCH_ALL = "auctionhouse.fetch.all";
	const FETCH_USERNAME = "auctionhouse.fetch.username";
	const FETCH_ACTIVE_NEXT = "auctionhouse.fetch.active.next";
	const FETCH_ACTIVE_UUID = "auctionhouse.fetch.active.uuid";
	const FETCH_ACTIVE_USERNAME = "auctionhouse.fetch.active.username";
	const FETCH_EXPIRED_UUID = "auctionhouse.fetch.expired.uuid";
}