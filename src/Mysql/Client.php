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
	
	public static function init($username, $password, $host = 'localhost', $port = 3306, $lazyConnect = true) {
		$connection = new Connection($username, $password, $host, $port);

		if ( ! $lazyConnect) {
			$connection->connect();
		}
		
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
	
	/**
	 * Выполнение нескольких запросов в рамках одной транзакции
	 * @param array $queries
	 * @throws \Mysql\Exception
	 */
	public function transaction(array $queries, array &$results = array()) {
		$this->connection->startTransaction();
		
		foreach ($queries as $query) {
			if (is_string($query)) {
				$sql = $query;
				$params = array();
			} elseif (is_array($query)) {
				$sql = array_shift($query);
				$params = array_shift($query)?: array();
			} else {
				throw new Exception('Неверный формат запроса');
			}
			
			try {
				$results[] = $this->query($sql, $params);
			} catch (Exception $e) {
				$this->connection->rollbackTransaction();
				throw $e;
			}
		}
		
		return $this->connection->commitTransaction();
	}

	/**
	 * Шорткат для new Table
	 * @param string $name имя таблицы
	 * @param string $pk имя столбца, по которому построен первичный ключ
	 * @return \Mysql\Table
	 */
	public function table($name, $pk = 'id') {
		return new Table($this, $name, $pk);
	}
	
	private function prepare($sql, array $params, $quoteFunc) {
		$replacePairs = array();
		foreach ($params as $name => $val) {
			$replacePairs[$name] = call_user_func($quoteFunc, $val);
		}
		
		return strtr($sql, $replacePairs);
	}
	
}
