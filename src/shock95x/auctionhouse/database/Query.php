<?php

declare(strict_types=1);

namespace shock95x\auctionhouse\database;

class Query{

	const INIT = "auctionhouse.init";
	const INSERT = "auctionhouse.insert";
	const DELETE = "auctionhouse.delete";
	const SET_EXPIRED = "auctionhouse.expired";

	const COUNT_ALL = "auctionhouse.count.all";
	const COUNT_ACTIVE = "auctionhouse.count.active.all";
	const COUNT_ACTIVE_UUID = "auctionhouse.count.active.uuid";
	const COUNT_ACTIVE_USERNAME = "auctionhouse.count.active.username";
	const COUNT_EXPIRED = "auctionhouse.count.expired.all";
	const COUNT_EXPIRED_UUID = "auctionhouse.count.expired.uuid";

	const FETCH_ID = "auctionhouse.fetch.id";
	const FETCH_ALL = "auctionhouse.fetch.all";
	const FETCH_ACTIVE_NEXT = "auctionhouse.fetch.active.next";
	const FETCH_ACTIVE_UUID = "auctionhouse.fetch.active.uuid";
	const FETCH_ACTIVE_USERNAME = "auctionhouse.fetch.active.username";

	const FETCH_EXPIRED_UUID = "auctionhouse.fetch.expired.uuid";
}
