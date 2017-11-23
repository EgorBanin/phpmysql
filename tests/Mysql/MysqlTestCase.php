<?php

namespace Mysql;

abstract class MysqlTestCase extends \PHPUnit\Framework\TestCase {
	
	//private static $mysqlCommand = '"C:\Program Files\MySQL\MySQL Server 5.6\bin\mysql"';
	private static $mysqlCommand = 'mysql';
	private static $mysqlUser = 'root';
	private static $mysqlPassword = '123456';

	protected $user = 'sakila';
	protected $password = 'password123';
	protected $dbName = 'sakiladb';

	public static function setUpBeforeClass() {
		$command = self::$mysqlCommand.' -u'.self::$mysqlUser.' -p'.self::$mysqlPassword.' < '.__DIR__.'/../sql/setUp.sql';
		exec($command);
	}
	
	public static function tearDownAfterClass() {
		$command = self::$mysqlCommand.' -u'.self::$mysqlUser.' -p'.self::$mysqlPassword.' < '.__DIR__.'/../sql/tearDown.sql';
		exec($command);
	}

	public function getDb() {
		return Client::init($this->user, $this->password)
			->defaultDb($this->dbName)
			->charset('utf8');
	}

}