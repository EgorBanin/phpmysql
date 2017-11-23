<?php

namespace Mysql;

class QueryBuilderTest extends \PHPUnit\Framework\TestCase {
	
	public function testWhere() {
		$qb = new QueryBuilder();
		$this->assertSame('`foo` = :0', $qb->buildWhere([
			'foo' => 'foo value',
		]));
		$this->assertSame('`foo` = :1 and `bar` = :2', $qb->buildWhere([
			'foo' => 'foo value',
			'bar' => 123,
		]));
		$this->assertSame('(`foo` = :3 or `bar` = :4)', $qb->buildWhere([
			'$or' => [
				'foo' => 'foo value',
				'bar' => 123,
			],
		]));
		$this->assertSame('(`foo` = :5 or `bar` = :6) and `baz` = :7', $qb->buildWhere([
			'$or' => [
				'foo' => 'foo value',
				'bar' => 123,
			],
			'baz' => time()
		]));
	}
	
	public function testSelect() {
		$qb = new QueryBuilder();
		$params = [];
		$this->assertSame('select * from `FooTable` where `foo` = :0', $qb->select(
			'FooTable',
			['*'],
			[
				'foo' => 'foo value',
			]
		));
		$this->assertSame([':0' => 'foo value'], $qb->getParams());
	}
	
	public function testInsert() {
		$qb = new QueryBuilder();
		$params = [];
		$this->assertSame('insert into `FooTable` set `foo` = :0', $qb->insert(
			'FooTable',
			[
				'foo' => 'foo value',
			]
		));
		$this->assertSame([':0' => 'foo value'], $qb->getParams());
		
		$params = [];
		$this->assertSame('insert into `FooTable` (`foo`) values :0', $qb->insert(
			'FooTable',
			[
				['foo' => 'value 1'],
				['foo' => 'value 2'],
			]
		));
		$this->assertSame([':0' => [
			['foo' => 'value 1'],
			['foo' => 'value 2'],
		]], $qb->getParams());
	}
	
	public function testUpdate() {
		$qb = new QueryBuilder();
		$params = [];
		$this->assertSame('update `FooTable` set `foo` = :0 where `id` = :1', $qb->update(
			'FooTable',
			[
				'foo' => 'foo value',
			],
			[
				'id' => 123,
			]
		));
		$this->assertSame([':0' => 'foo value', ':1' => 123], $qb->getParams());
	}
	
	public function testOrderBy() {
		$this->assertSame(' order by `id`', QueryBuilder::orderBy('id'));
		$this->assertSame(' order by `id`', QueryBuilder::orderBy(['id']));
		$this->assertSame(' order by `ctime`, `id`', QueryBuilder::orderBy(['ctime', 'id']));
		$this->assertSame(' order by `id` desc', QueryBuilder::orderBy(['id' => 'desc']));
		$this->assertSame(' order by `ctime` desc, `id` asc', QueryBuilder::orderBy(['ctime' => 0 ,'id' => 1]));
	}

	public function testLimit() {
		$this->assertSame(' limit 10', QueryBuilder::limit(10));
		$this->assertSame(' limit 10 offset 5', QueryBuilder::limit([5, 10]));
		$this->assertSame(' limit 10', QueryBuilder::limit(['limit' => 10]));
		$this->assertSame(' limit 10 offset 5', QueryBuilder::limit(['limit' => 10, 'offset' => 5]));
	}
	
}