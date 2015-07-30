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
			$sql .= ' order by '.self::orderBy($sort);
		}
		
		if ($limit) {
			$sql .= ' limit '.$limit;
		}
		
		return $sql;
	}
	
	public static function update($table, array $fields, array $query, array &$params = []) {
		$sql = 'update '.self::quote($table);
		
		$set = [];
		foreach ($fields as $name => $value) {
			$set[] = QueryBuilder::quote($name).' = :'.$name;
			$params[':'.$name] = $value;
		}
		
		$sql .= ' set '.implode(', ', $set);
			
		if ($query) {
			$sql .= ' where '.self::where($query, 'and', $params);
		}
		
		return $sql;
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
	
	public static function orderBy(array $sort) {
		$order = [];
		foreach ($sort as $field => $direction) {
			$direction = (
				$direction > 0
				|| strtolower($direction) === 'asc'
			)? 'asc' : 'desc';
			$order[] = self::quote($field).' '.$direction;
		}
		
		return implode(', ', $order);
	}
	
	public static function limit($slice) {
		
	}
	
}