<?php

namespace Mysql;

class Result {
	
	private $rows;
	
	private $affectedRows;
	
	private $insertId;
	
	public function __construct(array $rows, $affectedRows, $insertId) {
		$this->rows = $rows;
		$this->affectedRows = $affectedRows;
		$this->insertId = $insertId;
	}
	
	public function rows() {
		return $this->rows;
	}
	
	public function affectedRows() {
		return $this->affectedRows;
	}
	
	public function insertId() {
		return $this->insertId;
	}
	
	public function row() {
		$row = each($this->rows);
		
		return $row? $row['value'] : false;
	}
	
}
