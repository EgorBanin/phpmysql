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
	
	public function select(array $query = [], array $fields = ['*'], array $sort = [], $limit = 0) {
		$params = [];
		$sql = QueryBuilder::select($this->name, $query, $fields, $sort, $limit, $params);
		$result = $this->db->query($sql, $params);
		
		return $result->rows();
	}
	
	public function selectOne(array $query = [], array $fields = ['*'], array $sort = [], $limit = 0) {
		$rows = $this->select($query, $fields, $sort, $limit);
		
		return reset($rows);
	}
	
	public function insert(array $fields) {
		$params = [];
		$sql = QueryBuilder::insert($this->name, $fields, $params);
		$result = $this->db->query($sql, $params);
		
		return $result->insertId();
	}
	
	public function update(array $fields, array $query) {
		$params = [];
		$sql = QueryBuilder::update($this->name, $fields, $query, $params);
		$this->db->query($sql, $params);
	}
	
	public function delete(array $query) {
		$params = [];
		$sql = QueryBuilder::delete($this->name, $query, $params);
		$this->db->query($sql, $params);
	}
	
}
