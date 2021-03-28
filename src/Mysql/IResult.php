<?php declare(strict_types=1);

namespace Mysql;

/**
 * Результат выполнения запроса
 */
interface IResult {

	/**
	 * Получить SQL, для которого получен этот результат
	 * @return string
	 */
	public function sql(): string;

	/**
	 * Получить все выбранные строки
	 * @return array
	 */
	public function rows(): array;

	/**
	 * Получить число затронутых строк
	 * Имеет смысл для запросов update и delete.
	 * @return int
	 */
	public function affectedRows():int;

	/**
	 * Поучить автоматически сгенерированный id
	 * Имеет смысл для insert. Для множественной вставки вернёт id
	 * первой вставленной строки.
	 * @return int|string
	 */
	public function insertedId();

}
