<?php declare(strict_types=1);

namespace tests\Mysql;

class AsyncQueryTest extends MysqlTestCase
{
	public function testAsync()
	{
		$db = \Mysql\Client::pool([
			[
				'user' => 'root',
				'password' => 'root',
				'host' => $this->host,
				'defaultDb' => 'sakiladb',
				'charset' => 'utf8',
			],
			[
				'user' => 'root',
				'password' => 'root',
				'host' => $this->host,
				'defaultDb' => 'sakiladb',
				'charset' => 'utf8',
			],
		]);
		$start = microtime(true);
		$r1 = $db->asyncQuery('select sleep(1) as `a`');
		$r2 = $db->asyncQuery('select sleep(1) as `b`');
		sleep(1);
		$this->assertSame([['a' => '0']], $r1->rows());
		$this->assertSame([['b' => '0']], $r2->rows());
		$this->assertLessThan(2, microtime(true) - $start);
	}
}