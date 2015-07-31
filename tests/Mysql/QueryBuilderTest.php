<?php

namespace Mysql;

class QueryBuilderTest extends \PHPUnit_Framework_TestCase {
	
	public function testParseVal() {
		$b = new QueryBuilder();
		$result = $b->parseVal('foo');
		$this->assertSame(['=', 'foo'], $result);
	}
	
	public function testWhere() {
		$this->assertSame('`foo` = :foo', QueryBuilder::where([
			'foo' => 'foo value',
		]));
		$this->assertSame('`foo` = :foo and `bar` = :bar', QueryBuilder::where([
			'foo' => 'foo value',
			'bar' => 123,
		]));
		$this->assertSame('(`foo` = :foo or `bar` = :bar)', QueryBuilder::where([
			'$or' => [
				'foo' => 'foo value',
				'bar' => 123,
			],
		]));
		$this->assertSame('(`foo` = :foo or `bar` = :bar) and `baz` = :baz', QueryBuilder::where([
			'$or' => [
				'foo' => 'foo value',
				'bar' => 123,
			],
			'baz' => time()
		]));
	}
	
	public function testSelect() {
		$params = [];
		$this->assertSame('select * from `FooTable` where `foo` = :foo', QueryBuilder::select(
			'FooTable',
			[
				'foo' => 'foo value',
			],
			['*'],
			[],
			0,
			$params
		));
		$this->assertSame([':foo' => 'foo value'], $params);
	}
	
	public function testInsert() {
		$params = [];
		$this->assertSame('insert into `FooTable` set `foo` = :foo', QueryBuilder::insert(
			'FooTable',
			[
				'foo' => 'foo value',
			],
			$params
		));
		$this->assertSame([':foo' => 'foo value'], $params);
		
		$params = [];
		$this->assertSame('insert into `FooTable` (`foo`) values :values', QueryBuilder::insert(
			'FooTable',
			[
				['foo' => 'value 1'],
				['foo' => 'value 2'],
			],
			$params
		));
		$this->assertSame([':values' => [
			['foo' => 'value 1'],
			['foo' => 'value 2'],
		]], $params);
	}
	
	public function testUpdate() {
		$params = [];
		$this->assertSame('update `FooTable` set `foo` = :foo where `id` = :id', QueryBuilder::update(
			'FooTable',
			[
				'foo' => 'foo value',
			],
			[
				'id' => 123,
			],
			$params
		));
		$this->assertSame([':foo' => 'foo value', ':id' => 123], $params);
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