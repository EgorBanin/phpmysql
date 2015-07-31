<?php

namespace Mysql;

class QueryBuilder {
	
	public static function quote($str) {
		return '`'.strtr($str, ['`' => '``']).'`';
	}
	
	public static function select($table, array $query, array $fields, array $sort, $limit, array &$params = []) {
		$quotedFields = array_map(function ($val) {
			if ($val !== '*') {
				$val = QueryBuilder::quote($val);
			}
			
			return $val;
		}, $fields);
		$sql = 'select '.implode(', ', $quotedFields);
		$sql .= ' from '.self::quote($table);
		
		if ($query) {
			$sql .= ' where '.self::where($query, 'and', $params);
		}
		
		if ($sort) {
			$sql .= self::orderBy($sort);
		}
		
		if ($limit) {
			$sql .= ' limit '.$limit;
		}
		
		return $sql;
	}
	
	public static function insert($table, array $fields, array &$params = []) {
		$sql = 'insert into '.self::quote($table);
		$isAssoc =  ! empty(array_filter(array_keys($fields), 'is_string'));
		
		if ($isAssoc) {
			$sql .= self::set($fields, $params);
		} else {
			$keys = array_keys(reset($fields));
			
			$names = [];
			foreach ($keys as $name) {
				$names[] = self::quote($name);
			}
			
			$sql .= ' ('.  implode(', ', $names). ')';
			$sql .= ' values :values';
			$params[':values'] = $fields;
		}
		
		return $sql;
	}
	
	public static function update($table, array $fields, array $query, array &$params = []) {
		$sql = 'update '.self::quote($table);
		$sql .= self::set($fields, $params);
			
		if ($query) {
			$sql .= ' where '.self::where($query, 'and', $params);
		}
		
		return $sql;
	}
	
	public static function set(array $fields, array &$params) {
		$set = [];
		foreach ($fields as $name => $value) {
			$set[] = QueryBuilder::quote($name).' = :'.$name;
			$params[':'.$name] = $value;
		}
		
		return empty($set)? '' : ' set '.implode(', ', $set);
	}
	
	public static function delete($table, array $query, array &$params = []) {
		$sql = 'delete from '.self::quote($table);
		
		if ($query) {
			$sql .= ' where '.self::where($query, 'and', $params);
		}
		
		return $sql;
	}
	
	public static function where(array $criteria, $op = 'and', array &$params = []) {
		$opMap = [
			'$and' => 'and',
			'$or' => 'or',
		];
		$where = [];
		foreach ($criteria as $k => $v) {
			if (array_key_exists($k, $opMap) && is_array($v)) {
				$where[] = '('.self::where($v, $opMap[$k]).')';
			} else {
				list($comparisonOp, $val) = self::parseVal($v);
				$params[":$k"] = $val;
				$where[] = self::comparison($comparisonOp, $k);
			}
		}

		return implode(" $op ", $where);
	}
	
	public static function comparison($op, $field) {
		switch($op) {
			case 'between':
				$placeholder = ":$field and :$field";
				break;
			
			case 'in':
			case 'nin':
				$placeholder = "(:$field)";
				break;
			
			default:
				$placeholder = ":$field";
		}
		
		return sprintf('%s %s %s', self::quote($field), $op, $placeholder);
	}
	
	public static function parseVal($val) {
		$opMap = [
			'$eq' => '=',
			'$ne' => '!=',
			'$lt' => '<',
			'$lte' => '<=',
			'$gt' => '>',
			'$gte' => '>=',
			'$between' => 'between',
			'$in' => 'in',
			'$nin' => 'not in',
		];
		
		if (is_array($val)) {
			$first = reset($val);
			
			if (array_key_exists($first, $opMap)) {
				$op = $opMap[$first];
				array_shift($val);
			} else {
				$op = 'in';
			}
		} else {
			$op = '=';
		}
		
		return [$op, $val];
	}
	
	public static function orderBy($sort) {
		$order = [];
		
		if (is_array($sort)) {
			$isAssoc =  ! empty(array_filter(array_keys($sort), 'is_string'));
			
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
		
		return empty($order)? '' : 'order by '.implode(', ', $order);
	}
	
	public static function limit($slice) {
		if (is_array($slice)) {
			$isAssoc =  ! empty(array_filter(array_keys($slice), 'is_string'));
			
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
		
		$sql = 'limit '.$limit;
		$sql .= $offset? ' offset '.$offset : '';
		
		return $sql;
	}
	
}