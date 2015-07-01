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
			throw new Exception('Не удалось подключиться к базе данных: '.$e->getMessage(), Exception::CODE_CONNECTION_ERROR, $e);
		}
		
		if ($mysqli->connect_errno != 0) {
			throw new Exception('Не удалось подключиться к базе данных: '.$mysqli->connect_error, Exception::CODE_CONNECTION_ERROR);
		}
		
		$this->mysqli = $mysqli;
		
		if ($this->charset) {
			$this->charset($this->charset);
		}
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
			$result = $this->mysqli->set_charset($this->charset);
			
			if ($result === false) {
				throw new Exception('Ошибка при установке кодировки', Exception::CODE_CHARSET_ERROR);
			}
		}
	}
	
	/**
	 * База данных по умолчанию
	 * @param string $dbName
	 */
	public function defaultDb($dbName) {
		$this->defaultDb = $dbName;
		
		if ($this->mysqli) {
			$result = $this->mysqli->select_db($this->defaultDb);
			
			if ($result === false) {
				throw new Exception( 'Ошибка при установке базы данных по умолчанию', Exception::CODE_DEFAULTDB_ERROR);
			}
		}
	}
	
	public function query($sql) {
		if ( ! $this->mysqli) {
			$this->connect();
		}
		
		try {
			$mysqliResult = $this->mysqli->query($sql);
		} catch (\Exception $e) {
			throw new Exception('Не удалось выполнить запрос: '.$e->getMessage(), Exception::CODE_QUERY_ERROR, $e, $sql);
		}
		
		if ( ! $mysqliResult) {
			throw new Exception('Не удалось выполнить запрос: '.$this->mysqli->error, Exception::CODE_QUERY_ERROR, null, $sql);
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
				throw new Exception($e->getMessage(), Exception::CODE_QUOTE_ERROR, $e);
			}
			
			$quoted = $this->quote($strVal);
		}
		
		return $quoted;
	}
	
	public function __destruct() {
		$this->disconnect();
	}
	
}
