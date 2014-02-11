<?php

use Mysql\Result;

class ResutTest extends PHPUnit_Framework_TestCase {
	
	public function testRow() {
		$result = new Result([
			['id' => 1, 'text' => 'abc'],
			['id' => 2, 'text' => 'def'],
			['id' => 3, 'text' => 'ghi']
		], 1, null);
		
		$this->assertEquals(['id' => 1, 'text' => 'abc'], $result->row());
		$this->assertEquals(['id' => 2, 'text' => 'def'], $result->row());
		$this->assertEquals(['id' => 3, 'text' => 'ghi'], $result->row());
		$this->assertFalse($result->row());
	}
	
}
