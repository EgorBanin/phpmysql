<?php

namespace Mysql;

class Exception extends \Exception {
	
	const CODE_CONNECTION_ERROR = 1;
	const CODE_QUERY_ERROR = 2;
	const CODE_QUOTE_ERROR = 3;
	const CODE_CHARSET_ERROR = 4;
	const CODE_DEFAULTDB_ERROR = 5;
	
	public $sql;
	
	public function __construct($message = '', $code = 0, $previous = null, $sql = '') {
		$this->sql = $sql;
		parent::__construct($message, $code, $previous);
	}
	
}
