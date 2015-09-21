<?php

namespace Mysql;

/**
 * Клиент базы данных MySQL
 * Обёртка Mysqli с подключением по требованию.
 */
class Client {
	
	private $connection;
	
	public function __construct(Connection $connection) {
		$this->connection = $connection;
	}
	
	public static function init($username, $password, $host = 'localhost', $port = 3306) {
		$connection = new Connection($username, $password, $host, $port);
		
		return new self($connection);
	}
	
	/**
	 * База данных по умолчанию
	 * @param string $dbName
	 */
	public function defaultDb($dbName) {
		$this->connection->defaultDb($dbName);
		
		return $this;
	}
	
	/**
	 * Кодировка
	 * @param string $charset
	 */
	public function charset($charset) {
		$this->connection->charset($charset);
		
		return $this;
	}
	
	/**
	 * Запрос
	 * Значения параметров экранируются; строки заключаются в одинарные кавычки;
	 * булевы значения преобразуются в строки true и false, null-значения — в null;
	 * значения одномерных массивов разделяются запятыми,
	 * двуменые дополнительно заключаются скобки;
	 * объекты приводятся к строке.
	 * @param string $sql
	 * @param array $params
	 * @return Result
	 * @throws Exception
	 */
	public function query($sql, array $params = array()) {
		$prepared = $this->prepare($sql, $params, array($this->connection, 'quote'));
		$result = $this->connection->query($prepared);
		
		return $result;
	}
	
	private function prepare($sql, array $params, $quoteFunc) {
		$replacePairs = array();
		foreach ($params as $name => $val) {
			$replacePairs[$name] = call_user_func($quoteFunc, $val);
		}
		
		return strtr($sql, $replacePairs);
	}
	
}
