### Простая библиотека для работы с MySQL

Простейшая обёртка mysqli, которая экранирует и подставляет данные
в запрос.

#### Подключение, база данных по умолчанию и кодировка

~~~php
<?php

$db = Mysql\Client::init('username', 'password')
    ->defaultDb('Sakila')
    ->charset('utf8');
~~~

Подключение к базе данных создаётся не сразу, а при первом запросе.

#### Запрос и получение данных

~~~php
$result = $db->query('
	select * from `Book`
	where `author` = :author
', [':author' => 'Мартин Фаулер']);

while ($row = $result->row()) {
	echo $row['title'], "\n";
}
~~~

~~~php
$result = $db->query('
	insert into `Book`
	set
		`ISBN` = :ISBN,
		`title` = :title,
		`author` = :author;
', [
	':ISBN' => '978-5-7502-0064-1',
	':title' => 'Совершенный код',
	':author' => 'Стив Макконнелл'
]);

$id = $result->insertId();
$affectedRows = $result->affectedRows();
~~~

#### Массивы в параметрах

~~~php
$result = $db->query('
	select * from `Book`
	where `id` in (:ids)
', [':ids' => [1, 4, 10]]);
~~~

~~~php
$values = [
	['978-5-7502-0064-1', 'Совершенный код', 'Стив Макконнелл'],
	['978-5-93286-153-0', 'MySQL. Оптимизация производительности', 'Бэрон Шварц, Петр Зайцев, Вадим Ткаченко, Джереми Д. Зооднай, Дерек Дж. Баллинг, Арьен Ленц']
];
$result = $db->query('
	insert into `Book` (`ISBN`, `title`, `author`)
	values :values
', [':values' => $values]);
~~~

#### Исключения

В случае возникновения ошибки при выполнении запроса, метод query
бросает исключение Mysql\Exception.
