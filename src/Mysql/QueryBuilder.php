<?php

namespace Mysql;

class QueryBuilder {
	
	private $placeholderId = 0;
	
	private $params = array();
	
	private $whereOps = array(
		'$and' => 'and',
		'$or' => 'or',
	);
	
	private $ops = array(
		'$eq' => '=',
		'$ne' => '!=',
		'$lt' => '<',
		'$lte' => '<=',
		'$gt' => '>',
		'$gte' => '>=',
		'$between' => 'between',
		'$in' => 'in',
		'$nin' => 'not in',
		'$like' => 'like'
	);
	
	public function select($table, $fields = array('*'), array $where = array(), $order = null, $limit = null) {
		$this->placeholderId = 0;
		
		$fields = (array) $fields;
		$quotedFields = array();
		foreach ($fields as $alias => $field) {
			if ($field !== '*') {
				$field = self::quote($field);
			}
			
			$fieldSql = $field;
			
			if (is_string($alias)) {
				$fieldSql .= 'as '.self::quote($alias);
			}
			
			$quotedFields[] = $fieldSql;
		}
		
		$sql = 'select '.implode(', ', $quotedFields);
		$sql .= ' from '.self::quote($table);
		
		
		if ($where) {
			$sql .= ' where '.$this->buildWhere($where);
		}
		
		if ($order) {
			$sql .= self::orderBy($order);
		}
		
		if ($limit) {
			$sql .= self::limit($limit);
		}
		
		return $sql;
	}
	
	public function insert($table, $vals) {
		$this->placeholderId = 0;
		$sql = 'insert into '.self::quote($table);
		
		if (is_string(key($vals))) {
			$sql .= ' set '.$this->set($vals);
		} else {
			$keys = array_keys(reset($vals));
			
			$names = array();
			foreach ($keys as $name) {
				$names[] = self::quote($name);
			}
			
			$sql .= ' ('.  implode(', ', $names). ')';
			$sql .= ' values '.$this->addParam($vals);
		}
		
		return $sql;
	}
	
	public function update($table, $vals, $where) {
		$this->placeholderId = 0;
		$sql = 'update '.self::quote($table);
		$sql .= ' set '.$this->set($vals);
			
		if ($where) {
			$sql .= ' where '.$this->buildWhere($where);
		}
		
		return $sql;
	}
	
	public static function quote($str) {
		return '`'.strtr($str, array('`' => '``')).'`';
	}
	
	private function addParam($val) {
		$this->params[':'.$this->placeholderId] = $val;
		
		return ':'.$this->placeholderId++;
	}
	
	public function getParams() {
		return $this->params;
	}

	/**
	 * Рекурсивное построение where
	 * @param array $where
	 * @param string $op
	 * @return array where-выражение и параметры
	 */
	public function buildWhere(array $where, $op = 'and') {
		$conditions = array();
		foreach ($where as $k => $v) {
			if (array_key_exists($k, $this->whereOps) && is_array($v)) {
				// рекурсия
				$conditions[] = '('.$this->buildWhere($v, $this->whereOps[$k]).')';
			} else {
				if (is_int($k)) {
					$field = key($v);
					$val = current($v);
				} else {
					$field = $k;
					$val = $v;
				}
				
				$conditions[] = $this->buildComparison($field, $val);
			}
		}

		return implode(" $op ", $conditions);
	}
	
	public function buildComparison($field, $val) {
		if (is_array($val)) {
			if (is_string(key($val))) {
				$op = $this->ops[key($val)];
				$val = current($val);
			} else {
				$op = 'in';
			}
		} elseif (is_null($val)) {
			$op = 'is';
		} else {
			$op = '=';
		}
		
		$sql = self::quote($field).' '.$op.' ';
		switch($op) {
			case 'between':
				$sql .= $this->addParam(array_shift($val))
					.' and '.$this->addParam(array_shift($val));
				break;
			
			case 'in':
			case 'nin':
				$sql .= '('.$this->addParam($val).')';
				break;
			
			default:
				$sql .= $this->addParam($val);
		}
		
		return $sql;
	}
	
	public function set($vals) {
		$set = array();
		foreach ($vals as $filed => $val) {
			$set[] = self::quote($filed).' = '.$this->addParam($val);
		}
		
		return implode(', ', $set);
	}
	
	public function delete($table, array $where) {
		$this->placeholderId = 0;
		$sql = 'delete from '.self::quote($table);
		
		if ($where) {
			$sql .= ' where '.$this->buildWhere($where);
		}
		
		return $sql;
	}
	
	public static function orderBy($sort) {
		$order = array();
		
		if (is_array($sort)) {
			$stringKeys = array_filter(array_keys($sort), 'is_string');
			$isAssoc =  ! empty($stringKeys);
			
			foreach ($sort as $k => $v) {
				if ($isAssoc) {
					$direction = ($v > 0 || strtolower($v) === 'asc')? 'asc' : 'desc';
					$order[] = self::quote($k).' '.$direction;
				} else {
					$order[] = self::quote($v);
				}
			}
		} else {
			$order[] = self::quote($sort);
		}
		
		return empty($order)? '' : ' order by '.implode(', ', $order);
	}
	
	public static function limit($slice) {
		if (is_array($slice)) {
			$stringKeys = array_filter(array_keys($slice), 'is_string');
			$isAssoc =  ! empty($stringKeys);
			
			if ($isAssoc) {
				$limit =  (int) $slice['limit'];
				$offset = isset($slice['offset'])? (int) $slice['offset'] : null;
			} elseif (count($slice) === 1) {
				$limit = (int) array_shift($slice);
				$offset = null;
			} else {
				$offset = (int) array_shift($slice);
				$limit = (int) array_shift($slice);
			}
		} else {
			$limit = (int) $slice;
			$offset = null;
		}
		
		$sql = ' limit '.$limit;
		$sql .= $offset? ' offset '.$offset : '';
		
		return $sql;
	}
	
}