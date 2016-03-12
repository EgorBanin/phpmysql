<?php

namespace Mysql;

class TableTest extends \PHPUnit_Framework_TestCase {
	
	protected $db;
	
	public function setUp() {
		parent::setUp();
		$this->db = Client::init('', '')
			->defaultDb('test')
			->charset('utf8');
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
	
	public function tearDown() {
		$this->db->query('drop table if exists `foobar`');
		parent::tearDown();
	}
	
	public function testSelect() {
		$t = new Table($this->db, 'foobar');
		
		$this->assertSame([
			[
				'id' => '1',
				'title' => 'Foo',
				'content' => 'Foo content',
				'ct' => '1438168960',
				'ut' => '1438168960',
			],
			[
				'id' => '2',
				'title' => 'Bar',
				'content' => 'Bar content',
				'ct' => '1438168960',
				'ut' => '1438168961',
			],
			[
				'id' => '3',
				'title' => 'Baz',
				'content' => 'Baz content',
				'ct' => '1438168961',
				'ut' => '1438168962',
			],
			[
				'id' => '4',
				'title' => 'Qux',
				'content' => 'Qux content',
				'ct' => '1438168961',
				'ut' => '1438168963',
			],
		], $t->select());
		
		$this->assertSame([
			[
				'id' => '1',
			],
		], $t->select(['id' => 1], ['id']));
		
		$this->assertSame([
			[
				'id' => '4',
				'title' => 'Qux',
			],
			[
				'id' => '3',
				'title' => 'Baz',
			],
		], $t->select([], ['id', 'title'], ['id' => 'desc'], 2));
		$this->assertSame([
			[
				'id' => '3',
				'title' => 'Baz',
			],
			[
				'id' => '4',
				'title' => 'Qux',
			],
		], $t->select([], ['id', 'title'], ['ct' => 'desc', 'id' => 'asc'], 2));
		
		$this->assertSame([
			['id' => '3'],
		], $t->select([['id' => ['$gt' => 2]], ['id' => ['$lt' => 4]]], ['id']));

		$this->assertSame([
			['id' => '4'],
		], $t->select(['content' => ['$like' => '%ux%']], ['id']));
	}
	
	public function testGet() {
		$t = new Table($this->db, 'foobar');
		
		$this->assertSame([
			'id' => '3',
			'title' => 'Baz',
			'content' => 'Baz content',
			'ct' => '1438168961',
			'ut' => '1438168962',
		], $t->get(3));
		$this->assertNull($t->get(100500));
	}
	
	public function testSet() {
		$t = new Table($this->db, 'foobar');
		$this->assertSame([
			'id' => '3',
			'title' => 'Baz',
			'content' => 'Baz content',
			'ct' => '1438168961',
			'ut' => '1438168962',
		], $t->get(3));
		$t->set(3, ['title' => 'new title']);
		$this->assertSame([
			'id' => '3',
			'title' => 'new title',
			'content' => 'Baz content',
			'ct' => '1438168961',
			'ut' => '1438168962',
		], $t->get(3));
	}
	
	public function testRm() {
		$t = new Table($this->db, 'foobar');
		$this->assertSame([
			['id' => '1'],
			['id' => '2'],
			['id' => '3'],
			['id' => '4'],
		], $t->select([], ['id']));
		$t->rm(3);
		$this->assertSame([
			['id' => '1'],
			['id' => '2'],
			['id' => '4'],
		], $t->select([], ['id']));
	}
	
	public function testInsert() {
		$t = new Table($this->db, 'foobar');
		$this->assertSame([
			['id' => '1'],
			['id' => '2'],
			['id' => '3'],
			['id' => '4'],
		], $t->select([], ['id'], ['id' => 1]));
		$id = $t->insert([
			'title' => 'Quux',
			'content' => 'Quux content',
			'ct' => '1438168960',
			'ut' => '1438168960',
		]);
		$this->assertSame([
			['id' => '1'],
			['id' => '2'],
			['id' => '3'],
			['id' => '4'],
			['id' => (string) $id],
		], $t->select([], ['id'], ['id' => 1]));
		$this->assertSame([
			'id' => (string) $id,
			'title' => 'Quux',
			'content' => 'Quux content',
			'ct' => '1438168960',
			'ut' => '1438168960',
		], $t->get($id));
	}
}
