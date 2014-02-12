<?php

namespace Mysql;

/**
 * База данных MySQL
 * Обёртка Mysqli с подключением по требованию.
 */
class Mysql {
	
	const _MYSQLI_CONNECT_SUCCESS_CODE = 0;
	
	/**
	 * @var string имя пользоватедя бд
	 */
	private $username;
	
	/**
	 * @var string пароль пользователя бд
	 */
	private $password;
	
	private $host;
	
	private $port;
	
	/**
	 * @var string база данных по умолчанию
	 */
	private $defaultDb;
	
	/**
	 * @var string кодировка подключения
	 * @see http://dev.mysql.com/doc/refman/5.5/en/charset-connection.html
	 */
	private $charset;
	
	/**
	 * @var \Mysqli
	 */
	private $mysqli;
	
	/**
	 * Подключение создаётся не сразу, а по требованию
	 */
	public function __construct($username, $password, $host, $port = 3306) {
		$this->username = $username;
		$this->password = $password;
		$this->host = $host;
		$this->port = $port;
	}
	
	public function defaultDb($dbName) {
		$this->defaultDb = $dbName;
		
		if ($this->mysqli) {
			$this->mysqli->select_db($this->defaultDb);
		}
	}
	
	public function charset($charset) {
		$this->charset = $charset;
		
		if ($this->mysqli) {
			$this->mysqli->set_charset($this->charset);
		}
	}
	
	/**
	 * Создание и выполнение запроса
	 * @param string $sql
	 * @param array $vars
	 * @return Result
	 * @throws Exception
	 */
	public function query($sql, array $vars = []) {
		$query = new Query($sql, $vars);
		
		return $this->executeQuery($query);
	}
	
	/**
	 * @return \Mysqli
	 * @throws Exception
	 */
	private function connect() {
		try {
			$mysqli = new \Mysqli($this->host, $this->username, $this->password, $this->defaultDb, $this->port);
		} catch (\Exception $e) {
			throw new Exception($e->getMessage());
		}
		
		if ($mysqli->connect_errno != self::_MYSQLI_CONNECT_SUCCESS_CODE) {
			throw new Exception('Не удалось подключиться к базе данных: '.$mysqli->connect_error);
		}
		
		if ($this->charset) {
			$mysqli->set_charset($this->charset);
		}
		
		return $mysqli;
	}
	
	public function mysqli() {
		if ($this->mysqli === null) {
			$this->mysqli = $this->connect();
		}
		
		return $this->mysqli;
	}
	
	/**
	 * Выполнение запроса
	 * @param Query $query
	 * @return Result
	 * @throws Exception
	 */
	public function executeQuery(Query $query) {
		$mysqli = $this->mysqli();
		
		$sql = $query->prepare([$mysqli, 'escape_string']);
		$mysqliResult = $mysqli->query($sql);
		
		if ( ! $mysqliResult) {
			throw new Exception('Не удалось выполнить запрос: '.$mysqli->error);
		}
		
		$rows = [];
		
		if ($mysqliResult instanceof \Mysqli_Result) {
			while ($row = $mysqliResult->fetch_assoc()) {
				$rows[] = $row;
			}
		}
		
		return new Result($sql, $rows, $mysqli->affected_rows, $mysqli->insert_id);
	}
	
	public function __destruct() {
		if ($this->mysqli) {
			$this->mysqli->close();
		}
	}
	
}
