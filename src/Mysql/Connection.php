<?php

namespace Mysql;

class Connection {
	
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
	
	public function __construct($username, $password, $host, $port) {
		$this->username = $username;
		$this->password = $password;
		$this->host = $host;
		$this->port = $port;
	}
	
	public function connect() {
		try {
			$mysqli = new \Mysqli($this->host, $this->username, $this->password, $this->defaultDb, $this->port);
		} catch (\Exception $e) {
			throw new Exception($e->getMessage());
		}
		
		if ($mysqli->connect_errno != 0) {
			throw new Exception('Не удалось подключиться к базе данных: '.$mysqli->connect_error);
		}
		
		if ($this->charset) {
			$mysqli->set_charset($this->charset);
		}
		
		$this->mysqli = $mysqli;
	}
	
	public function disconnect() {
		if ($this->mysqli) {
			$this->mysqli->close();
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
	 * База данных по умолчанию
	 * @param string $dbName
	 */
	public function defaultDb($dbName) {
		$this->defaultDb = $dbName;
		
		if ($this->mysqli) {
			$this->mysqli->select_db($this->defaultDb);
		}
	}
	
	public function query($sql) {
		if ( ! $this->mysqli) {
			$this->connect();
		}
		
		try {
			$mysqliResult = $this->mysqli->query($sql);
		} catch (\Exception $e) {
			throw new Exception($e->getMessage());
		}
		
		if ( ! $mysqliResult) {
			throw new Exception('Не удалось выполнить запрос: '.$this->mysqli->error);
		}
		
		$rows = [];
		if ($mysqliResult instanceof \Mysqli_Result) {
			while ($row = $mysqliResult->fetch_assoc()) {
				$rows[] = $row;
			}
			
			$mysqliResult->free();
		}
		
		return new Result($sql, $rows, $this->mysqli->affected_rows, $this->mysqli->insert_id);
	}
	
	/**
	 * @param mixed $val
	 * @return string
	 * @throws \Mysql\Exception
	 */
	public function quote($val) {
		if ( ! $this->mysqli) {
			$this->connect();
		}
		
		if (is_string($val)) {
			$str = $this->mysqli->escape_string($val);
			$quoted = "'$str'";
		} elseif (is_int($val)) {
			$quoted = (string) $val;
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
		} else {
			try {
				$strVal = (string) $val;
			} catch (\Exception $e) {
				throw new Exception($e->getMessage());
			}
			
			$quoted = $this->quote($strVal);
		}
		
		return $quoted;
	}
	
	public function __destruct() {
		$this->disconnect();
	}
	
}

