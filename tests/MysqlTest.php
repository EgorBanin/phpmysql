<?php

use Mysql\Mysql,
	Mysql\Result;

/**
 * Тест сравнивает ожидаемые запросы с теми, что попадают в лог запросов MySQL.
 * Также проверяются результаты.
 */
class MysqlTest extends PHPUnit_Framework_TestCase {
	
	private static $tmpDir;
	
	private static $mysqlLog;
	
	public static function setUpBeforeClass() {
		$command = 'mysql -uroot -p123456 < '.__DIR__.'/sql/setUp.sql';
		exec($command);
		
		// Предпологается, что mysqld может создавать файлы в tmp директории пользователя.
		// Например в Ubuntu AppArmor, настроенный по умолчанию, допускает такое.
		self::$tmpDir = getenv('HOME').'/tmp/mysqllog';
		mkdir(self::$tmpDir);
		exec('chmod 2777 '.self::$tmpDir); // не получилось установить SGID по другому
		self::$mysqlLog = self::$tmpDir.'/mysql.log';
	}
	
	public static function tearDownAfterClass() {
		$command = 'mysql -uroot -p123456 < '.__DIR__.'/sql/tearDown.sql';
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
		$this->assertEquals(new Result([], 0, null), $result);
		
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
		$this->assertEquals(new Result([], 2, 1), $result);
		
		// select
		$result = $db->query('
			select *
			from `Book`
			where `ISBN` = :ISBN
		', [':ISBN' => '978-5-93286-153-0']);
		$this->assertLogEndsWith('
			select *
			from `Book`
			where `ISBN` = \'978-5-93286-153-0\'
		');
		$this->assertEquals(new Result([[
			'id' => 2,
			'ISBN' => '978-5-93286-153-0',
			'title' => 'MySQL. Оптимизация производительности',
			'author' => 'Бэрон Шварц, Петр Зайцев, Вадим Ткаченко, Джереми Д. Зооднай, Дерек Дж. Баллинг, Арьен Ленц'
		]], 1, null), $result);
	}
	
}
