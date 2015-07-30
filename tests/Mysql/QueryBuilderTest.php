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
	
}