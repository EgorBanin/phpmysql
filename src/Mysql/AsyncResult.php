<?php declare(strict_types=1);

namespace Mysql;

class AsyncResult implements IResult
{

	/**
	 * @var string
	 */
	private $sql;

	/**
	 * @var callable
	 */
	private $wait;

	/**
	 * @var null|Result
	 */
	private $result;

	/**
	 * @param string $sql
	 * @param callable $wait функция ожидания завершения запроса
	 */
	public function __construct(string $sql, callable $wait)
	{
		$this->sql = $sql;
		$this->wait = $wait;
	}

	public function sql(): string
	{
		return $this->sql;
	}

	public function rows(): array
	{
		return $this->waitResult()->rows();
	}

	public function affectedRows(): int
	{
		return $this->waitResult()->affectedRows();
	}

	public function insertedId()
	{
		return $this->waitResult()->insertedId();
	}

	public function setResult(Result $result)
	{
		$this->result = $result;
	}

	private function waitResult(): Result
	{
		if (!$this->result) {
			($this->wait)();
		}

		return $this->result;
	}

}