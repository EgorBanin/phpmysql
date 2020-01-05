<?php

namespace tests\Mysql;

abstract class MysqlTestCase extends \PHPUnit\Framework\TestCase {

	protected $host = 'mysql';
	protected $user = 'sakila';
	protected $password = 'passw0rd';
	protected $dbName = 'sakiladb';

	public function getDb() {
		return \Mysql\Client::init($this->user, $this->password, $this->host)
			->defaultDb($this->dbName)
			->charset('utf8');
	}

}