<?php

namespace Mysql;

/**
 * Универсальный результат выполнения запроса
 */
class Result {
	
	private $sql;
	
	private $rows;
	
	private $affectedRows;
	
	private $insertedId;
	
	public function __construct($sql, array $rows, $affectedRows, $insertedId) {
		$this->sql = $sql;
		$this->rows = $rows;
		$this->affectedRows = $affectedRows;
		$this->insertedId = $insertedId;
	}
	
	/**
	 * SQL-запрос
	 * @return string
	 */
	public function sql() {
		return $this->sql;
	}
	
	/**
	 * Выбранные строки
	 * @return array
	 */
	public function rows() {
		return $this->rows;
	}
	
	/**
	 * Затронутые строки
	 * Прежде всего имеет смысл для операций update и delete.
	 * @return int
	 */
	public function affectedRows() {
		return $this->affectedRows;
	}
	
	/**
	 * Автоматически генерируемый id
	 * Имеет смысл для insert. Для множественной вставки вернёт id
	 * первой вставленной строки.
	 * @return int|string
	 */
	public function insertedId() {
		return $this->insertedId;
	}
	
	/**
	 * Получение слудующей строки
	 * @return array
	 */
	public function row() {
		$row = each($this->rows);
		
		return $row? $row['value'] : false;
	}
	
}
