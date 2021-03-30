<?php

namespace tests\Mysql;

class ClientTest extends MysqlTestCase
{

	protected $db;

	public function setUp()
	{
		parent::setUp();
		$this->db = $this->db();
		$this->db->query('drop table if exists `foobar`');
		$this->db->query('
			create table `foobar` (
				`id` int unsigned not null auto_increment,
				`title` varchar(255) not null,
				`content` varchar(10000) not null,
				`ct` int unsigned not null comment "timestamp",
				`ut` int unsigned not null comment "timestamp",
				primary key (`id`),
				key `ct` (`ct`)
			);
		');
		$this->db->query('
			insert into `foobar`
				(`title`, `content`, `ct`, `ut`)
			values :values
		', [':values' => [
			['Foo', 'Foo content', 1438168960, 1438168960],
			['Bar', 'Bar content', 1438168960, 1438168961],
			['Baz', 'Baz content', 1438168961, 1438168962],
			['Qux', 'Qux content', 1438168961, 1438168963],
		]]);
	}

	public function testTransaction()
	{
		$this->assertArraySubset([
			'id' => '1',
			'ut' => '1438168960',
		], $this->db->table('foobar')->get(1));

		$this->db->transaction(function (\Mysql\Connection $db) {
			$db->query('delete from `foobar` where `id` = 1');
			$db->query('
				update `foobar`
				set `ut` = :ut
				where `id` = :id
			', [':ut' => 0, ':id' => 1]);
		});

		$this->assertSame(null, $this->db->table('foobar')->get(1));
	}

	public function testBadTransaction()
	{
		$this->assertSame([
			'id' => '1',
			'ut' => '1438168960',
		], $this->db->query('
			select
				`id`,
				`ut`
			from `foobar`
			where id = 1
		')->row());

		try {
			$this->db->transaction(function (\Mysql\Connection $db) {
				$db->query('delete from `foobar` where `id` = 1');
				$db->query('
					update `foobar`
					set `ut` = :ut
					where `id` = :id
				', [':ut' => 0, ':id' => 1]);
				$db->query('wrong query');
			});
		} catch (\Mysql\Exception $e) {
			//
		}

		$this->assertSame([
			'id' => '1',
			'ut' => '1438168960',
		], $this->db->query('
			select
				`id`,
				`ut`
			from `foobar`
			where id = 1
		')->row());
	}

	public function testTransactionCommit()
	{
		$this->assertArraySubset([
			'id' => '1',
			'ut' => '1438168960',
		], $this->db->table('foobar')->get(1));

		try {
			$this->db->transaction(function (\Mysql\Connection $db, callable $commit) {
				$db->query('delete from `foobar` where `id` = 1');
				throw new \Exception('Транзакция откатится так как не зафиксирована');
			});
		} catch (\Mysql\Exception $e) {
		}
		$this->assertNotNull($this->db->table('foobar')->get(1));

		try {
			$this->db->transaction(function (\Mysql\Connection $db, callable $commit) {
				$db->query('delete from `foobar` where `id` = 1');
				$commit();
				throw new \Exception('Транзакция не откатится так как уже зафиксирована');
			});
		} catch (\Mysql\Exception $e) {
		}
		$this->assertNull($this->db->table('foobar')->get(1));
	}

	public function testTransactionRollback()
	{
		$this->assertArraySubset([
			'id' => '1',
			'ut' => '1438168960',
		], $this->db->table('foobar')->get(1));

		$this->db->transaction(function (\Mysql\Connection $db, callable $commit, callable $rollback) {
			$db->query('delete from `foobar` where `id` = 1');
			$rollback();
		});
		$this->assertNotNull($this->db->table('foobar')->get(1));
	}

}
