<?php

namespace Mysql;

class Table {
	
	protected $db;
	
	protected $name;
	
	protected $pk;
	
	public function __construct(Client $db, $name, $pk = 'id') {
		$this->db = $db;
		$this->name = $name;
		$this->pk = $pk;
	}
	
	public function get($id) {
		return $this->selectOne([$this->pk => $id]);
	}
	
	public function set($id, array $fields) {
		$this->update($fields, [$this->pk => $id]);
	}
	
	public function rm($id) {
		$this->delete([$this->pk => $id]);
	}
	
	public function select(array $where = [], $fields = ['*'], $order = null, $limit = null) {
		$builder = new QueryBuilder();
		$sql = $builder->select($this->name, $fields, $where, $order, $limit);
		$result = $this->db->query($sql, $builder->getParams());
		
		return $result->rows();
	}
	
	public function selectOne(array $where = [], array $fields = ['*'], array $order = []) {
		$rows = $this->select($where, $fields, $order, 1);
		
		return reset($rows);
	}
	
	public function insert(array $fields) {
		$builder = new QueryBuilder();
		$sql = $builder->insert($this->name, $fields);
		$result = $this->db->query($sql, $builder->getParams());
		
		return $result->insertId();
	}
	
	public function update(array $fields, array $where) {
		$builder = new QueryBuilder();
		$sql = $builder->update($this->name, $fields, $where);
		$this->db->query($sql, $builder->getParams());
	}
	
	public function delete(array $where) {
		$builder = new QueryBuilder();
		$sql = $builder->delete($this->name, $where);
		$this->db->query($sql, $builder->getParams());
	}
	
}
