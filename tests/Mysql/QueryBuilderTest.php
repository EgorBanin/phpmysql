<?php

namespace Mysql;

class QueryBuilderTest extends \PHPUnit_Framework_TestCase {
	
	public function testParseVal() {
		$b = new QueryBuilder();
		$result = $b->parseVal('foo');
		$this->assertSame(['=', 'foo'], $result);
	}
	
	public function testWhere() {
		$this->assertSame('`foo` = :0', QueryBuilder::where([
			'foo' => 'foo value',
		]));
		$this->assertSame('`foo` = :1 and `bar` = :2', QueryBuilder::where([
			'foo' => 'foo value',
			'bar' => 123,
		]));
		$this->assertSame('(`foo` = :3 or `bar` = :4)', QueryBuilder::where([
			'$or' => [
				'foo' => 'foo value',
				'bar' => 123,
			],
		]));
		$this->assertSame('(`foo` = :5 or `bar` = :6) and `baz` = :7', QueryBuilder::where([
			'$or' => [
				'foo' => 'foo value',
				'bar' => 123,
			],
			'baz' => time()
		]));
	}
	
	public function testSelect() {
		$params = [];
		$this->assertSame('select * from `FooTable` where `foo` = :0', QueryBuilder::select(
			'FooTable',
			[
				'foo' => 'foo value',
			],
			['*'],
			[],
			0,
			$params
		));
		$this->assertSame([':0' => 'foo value'], $params);
	}
	
	public function testInsert() {
		$params = [];
		$this->assertSame('insert into `FooTable` set `foo` = :0', QueryBuilder::insert(
			'FooTable',
			[
				'foo' => 'foo value',
			],
			$params
		));
		$this->assertSame([':0' => 'foo value'], $params);
		
		$params = [];
		$this->assertSame('insert into `FooTable` (`foo`) values :0', QueryBuilder::insert(
			'FooTable',
			[
				['foo' => 'value 1'],
				['foo' => 'value 2'],
			],
			$params
		));
		$this->assertSame([':0' => [
			['foo' => 'value 1'],
			['foo' => 'value 2'],
		]], $params);
	}
	
	public function testUpdate() {
		$params = [];
		$this->assertSame('update `FooTable` set `foo` = :0 where `id` = :1', QueryBuilder::update(
			'FooTable',
			[
				'foo' => 'foo value',
			],
			[
				'id' => 123,
			],
			$params
		));
		$this->assertSame([':0' => 'foo value', ':1' => 123], $params);
	}
	
	public function testOrderBy() {
		$this->assertSame('order by `id`', QueryBuilder::orderBy('id'));
		$this->assertSame('order by `id`', QueryBuilder::orderBy(['id']));
		$this->assertSame('order by `ctime`, `id`', QueryBuilder::orderBy(['ctime', 'id']));
		$this->assertSame('order by `id` desc', QueryBuilder::orderBy(['id' => 'desc']));
		$this->assertSame('order by `ctime` desc, `id` asc', QueryBuilder::orderBy(['ctime' => 0 ,'id' => 1]));
	}

	public function testLimit() {
		$this->assertSame('limit 10', QueryBuilder::limit(10));
		$this->assertSame('limit 10 offset 5', QueryBuilder::limit([5, 10]));
		$this->assertSame('limit 10', QueryBuilder::limit(['limit' => 10]));
		$this->assertSame('limit 10 offset 5', QueryBuilder::limit(['limit' => 10, 'offset' => 5]));
	}
	
}