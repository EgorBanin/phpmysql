<?php declare(strict_types=1);

namespace Mysql;

/**
 * Пул подключений
 */
class Pool
{
	/** @var Connection[] */
	private $connections = [];

	public function __construct(array $connections)
	{
		$this->connections = $connections;
	}

	/**
	 * @param array $tags
	 * @return Connection
	 * @throws Exception
	 */
	public function getFreeConnection(array $tags): Connection
	{
		$freeConnection = null;
		$mysqliConnections = [];
		foreach ($this->connections as $connection) {
			if ($tags && !array_intersect($tags, $connection->getTags())) {
				continue;
			}
			$mysqli = $connection->getAsyncMysqli();
			if ($mysqli) {
				$mysqliConnections[] = $mysqli;
			} else {
				$freeConnection = $connection;
				break;
			}
		}

		if (!$freeConnection) {
			if ($mysqliConnections) {
				$freeConnection = $this->waitForFree($mysqliConnections);
			} else {
				throw new Exception(sprintf(
					'Не найдено подходящих подключений %s',
					implode(', ', $tags)
				));
			}
		}

		return $freeConnection;
	}

	/**
	 * @param \Mysqli[] $mysqliConnections
	 * @return Connection
	 * @throws Exception
	 */
	private function waitForFree(array $mysqliConnections): Connection
	{
		$mysqli = Connection::poll($mysqliConnections);
		$connection = null;
		foreach ($this->connections as $connection) {
			if ($connection->getAsyncMysqli() === $mysqli) {
				$connection =  $connection->sync();
				break;
			}
		}

		if (!$connection) {
			throw new Exception('Подключение не зарегистрировано в пуле');
		}

		return $connection;
	}

}