<?php

/*
 * libasynql
 *
 * Copyright (C) 2018 SOFe
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace shock95x\auctionhouse\libs\_45f135c7cfd69bf7\poggit\libasynql\mysqli;

use JsonSerializable;
use mysqli;
use mysqli_sql_exception;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\poggit\libasynql\ConfigException;
use shock95x\auctionhouse\libs\_45f135c7cfd69bf7\poggit\libasynql\SqlError;
use function strlen;

class MysqlCredentials implements JsonSerializable{
	/** @var string $host */
	private $host;
	/** @var string $username */
	private $username;
	/** @var string $password */
	private $password;
	/** @var string $schema */
	private $schema;
	/** @var int $port */
	private $port;
	/** @var string $socket */
	private $socket;
	/** @var MysqlSslCredentials|null */
	private $sslCredentials;


	/**
	 * Creates a new {@link MysqlCredentials} instance from an array (e.g. from Config), with the following defaults:
	 * <pre>
	 * host: 127.0.0.1
	 * username: root
	 * password: ""
	 * schema: {$defaultSchema}
	 * port: 3306
	 * socket: ""
	 * </pre>
	 *
	 * @param array       $array
	 * @param string|null $defaultSchema default null
	 * @return MysqlCredentials
	 * @throws ConfigException If <code>schema</code> is missing and <code>$defaultSchema</code> is null/not passed
	 */
	public static function fromArray(array $array, ?string $defaultSchema = null) : MysqlCredentials{
		if(!isset($defaultSchema, $array["schema"])){
			throw new ConfigException("The attribute \"schema\" is missing in the MySQL settings");
		}
		return new MysqlCredentials(
			$array["host"] ?? "127.0.0.1",
			$array["username"] ?? "root",
			$array["password"] ?? "",
			$array["schema"] ?? $defaultSchema,
			$array["port"] ?? 3306,
			$array["socket"] ?? "",
			isset($array["ssl"]) ? MysqlSslCredentials::fromArray($array["ssl"]) : null,
		);
	}

	/**
	 * Constructs a new {@link MysqlCredentials} by passing parameters directly.
	 *
	 * @param string $host
	 * @param string $username
	 * @param string $password
	 * @param string $schema
	 * @param int    $port
	 * @param string $socket
	 * @param MysqlSslCredentials|null $sslCredentials
	 */
	public function __construct(string $host, string $username, string $password, string $schema, int $port = 3306, string $socket = "", ?MysqlSslCredentials $sslCredentials = null){
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
		$this->schema = $schema;
		$this->port = $port;
		$this->socket = $socket;
		$this->sslCredentials = $sslCredentials;
	}

	/**
	 * Creates a new <a href="https://php.net/mysqli">mysqli</a> instance
	 *
	 * @return mysqli
	 *
	 * @throws SqlError
	 */
	public function newMysqli() : mysqli{
		$mysqli = mysqli_init();
		if($mysqli === false){
			throw new SqlError(SqlError::STAGE_CONNECT, "Failed to initialize mysqli");
		}
		if($this->sslCredentials !== null){
			$this->sslCredentials->applyToInstance($mysqli);
		}
		try {
			@mysqli_real_connect($mysqli, $this->host, $this->username, $this->password, $this->schema, $this->port, $this->socket);
			if($mysqli->connect_error){
				throw new SqlError(SqlError::STAGE_CONNECT, $mysqli->connect_error);
			}
		}catch (mysqli_sql_exception $e){
			//TODO HACK! extensive testing showed that both ways of error handling are acceptable.
			// maybe it depends on php build, or OS type? I don't really know. Testing it on windows
			// shows that mysqli_real_connect throws mysqli exception if it is a first connection.
			// Exception is being thrown before we could actually check connect_error
			// so we are forced to catch mysqli_sql_exception
			// On the other hand, if we try to reconnectMysqli, there is no mysqli exception thrown,
			// instead, we should check connect_error to find out if connect is successful or not
			// (looks like first connection is throwing, and other ones do not)
			// tested on 8.1.23 (ZTS Visual C++ 2019 x64) Windows

			throw new SqlError(SqlError::STAGE_CONNECT, $e->getMessage());
		}
		return $mysqli;
	}

	/**
	 * Reuses an existing <a href="https://php.net/mysqli">mysqli</a> instance
	 *
	 * @param mysqli $mysqli
	 *
	 * @throws SqlError
	 */
	public function reconnectMysqli(mysqli $mysqli) : void{
		try {
			@mysqli_real_connect($mysqli, $this->host, $this->username, $this->password, $this->schema, $this->port, $this->socket);
			if ($mysqli->connect_error) {
				throw new SqlError(SqlError::STAGE_CONNECT, $mysqli->connect_error);
			}
		}catch (mysqli_sql_exception $e){
			throw new SqlError(SqlError::STAGE_CONNECT, $e->getMessage());
		}
	}

	/**
	 * Produces a human-readable output without leaking password
	 *
	 * @return string
	 */
	public function __toString() : string{
		return "$this->username@$this->host:$this->port/schema,$this->socket";
	}

	/**
	 * Prepares value to be var_dump()'ed without leaking password
	 *
	 * @return array
	 */
	public function __debugInfo(){
		return [
			"host" => $this->host,
			"username" => $this->username,
			"password" => str_repeat("*", strlen($this->password)),
			"schema" => $this->schema,
			"port" => $this->port,
			"socket" => $this->socket,
			"sslCredentials" => $this->sslCredentials,
		];
	}

	public function jsonSerialize() : array{
		return [
			"host" => $this->host,
			"username" => $this->username,
			"password" => $this->password,
			"schema" => $this->schema,
			"port" => $this->port,
			"socket" => $this->socket,
			"sslCredentials" => $this->sslCredentials,
		];
	}
}