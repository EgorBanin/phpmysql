<?php

namespace tests\Mysql;

class ResutTest extends \PHPUnit\Framework\TestCase
{

	public function testRow()
	{
		$result = new \Mysql\Result('', [
			['id' => 1, 'text' => 'abc'],
			['id' => 2, 'text' => 'def'],
			['id' => 3, 'text' => 'ghi']
		], 1, 0);

		$this->assertEquals(['id' => 1, 'text' => 'abc'], $result->row());
		$this->assertEquals(['id' => 2, 'text' => 'def'], $result->row());
		$this->assertEquals(['id' => 3, 'text' => 'ghi'], $result->row());
		$this->assertEmpty($result->row());
	}

}
