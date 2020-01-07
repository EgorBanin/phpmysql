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
	 * Выполнение функции в рамках транзакции
	 * В качества аргумента принимает функцию типа
	 * function(\Mysql\Client $db, callable $commit, callable $rollback) { ... }
	 * Исключение внутри функции вызовет автоматический откат транзакции, завершение без ошибок -- автоматический коммит.
	 * @param callable $func
	 * @throws \Mysql\Exception
	 * @return mixed результат вызова $func
	 */
	public function transaction(callable $func) {
		$connection = $this->connection;
		$connection->startTransaction();

		$commitResult = null;
		$commit = static function() use($connection, &$commitResult): bool {
			$commitResult = $connection->commitTransaction();
			return $commitResult;
		};
		$rollbackResult = null;
		$rollback = static function() use($connection, &$rollbackResult): bool {
			$rollbackResult = $connection->rollbackTransaction();
			return $rollbackResult;
		};

		try {
			$result = $func($this, $commit, $rollback);
		} catch (\Throwable $e) {
			if ($commitResult === null && $rollbackResult === null) {
				$connection->rollbackTransaction();
			}
			throw new Exception('Не удалось выполнить транзакцию: ' . $e->getMessage(), 0, $e);
		}

		if ($commitResult === null && $rollbackResult === null) {
			$connection->commitTransaction();
		}

		return $result;
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
