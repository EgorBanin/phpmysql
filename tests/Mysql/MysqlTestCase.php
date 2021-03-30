<?php

namespace tests\Mysql;

abstract class MysqlTestCase extends \PHPUnit\Framework\TestCase
{

	protected function db(array $options = []): \Mysql\Client
	{
		return \Mysql\Client::init($this->getConnectionOptions($options));
	}

	protected function getConnectionOptions(array $options = []): array
	{
		return array_merge([
			'user' => 'sakila',
			'password' => 'passw0rd',
			'host' => 'mysql',
			'port' => 3306,
			'defaultDb' => 'sakiladb',
			'charset' => 'utf8',
		], $options);
	}

	protected function getConnectionConstructorAgs(array $options = []): array
	{
		$connectionOptions = $this->getConnectionOptions($options);

		return [
			$connectionOptions['user'],
			$connectionOptions['password'],
			$connectionOptions['host'],
			$connectionOptions['port'],
			$connectionOptions['tags'] ?? [],
		];
	}

}