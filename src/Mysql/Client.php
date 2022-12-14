<?php declare(strict_types=1);

namespace Mysql;

/**
 * Клиент базы данных MySQL
 * Обёртка Mysqli. Поддерживает несколько подключений к MySQL
 * и асинхронные запросы в синхронном стиле.
 */
class Client
{

	/** @var Pool */
	private $pool;

	public function __construct(Pool $pool)
	{
		$this->pool = $pool;
	}

	/**
	 * Создать клиент на основе конфига подключения к MySQL
	 * @see Client::pool()
	 * @param array $config
	 * @return self
	 * @throws Exception
	 */
	public static function init(array $config): self
	{
		return self::pool([$config]);
	}

	/**
	 * Создать клиент на основе нескольких конфигов подключения к MySQL
	 * Конфиг подключения поддерживает следующие ключи:
	 * user -- string имя пользователя
	 * password -- string пароль пользователя
	 * [host] -- string хост (localhost)
	 * [port] -- int порт (3306)
	 * [defaultDb] -- string|null имя базы данных по умолчанию (null)
	 * [charset] -- string|null кодировка подключения (null)
	 * [lazy] -- bool подключаться не сразу, а по требованию (false)
	 * [tags] -- string[] тэги ([])
	 * @param array $configs
	 * @return self
	 * @throws Exception
	 */
	public static function pool(array $configs): self
	{
		$defaults = [
			'user' => '',
			'password' => '',
			'host' => 'localhost',
			'port' => 3306,
			'tags' => [],
			'defaultDb' => null,
			'charset' => null,
			'lazy' => false,
		];
		$connections = [];
		foreach ($configs as $config) {
			$c = array_replace($defaults, $config);
			$diff = array_diff(array_keys($c), array_keys($defaults));
			if ($diff) {
				throw new Exception(sprintf('Неожиданный параметр подключения %s', implode(', ', $diff)));
			}

			$connection = new Connection(
				$c['user'],
				$c['password'],
				$c['host'],
				$c['port'],
				$c['tags']
			);
			if (isset($c['defaultDb'])) {
				$connection->defaultDb($config['defaultDb']);
			}
			if (isset($c['charset'])) {
				$connection->charset($config['charset']);
			}

			if (!$c['lazy']) {
				$connection->connect();
			}
			$connections[] = $connection;
		}

		return new self(new Pool($connections));
	}

	/**
	 * Выбрать подходящее подключение и выполнить на нём запрос
	 * @see Connection::query()
	 * @param string $sql
	 * @param array $params
	 * @param string[] $tags
	 * @return Result
	 * @throws Exception
	 */
	public function query(string $sql, array $params = [], array $tags = []): Result
	{
		$connection = $this->pool->getFreeConnection($tags);

		return $connection->query($sql, $params);
	}

	/**
	 * Выбрать подходящее подключение и выполнить на нём асинхронный запрос
	 * @see Connection::asyncQuery()
	 * @param string $sql
	 * @param array $params
	 * @param string[] $tags
	 * @return AsyncResult
	 * @throws Exception
	 */
	public function asyncQuery(string $sql, array $params = [], array $tags = []): AsyncResult
	{
		$connection = $this->pool->getFreeConnection($tags);

		return $connection->asyncQuery($sql, $params);
	}

	/**
	 * Выбрать подходящее подключение и создать на нём объект Table
	 * @see Table
	 * @param string $name
	 * @param string $pk
	 * @param array $tags
	 * @return Table
	 */
	public function table(string $name, string $pk = 'id', array $tags = []): Table
	{
		$connection = $this->pool->getFreeConnection($tags);

		return $connection->table($name, $pk);
	}

	/**
	 * Выбрать подходящее подключение и запустить на нём транзакцию
	 * В качества аргумента принимает функцию типа
	 * function(\Mysql\Client $db, callable $commit, callable $rollback) { ... }
	 * Исключение внутри функции вызовет автоматический откат транзакции, завершение без ошибок -- автоматический коммит.
	 * @param callable $func
	 * @param array $tags
	 * @return mixed результат вызова $func
	 * @throws Exception
	 */
	public function transaction(callable $func, array $tags = [])
	{
		$connection = $this->pool->getFreeConnection($tags);
		$connection->startTransaction();

		$commitResult = null;
		$commit = static function () use ($connection, &$commitResult): bool {
			$commitResult = $connection->commitTransaction();
			return $commitResult;
		};
		$rollbackResult = null;
		$rollback = static function () use ($connection, &$rollbackResult): bool {
			$rollbackResult = $connection->rollbackTransaction();
			return $rollbackResult;
		};

		try {
			$result = $func($connection, $commit, $rollback);
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

}
