<?php declare(strict_types=1);

namespace Mysql;

/**
 * Подключение к базе данных
 */
class Connection
{

	private $username;

	private $password;

	private $host;

	private $port;

	private $defaultDb = '';

	private $charset;

	private $tags;

	/** @var null|\Mysqli */
	private $mysqli;

	/** @var null|AsyncResult */
	private $asyncResult;

	public function __construct(
		string $username,
		string $password,
		string $host,
		int $port,
		array $tags
	)
	{
		$this->username = $username;
		$this->password = $password;
		$this->host = $host;
		$this->port = $port;
		$this->tags = $tags;
	}

	public function __destruct()
	{
		$this->disconnect();
	}

	/**
	 * Подключиться к бд
	 * @return self
	 * @throws Exception
	 */
	public function connect(): self
	{
		try {
			$mysqli = new \Mysqli($this->host, $this->username, $this->password, $this->defaultDb, $this->port);
		} catch (\Exception $e) {
			throw new Exception('Не удалось подключиться к базе данных: ' . $e->getMessage(), Exception::CODE_CONNECTION_ERROR, $e);
		}

		if ($mysqli->connect_errno != 0) {
			throw new Exception('Не удалось подключиться к базе данных: ' . $mysqli->connect_error, Exception::CODE_CONNECTION_ERROR);
		}

		$this->mysqli = $mysqli;

		if ($this->charset) {
			$this->charset($this->charset);
		}

		return $this;
	}

	/**
	 * Отключиться от бд
	 * @return bool
	 */
	public function disconnect(): bool
	{
		if ($this->mysqli) {
			$result = $this->mysqli->close();
		} else {
			$result = false;
		}

		return $result;
	}

	/**
	 * Установить кодировку
	 * @param string $charset
	 * @throws Exception
	 */
	public function charset(string $charset)
	{
		$this->charset = $charset;

		if ($this->mysqli) {
			$result = $this->mysqli->set_charset($this->charset);

			if ($result === false) {
				throw new Exception('Ошибка при установке кодировки', Exception::CODE_CHARSET_ERROR);
			}
		}
	}

	/**
	 * Установить базу данных по умолчанию
	 * @param string $dbName
	 * @throws Exception
	 */
	public function defaultDb(string $dbName)
	{
		$this->defaultDb = $dbName;

		if ($this->mysqli) {
			$result = $this->mysqli->select_db($this->defaultDb);

			if ($result === false) {
				throw new Exception('Ошибка при установке базы данных по умолчанию', Exception::CODE_DEFAULTDB_ERROR);
			}
		}
	}

	/**
	 * Выполнить запрос
	 * Значения параметров экранируются: строки заключаются в одинарные кавычки,
	 * булевы значения преобразуются в строки true и false, null-значения — в null,
	 * значения одномерных массивов разделяются запятыми,
	 * двумерные дополнительно заключаются в скобки,
	 * объекты приводятся к строке.
	 * @param string $sql
	 * @param array $params
	 * @return Result
	 * @throws Exception
	 */
	public function query(string $sql, array $params = []): Result
	{
		if (!$this->mysqli) {
			$this->connect();
		} elseif ($this->asyncResult) {
			$this->wait();
		}

		$preparedSql = $this->prepareSql($sql, $params);

		try {
			$mysqliResult = $this->mysqli->query($preparedSql);
		} catch (\Throwable $e) {
			throw new Exception(
				'Не удалось выполнить запрос: ' . $e->getMessage(),
				Exception::CODE_QUERY_ERROR,
				$e,
				$preparedSql
			);
		}

		return $this->createResult($mysqliResult, $preparedSql);
	}

	/**
	 * Выполнить асинхронный запрос
	 * Результат вернётся немедленно, однако реальные данные
	 * будут получены только при попытке доступа к ним.
	 * @param string $sql
	 * @param array $params
	 * @return AsyncResult
	 * @throws Exception
	 */
	public function asyncQuery(string $sql, array $params = []): AsyncResult
	{
		if (!$this->mysqli) {
			$this->connect();
		} elseif ($this->asyncResult) {
			$this->wait();
		}

		$preparedSql = $this->prepareSql($sql, $params);

		if (!$this->mysqli->query($preparedSql, \MYSQLI_ASYNC)) {
			throw new Exception(
				'Не удалось выполнить запрос: ' . $this->mysqli->error,
				Exception::CODE_QUERY_ERROR,
				null,
				$preparedSql
			);
		}

		$this->asyncResult = new AsyncResult($preparedSql, function () {
			$this->wait();
		});

		return $this->asyncResult;
	}

	/**
	 * Создать вспомогательный объект Table
	 * @param $name
	 * @param string $pk
	 * @return Table
	 */
	public function table($name, $pk = 'id'): Table
	{
		return new Table($this, $name, $pk);
	}

