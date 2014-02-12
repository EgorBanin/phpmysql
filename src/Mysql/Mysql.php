<?php

namespace Mysql;

use \Mysqli;

/**
 * База данных MySQL
 * Обёртка Mysqli с подключением по требованию.
 */
class Mysql {
	
	private $username;
	
	private $password;
	
	private $host;
	
	private $port;
	
	private $defaultDb;
	
	private $charset;
	
	/**
	 * @var Mysqli
	 */
	private $mysqli;
	
	/**
	 * Подключение создаётся не сразу, а при первом запросе.
	 */
	public function __construct($username, $password, $host, $port = 3306) {
		$this->username = $username;
		$this->password = $password;
		$this->host = $host;
		$this->port = $port;
	}
	
	/**
	 * База данных по умолчанию
	 * @param string $dbName
	 */
	public function defaultDb($dbName) {
		$this->defaultDb = $dbName;
		
		if ($this->mysqli) {
			$this->mysqli->select_db($this->defaultDb);
		}
	}
	
	/**
	 * Кодировка
	 * @param string $charset
	 */
	public function charset($charset) {
		$this->charset = $charset;
		
		if ($this->mysqli) {
			$this->mysqli->set_charset($this->charset);
		}
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
	public function query($sql, array $params = []) {
		$preparedSql = $this->prepare($sql, $params);
		$mysqli = $this->mysqli();
		$mysqliResult = $mysqli->query($preparedSql);
		
		if ( ! $mysqliResult) {
			throw new Exception('Не удалось выполнить запрос: '.$mysqli->error);
		}
		
		$rows = [];
		if ($mysqliResult instanceof \Mysqli_Result) {
			while ($row = $mysqliResult->fetch_assoc()) {
				$rows[] = $row;
			}
		}
		
		return new Result($preparedSql, $rows, $mysqli->affected_rows, $mysqli->insert_id);
	}
	
	private function prepare($sql, array $params) {
		$replacePairs = [];
		
		foreach ($params as $name => $val) {
			$replacePairs[$name] = $this->quote($val);
		}
		
		return strtr($sql, $replacePairs);
	}
	
	private function quote($val) {
		$mysqli = $this->mysqli();
		
		if (is_string($val)) {
			$str = $mysqli->escape_string($val);
			$quoted = "'$str'";
		} elseif (is_int($val)) {
			$quoted = $val;
		} elseif (is_float($val)) {
			$quoted = sprintf('%F', $val);
		} elseif (is_bool($val)) {
			$quoted = $val? 'true' : 'false';
		} elseif (is_null($val)) {
			$quoted = 'null';
		} elseif (is_array($val)) {
			$quotedArr =[];
			
			foreach ($val as $innerVal) {
				$str = $this->quote($innerVal);
				
				if (is_array($innerVal)) {
					$quotedArr[] = "($str)";
				} else {
					$quotedArr[] = $str;
				}
			}
			
			$quoted = join(', ', $quotedArr);
		} elseif (is_object($val)) {
			$quoted = $this->quote((string) $val);
		} else {
			throw new Exception('Неожиданный тип значения '.gettype($val));
		}
		
		return $quoted;
	}
	
	/**
	 * @return Mysqli
	 * @throws Exception
	 */
	private function connect() {
		try {
			$mysqli = new Mysqli($this->host, $this->username, $this->password, $this->defaultDb, $this->port);
		} catch (\Exception $e) {
			throw new Exception($e->getMessage());
		}
		
		if ($mysqli->connect_errno != 0) {
			throw new Exception('Не удалось подключиться к базе данных: '.$mysqli->connect_error);
		}
		
		if ($this->charset) {
			$mysqli->set_charset($this->charset);
		}
		
		return $mysqli;
	}
	
	private function mysqli() {
		if ($this->mysqli === null) {
			$this->mysqli = $this->connect();
		}
		
		return $this->mysqli;
	}
	
	public function __destruct() {
		if ($this->mysqli) {
			$this->mysqli->close();
		}
	}
	
}
