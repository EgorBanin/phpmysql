<?php

namespace Mysql;

/**
 * Тест сравнивает ожидаемые запросы с теми, что попадают в лог запросов MySQL.
 * Также проверяются результаты.
 */
class MysqlTest extends MysqlTestCase {
	
	//private static $tmpDir = 'C:/tmp/mysqllog';
	private static $tmpDir = '/tmp/mysqllog';
	
	private static $mysqlLog;
	
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		
		mkdir(self::$tmpDir);
		if (stripos(PHP_OS, 'win') !== 0) {
			@exec('chmod 2777 '.self::$tmpDir); // не получилось установить SGID по-другому
		}
		self::$mysqlLog = self::$tmpDir.'/mysql.log';
	}
	
	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();

		@unlink(self::$mysqlLog);
		rmdir(self::$tmpDir);
	}
	
	private function assertLogEndsWith($string) {
		$this->assertStringEndsWith(trim($string)."\n", file_get_contents(self::$mysqlLog));
	}
	
	public function testQuery() {
		$db = Client::init('sakila', 'password123', 'localhost');
		$db->query('
			set global
				general_log_file = :logFile,
				general_log = 1;
		', [':logFile' => self::$mysqlLog]);
		$this->assertFileExists(self::$mysqlLog);
		
		$db->defaultDb('sakiladb');
		$this->assertLogEndsWith('Init DB	sakiladb');
		
		$db->charset('utf8');
		$this->assertLogEndsWith('SET NAMES utf8');
		
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
		$this->assertEquals(new Result('
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
		$this->assertEquals(new Result('
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
		$this->assertEquals(new Result('
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
		$this->assertEquals(new Result('
			insert into `Book` set
			`ISBN` = \'000-0-0000-0000-0\',
			`title` = \'\\"\\\'\\\\/?&%@=>;\\0\',
			`author` = \'\';
		', [], 1, 3), $result);
		
		// exception
		try {
			$db->query('');
			$this->fail('Expected exception not thrown');
		} catch (Exception $e) {}
		
		try {
			$db->query('xxx :var', [':var' => 'foo']);
			$this->fail('Expected exception not thrown');
		} catch (Exception $e) {
			$this->assertSame('xxx \'foo\'', $e->sql);
		}
	}
	
	public function testQuote() {
		$conn = new Connection('sakila', 'password123', 'localhost', 3306);
		
		$this->assertSame("'Foo'", $conn->quote('Foo'));
		$this->assertSame("'\\'Bar\\''", $conn->quote("'Bar'"));
		$this->assertSame('123', $conn->quote(123));
		$this->assertSame('-123', $conn->quote(-123));
		$this->assertSame('0.500000', $conn->quote(0.5));
		$this->assertSame('true', $conn->quote(true));
		$this->assertSame('false', $conn->quote(false));
		$this->assertSame('null', $conn->quote(null));
		$this->assertSame('\'Baz\', 0', $conn->quote(['Baz', 0]));
		$this->assertSame('(\'qux\', 1), (\'quux\', 2)', $conn->quote([['qux', 1], ['quux', 2]]));
		$this->assertSame("'Hello world'", $conn->quote(new \SimpleXmlElement('<root>Hello world</root>')));
		
		$e = null;
		try {
			$conn->quote(new \stdClass);
		} catch(Exception $e) {}
		$this->assertInstanceOf(Exception::class, $e);
	}
	
	public function testDefaultDb() {
		$conn = new Connection('sakila', 'password123', 'localhost', 3306);
		$conn->query('select 1');
		
		$conn->defaultDb('sakiladb');
		
		$e = null;
		try {
			$conn->defaultDb('bad db');
		} catch (Exception $e) {}
		$this->assertInstanceOf(Exception::class, $e);
	}
	
	public function testCharset() {
		$conn = new Connection('sakila', 'password123', 'localhost', 3306);
		$conn->query('select 1');
		
		$conn->charset('utf8');
		
		$e = null;
		try {
			$conn->charset('bad charset');
		} catch (Exception $e) {}
		$this->assertInstanceOf(Exception::class, $e);
	}
	
}