	/**
	 * Стартовать транзакцию
	 * @return bool
	 * @throws Exception
	 */
	public function startTransaction(): bool
	{
		if (!$this->mysqli) {
			$this->connect();
		} elseif ($this->asyncResult) {
			$this->wait();
		}

		return $this->mysqli->autocommit(false);
	}

	/**
	 * Закоммитить транзакцию
	 * @return bool
	 * @throws Exception
	 */
	public function commitTransaction(): bool
	{
		if (!$this->mysqli) {
			$this->connect();
		} elseif ($this->asyncResult) {
			$this->wait();
		}

		$result = $this->mysqli->commit();
		$this->mysqli->autocommit(true);

		return $result;
	}

	/**
	 * Откатить транзакцию
	 * @return bool
	 * @throws Exception
	 */
	public function rollbackTransaction(): bool
	{
		if (!$this->mysqli) {
			$this->connect();
		} elseif ($this->asyncResult) {
			$this->wait();
		}

		$result = $this->mysqli->rollback();
		$this->mysqli->autocommit(true);

		return $result;
	}

	public function getTags()
	{
		return $this->tags;
	}

	/**
	 * @return \Mysqli|false
	 */
	public function getAsyncMysqli()
	{
		return $this->asyncResult? $this->mysqli: false;
	}

	/**
	 * Синхронизировать, получив результат асинхронного запроса
	 * @return self
	 * @throws Exception
	 */
	public function sync(): self
	{
		if ($this->asyncResult) {
			$result = $this->mysqli->reap_async_query();
			$this->asyncResult->setResult($this->createResult($result, $this->asyncResult->sql()));
			$this->asyncResult = null;
		}

		return $this;
	}

	/**
	 * @param array $mysqliConnections
	 * @return \Mysqli
	 */
	public static function poll(array $mysqliConnections): \Mysqli
	{
		do {
			$read = $error = $reject = $mysqliConnections;
			\Mysqli::poll($read, $error, $reject, 1);
			$all = array_merge($read, $error, $reject);
		} while (!$all);

		return $all[0];
	}

	/**
	 * @throws Exception
	 */
	private function wait()
	{
		self::poll([$this->mysqli]);
		$this->sync();
	}

	/**
	 * Подготовить SQL
	 * Меняет плэйсхолдеры на экранированные значения.
	 * @param string $sql
	 * @param array $params
	 * @return string
	 * @throws Exception
	 */
	private function prepareSql(string $sql, array $params): string
	{
		$replacePairs = [];
		foreach ($params as $name => $val) {
			$replacePairs[$name] = $this->quote($val);
		}

		return strtr($sql, $replacePairs);
	}

	/**
	 * Экранировать значение
	 * @param mixed $val
	 * @return string
	 * @throws Exception
	 */
	private function quote($val): string
	{
		if (is_string($val)) {
			$str = $this->mysqli->escape_string($val);
			$quoted = "'$str'";
		} elseif (is_int($val)) {
			$quoted = (string)$val;
		} elseif (is_float($val)) {
			$quoted = sprintf('%F', $val);
		} elseif (is_bool($val)) {
			$quoted = $val ? 'true' : 'false';
		} elseif (is_null($val)) {
			$quoted = 'null';
		} elseif (is_array($val)) {
			$quotedArr = array();

			foreach ($val as $innerVal) {
				$str = $this->quote($innerVal);

				if (is_array($innerVal)) {
					$quotedArr[] = "($str)";
				} else {
					$quotedArr[] = $str;
				}
			}

			$quoted = implode(', ', $quotedArr);
		} else {
			try {
				$strVal = (string)$val;
			} catch (\Throwable $e) {
				throw new Exception($e->getMessage(), Exception::CODE_QUOTE_ERROR, $e);
			}

			$quoted = $this->quote($strVal);
		}

		return $quoted;
	}

	/**
	 * @param mixed $mysqliResult
	 * @param string $preparedSql
	 * @return Result
	 * @throws Exception
	 */
	private function createResult($mysqliResult, string $preparedSql): Result
	{
		if (!$mysqliResult) {
			throw new Exception(
				'Не удалось выполнить запрос: ' . $this->mysqli->error,
				Exception::CODE_QUERY_ERROR,
				null,
				$preparedSql
			);
		}

		$rows = [];
		if ($mysqliResult instanceof \Mysqli_Result) {
			$rows = $mysqliResult->fetch_all(\MYSQLI_ASSOC);
			$mysqliResult->free();
		}

		return new Result($preparedSql, $rows, $this->mysqli->affected_rows, $this->mysqli->insert_id);
	}

}

