<?php

use
	Mysql\Mysql,
	Mysql\Result;

/**
 * Тест сравнивает ожидаемые запросы с теми, что попадают в лог запросов MySQL.
 * Также проверяются результаты.
 */
class MysqlTest extends PHPUnit_Framework_TestCase {
	
	private static $mysqlUser = 'root';
	private static $mysqlPassword = '123456';
	
	private static $tmpDir = '/tmp/mysqllog';
	
	private static $mysqlLog;
	
	public static function setUpBeforeClass() {
		$command = 'mysql -u'.self::$mysqlUser.' -p'.self::$mysqlPassword.' < '.__DIR__.'/sql/setUp.sql';
		exec($command);
		
		// Предпологается, что mysqld может создавать файлы в tmp директории.
		// Например в Ubuntu AppArmor, настроенный по умолчанию, допускает такое.
		mkdir(self::$tmpDir);
		exec('chmod 2777 '.self::$tmpDir); // не получилось установить SGID по другому
		self::$mysqlLog = self::$tmpDir.'/mysql.log';
	}
	
	public static function tearDownAfterClass() {
		$command = 'mysql -u'.self::$mysqlUser.' -p'.self::$mysqlPassword.' < '.__DIR__.'/sql/tearDown.sql';
		exec($command);
		@unlink(self::$mysqlLog);
		rmdir(self::$tmpDir);
	}
	
	private function assertLogEndsWith($string) {
		$this->assertStringEndsWith(trim($string)."\n", file_get_contents(self::$mysqlLog));
	}
	
	public function testQuery() {
		$db = new Mysql('sakila', 'password123', 'localhost');
		$db->query('
			set global
				general_log_file = :logFile,
				general_log = 1;
		', [':logFile' => self::$mysqlLog]);
		$this->assertFileExists(self::$mysqlLog);
		
		$db->defaultDb('sakilaDb');
		$this->assertLogEndsWith('Init DB	sakilaDb');
		
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
			engine InndoDB
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
			engine InndoDB
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
			engine InndoDB
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
		$this->setExpectedException('\Mysql\Exception');
		$db->query('');
		$db->query('xxx');
	}
	
	public function testQuote() {
		$db = new Mysql('sakila', 'password123', 'localhost');
		
		$reflectionMysql = new ReflectionClass('Mysql\Mysql');
		$quote = $reflectionMysql->getMethod('quote');
		$quote->setAccessible(true);
		
		$this->assertSame("'Foo'", $quote->invoke($db, 'Foo'));
		$this->assertSame("'\\'Bar\\''", $quote->invoke($db, "'Bar'"));
		$this->assertSame('123', $quote->invoke($db, 123));
		$this->assertSame('-123', $quote->invoke($db, -123));
		$this->assertSame('0.500000', $quote->invoke($db, 0.5));
		$this->assertSame('true', $quote->invoke($db, true));
		$this->assertSame('false', $quote->invoke($db, false));
		$this->assertSame('null', $quote->invoke($db, null));
		$this->assertSame('\'Baz\', 0', $quote->invoke($db, ['Baz', 0]));
		$this->assertSame('(\'qux\', 1), (\'quux\', 2)', $quote->invoke($db, [['qux', 1], ['quux', 2]]));
		$this->assertSame("'Hello world'", $quote->invoke($db, new \SimpleXmlElement('<root>Hello world</root>')));
		
		$this->setExpectedException('\Mysql\Exception');
		$quote->invoke($db, new \stdClass);
	}
	
}
