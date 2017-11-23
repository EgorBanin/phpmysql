<?php

namespace Mysql;

/**
 * Вспомогательный класс для сокращения объёма
 * похожего кода тривиальных запросов
 */
class Table {
	
	protected $db;
	
	protected $name;
	
	protected $pk;
	
	public function __construct(Client $db, $name, $pk = 'id') {
		$this->db = $db;
		$this->name = $name;
		$this->pk = $pk;
	}
	
	/**
	 * Получить строку из таблицы по первичному ключу
	 * @param int|string $id
	 * @return array|null
	 */
	public function get($id) {
		return $this->selectOne(array($this->pk => $id));
	}
	
	/**
	 * Обновить строку из таблицы по первичному ключу
	 * @param int|string $id
	 * @param array $fields
	 * @return void
	 */
	public function set($id, array $fields) {
		$this->update(array($this->pk => $id), $fields);
	}
	
	/**
	 * Удалить строку из таблицы по первичному ключу
	 * @param int|string $id
	 * @return void
	 */
	public function rm($id) {
		$this->delete(array($this->pk => $id));
	}
	
	/**
	 * Выбрать строки из таблицы
	 * @param array $where
	 * @param array $fields
	 * @param mixed $order
	 * @param mixed $limit
	 * @return array
	 */
	public function select(array $where = array(), $fields = array('*'), $order = null, $limit = null) {
		$builder = new QueryBuilder();
		$sql = $builder->select($this->name, $fields, $where, $order, $limit);
		$result = $this->db->query($sql, $builder->getParams());
		
		return $result->rows();
	}
	
	/**
	 * Выбрать одну строку из таблицы
	 * @param array $where
	 * @param array $fields
	 * @return array|null
	 */
	public function selectOne(array $where = array(), array $fields = array('*')) {
		$rows = $this->select($where, $fields, null, 1);
		
		return reset($rows)?: null;
	}
	
	/**
	 * Добавить одну или несколько строк в таблицу
	 * @param array $fields
	 * @return string
	 */
	public function insert(array $fields) {
		$builder = new QueryBuilder();
		$sql = $builder->insert($this->name, $fields);
		$result = $this->db->query($sql, $builder->getParams());
		
		return $result->insertedId();
	}
	
	/**
	 * Обновить строки в таблицу
	 * @param array $where
	 * @param array $fields
	 * @return void
	 */
	public function update(array $where, array $fields) {
		$builder = new QueryBuilder();
		$sql = $builder->update($this->name, $fields, $where);
		$result = $this->db->query($sql, $builder->getParams());

		return $result->affectedRows();
	}
	
	/**
	 * Удалить строки из таблицы
	 * @param array $where
	 */
	public function delete(array $where) {
		$builder = new QueryBuilder();
		$sql = $builder->delete($this->name, $where);
		$this->db->query($sql, $builder->getParams());
	}
	
}
