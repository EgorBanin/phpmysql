<?php

namespace Mysql;

/**
 * Запрос к базе данных
 */
class Query {
	
	private $sql;
	
	private $params;
	
	/**
	 * @param string $sql SQL-запрос
	 * @param array $params переменные, которые будут подставлены в запрос
	 */
	public function __construct($sql, array $params = []) {
		$this->sql = $sql;
		$this->params = $params;
	}
	
	/**
	 * Подготовить запрос
	 * Подставляет переменные в запрос.
	 * @param callable $escapeFunc функция экранирования переменных перед вставкой в запрос
	 */
	public function prepare($escapeFunc) {
		$replacePairs = [];
		
		foreach ($this->params as $name => $val) {
			$replacePairs[$name] = $this->quote($val, $escapeFunc);
		}
		
		return strtr($this->sql, $replacePairs);
	}
	
	/**
	 * Форматирование значения для вставки в запрос
	 * Значения экранируются; строки заключаются в одинарные кавычки;
	 * булевы значения преобразуются в строки 'true' и 'false', null — в 'null';
	 * значения одномерных массивов разделяются запятыми,
	 * двуменые дополнительно заключаются скобки (что удобно для множественного insert'а);
	 * объекты приводятся к строке.
	 * @param mixed $val
	 * @param callable $escapeFunc
	 * @return string
	 * @throws Exception
	 */
	public function quote($val, $escapeFunc) {
		if (is_string($val)) {
			$str = $escapeFunc($val);
			$quoted = "'$str'";
		} elseif (is_int($val)) {
			$quoted = $val;
		} elseif (is_float($val)) {
			$quoted = sprintf('%F', $val);
		} elseif (is_bool($val)) {
			$quoted = $val? 'true' : 'false';
		} elseif (is_null($val)) {
			$quoted = 'null';
		} elseif (is_array($val)) {
			$quotedArr =[];
			
			foreach ($val as $innerVal) {
				$str = $this->quote($innerVal, $escapeFunc);
				
				if (is_array($innerVal)) {
					$quotedArr[] = "($str)";
				} else {
					$quotedArr[] = $str;
				}
			}
			
			$quoted = join(', ', $quotedArr);
		} elseif (is_object($val)) {
			$quoted = $this->quote((string) $val, $escapeFunc);
		} else {
			throw new Exception('Неожиданный тип значения '.gettype($val));
		}
		
		return $quoted;
	}
	
}
