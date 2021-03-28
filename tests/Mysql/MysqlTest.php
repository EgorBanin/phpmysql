<?php

namespace tests\Mysql;

/**
 * Тест сравнивает ожидаемые запросы с теми, что попадают в лог запросов MySQL.
 * Также проверяются результаты.
 */
class MysqlTest extends MysqlTestCase
{

	private static $mysqlLog = '/var/log/mysql-log/mysql.log';

	private function assertLogEndsWith($string)
	{
		$this->assertStringEndsWith(trim($string) . "\n", file_get_contents(self::$mysqlLog));
	}

	public function testQuery()
	{
		$db = \Mysql\Client::init([
			'user' => 'root',
			'password' => 'root',
			'host' => $this->host,
			'defaultDb' => 'sakiladb',
			'charset' => 'utf8',
		]);
		$db->query('
			set global
				general_log_file = :logFile,
				general_log = 1;
		', [':logFile' => self::$mysqlLog]);
		$this->assertFileExists(self::$mysqlLog);

//		$db->defaultDb('sakiladb');
//		$this->assertLogEndsWith('Init DB sakiladb');

//		$db->charset('utf8');
//		$this->assertLogEndsWith('SET NAMES utf8');

		// создание таблицы
		$result = $db->query('
			create table `Book` (
				`id` int unsigned not null auto_increment,
				`ISBN` char(17) binary not null,
				`title` varchar(255) not null,
				`author` varchar(255) not null,
				primary key (`id`),
				unique key `ISBN` (`ISBN`)
			)
			engine InnoDB
			comment \'Книги\';
		');
		$this->assertLogEndsWith('
			create table `Book` (
				`id` int unsigned not null auto_increment,
				`ISBN` char(17) binary not null,
				`title` varchar(255) not null,
				`author` varchar(255) not null,
				primary key (`id`),
				unique key `ISBN` (`ISBN`)
			)
			engine InnoDB
			comment \'Книги\'
		');
		$this->assertEquals(new \Mysql\Result('
			create table `Book` (
				`id` int unsigned not null auto_increment,
				`ISBN` char(17) binary not null,
				`title` varchar(255) not null,
				`author` varchar(255) not null,
				primary key (`id`),
				unique key `ISBN` (`ISBN`)
			)
			engine InnoDB
			comment \'Книги\';
		', [], 0, 0), $result);

		// insert
		$result = $db->query('
			insert into `Book` (
				`ISBN`, `title`, `author`
			) values :values;
		', [':values' => [
			['978-5-7502-0064-1', 'Совершенный код', 'Стив Макконнелл'],
			['978-5-93286-153-0', 'MySQL. Оптимизация производительности', 'Бэрон Шварц, Петр Зайцев, Вадим Ткаченко, Джереми Д. Зооднай, Дерек Дж. Баллинг, Арьен Ленц']
		]]);
		$this->assertLogEndsWith('
			insert into `Book` (
				`ISBN`, `title`, `author`
			) values (\'978-5-7502-0064-1\', \'Совершенный код\', \'Стив Макконнелл\'), (\'978-5-93286-153-0\', \'MySQL. Оптимизация производительности\', \'Бэрон Шварц, Петр Зайцев, Вадим Ткаченко, Джереми Д. Зооднай, Дерек Дж. Баллинг, Арьен Ленц\')
		');
		$this->assertEquals(new \Mysql\Result('
			insert into `Book` (
				`ISBN`, `title`, `author`
			) values (\'978-5-7502-0064-1\', \'Совершенный код\', \'Стив Макконнелл\'), (\'978-5-93286-153-0\', \'MySQL. Оптимизация производительности\', \'Бэрон Шварц, Петр Зайцев, Вадим Ткаченко, Джереми Д. Зооднай, Дерек Дж. Баллинг, Арьен Ленц\');
		', [], 2, 1), $result);

		// select
		$result = $db->query('
			select *
			from `Book`
			where `ISBN` = :ISBN;
		', [':ISBN' => '978-5-93286-153-0']);
		$this->assertLogEndsWith('
			select *
			from `Book`
			where `ISBN` = \'978-5-93286-153-0\'
		');
		$this->assertEquals(new \Mysql\Result('
			select *
			from `Book`
			where `ISBN` = \'978-5-93286-153-0\';
		', [[
			'id' => 2,
			'ISBN' => '978-5-93286-153-0',
			'title' => 'MySQL. Оптимизация производительности',
			'author' => 'Бэрон Шварц, Петр Зайцев, Вадим Ткаченко, Джереми Д. Зооднай, Дерек Дж. Баллинг, Арьен Ленц'
		]], 1, 0), $result);

		$result = $db->query('
			insert into `Book` set
			`ISBN` = :ISBN,
			`title` = :title,
			`author` = :author;
		', [
			':ISBN' => '000-0-0000-0000-0',
			':title' => "\"'\\/?&%@=>;\0",
			':author' => ''
		]);
		$this->assertLogEndsWith('
			insert into `Book` set
			`ISBN` = \'000-0-0000-0000-0\',
			`title` = \'\\"\\\'\\\\/?&%@=>;\\0\',
			`author` = \'\'
		');
		$this->assertEquals(new \Mysql\Result('
			insert into `Book` set
			`ISBN` = \'000-0-0000-0000-0\',
			`title` = \'\\"\\\'\\\\/?&%@=>;\\0\',
			`author` = \'\';
		', [], 1, 3), $result);

		// exception
		$actualException = null;
		try {
			$db->query('');
		} catch (\Mysql\Exception $actualException) {
		}
		$this->assertNotNull($actualException);

		$actualException = null;
		try {
			$db->query('xxx :var', [':var' => 'foo']);
		} catch (\Mysql\Exception $actualException) {
		}
		$this->assertNotNull($actualException);
		$this->assertSame('xxx \'foo\'', $actualException->sql);
	}

	public function testQuote()
	{
		$conn = new \Mysql\Connection($this->user, $this->password, $this->host, 3306, []);
		$conn->connect();
		$quote = new \ReflectionMethod(\Mysql\Connection::class, 'quote');
		$quote->setAccessible(true);

		$this->assertSame("'Foo'", $quote->invoke($conn, 'Foo'));
		$this->assertSame("'\\'Bar\\''", $quote->invoke($conn, "'Bar'"));
		$this->assertSame('123', $quote->invoke($conn, 123));
		$this->assertSame('-123', $quote->invoke($conn, -123));
		$this->assertSame('0.500000', $quote->invoke($conn, 0.5));
		$this->assertSame('true', $quote->invoke($conn, true));
		$this->assertSame('false', $quote->invoke($conn, false));
		$this->assertSame('null', $quote->invoke($conn, null));
		$this->assertSame('\'Baz\', 0', $quote->invoke($conn, ['Baz', 0]));
		$this->assertSame('(\'qux\', 1), (\'quux\', 2)', $quote->invoke($conn, [['qux', 1], ['quux', 2]]));
		$this->assertSame("'Hello world'", $quote->invoke($conn, new \SimpleXmlElement('<root>Hello world</root>')));

		$e = null;
		try {
			$quote->invoke($conn, new \stdClass);
		} catch (\Mysql\Exception $e) {
		}
		$this->assertInstanceOf(\Mysql\Exception::class, $e);
	}

	public function testDefaultDb()
	{
		$conn = new \Mysql\Connection($this->user, $this->password, $this->host, 3306, []);
		$conn->query('select 1');

		$conn->defaultDb($this->dbName);

		$e = null;
		try {
			$conn->defaultDb('bad db');
		} catch (\Mysql\Exception $e) {
		}
		$this->assertInstanceOf(\Mysql\Exception::class, $e);
	}

	public function testCharset()
	{
		$conn = new \Mysql\Connection($this->user, $this->password, $this->host, 3306, []);
		$conn->query('select 1');

		$conn->charset('utf8');

		$e = null;
		try {
			$conn->charset('bad charset');
		} catch (\Mysql\Exception $e) {
		}
		$this->assertInstanceOf(\Mysql\Exception::class, $e);
	}

}
