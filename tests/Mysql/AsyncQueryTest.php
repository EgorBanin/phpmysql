<?php declare(strict_types=1);

namespace tests\Mysql;

use Mysql\Connection;
use Mysql\Result;

class AsyncQueryTest extends MysqlTestCase
{

	private $pool;

	public function setUp()
	{
		parent::setUp();
		$this->pool = \Mysql\Client::pool([
			$this->getConnectionOptions(),
			$this->getConnectionOptions(),
		]);
	}

	public function testAsync()
	{
		$start = microtime(true);
		$r1 = $this->pool->asyncQuery('select sleep(1) as `a`');
		$this->assertLessThan(
			1,
			microtime(true) - $start,
			'асинхронный запрос не ждёт результатов'
		);
		$r2 = $this->pool->asyncQuery('select sleep(1) as `b`');
		$this->assertLessThan(
			1,
			microtime(true) - $start,
			'второй асинхронный запрос уходит на свободное подключение и тоже не ждёт'
		);
		sleep(1);
		$this->assertSame([['a' => '0']], $r1->rows());
		$this->assertSame([['b' => '0']], $r2->rows());
		$this->assertLessThan(
			2,
			microtime(true) - $start,
			'оба запроса по секунде и секунда слипа завершатся менее чем за две секунды'
		);
	}

	/**
	 * Тест поведения асинхронных запросов и транзакций
	 * В общем случае использовать асинхронные запросы в рамках транзакции не имеет смысла.
	 * Однако ограничений на такое использование нет,
	 * соединение будет синхронизировано перед стартом и перед завершением транзакции.
	 * @throws \Mysql\Exception
	 */
	public function testAsyncTransaction()
	{
		// case: данные асинхронного запроса получены внутри функции транзакции
		$rows = $this->db()->transaction(function ($conn) {
			$this->assertInstanceOf(Connection::class, $conn);
			$r = $conn->asyncQuery('select 1 as `ok`');
			return $r->rows(); // доступ к данным синхронизирует подключение
		});
		$this->assertSame([['ok' => '1']], $rows);

		// case: данные асинхронного запроса будут получены автоматически перед коммитом
		$getResult = function () {
			return $this->result;
		};
		$result = $this->db()->transaction(function ($conn) use (&$getResult) {
			$this->assertInstanceOf(Connection::class, $conn);
			$result = $conn->asyncQuery('select 1 as `ok`');
			$getResult = $getResult->bindTo($result, $result);
			$this->assertEquals(
				null,
				$getResult(),
				'пока данные не получены, результат не задан'
			);

			return $result; // данные не получены, но при коммите соединение дождётся результата
		});
		$this->assertInstanceOf(
			Result::class,
			$getResult(),
			'перед коммитом транзакции данные были получены'
		);
		$this->assertSame([['ok' => '1']], $result->rows());

		// case: данные асинхронного запроса будут получены автоматически перед откатом
		$getResult = function () {
			return $this->result;
		};
		$this->db()->transaction(function(
			Connection $conn,
			callable $commit,
			callable $rollback
		) use(&$getResult) {
			$result = $conn->asyncQuery('select 1 as `ok`');
			$getResult = $getResult->bindTo($result, $result);
			$this->assertEquals(
				null,
				$getResult(),
				'пока данные не получены, результат не задан'
			);
			$rollback(); // данные не получены, но при откате соединение дождётся результата
		});
		$getResult = $getResult->bindTo($result, $result);
		$this->assertInstanceOf(
			Result::class,
			$getResult(),
			'перед откатом транзакции данные были получены'
		);
		$this->assertSame([['ok' => '1']], $result->rows());

		// case: данные асинхронного запроса не получены до старта транзакции на том же подключении
		$db = $this->db();
		$result = $db->asyncQuery('select 1 as `ok`');
		$rows = $db->transaction(function() use($db) { // перед стартом подключение дождётся ответа на предыдущий асинхронный запрос
			$result = $db->asyncQuery('select 2 as `ok`');

			return $result->rows();
		});
		$this->assertSame([['ok' => '1']], $result->rows());
		$this->assertSame([['ok' => '2']], $rows);
	}
}