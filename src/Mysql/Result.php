<?php

namespace Mysql;

/**
 * Результат выполнения запроса
 */
class Result implements IResult
{

	private $sql;

	private $rows;

	private $affectedRows;

	private $insertedId;

	private $rowOffset = 0;

	public function __construct(
		string $sql,
		array $rows,
		int $affectedRows,
		$insertedId
	)
	{
		$this->sql = $sql;
		$this->rows = $rows;
		$this->affectedRows = $affectedRows;
		$this->insertedId = $insertedId;
	}

	public function sql(): string
	{
		return $this->sql;
	}

	public function rows(): array
	{
		return $this->rows;
	}

	public function affectedRows(): int
	{
		return $this->affectedRows;
	}

	public function insertedId()
	{
		return $this->insertedId;
	}

	public function row(): array
	{
		return $this->rows[$this->rowOffset++] ?? [];
	}

}
