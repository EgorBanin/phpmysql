Простая библиотека для работы с MySQL

#### Подключение и база данных по умолчанию

~~~php
<?php

use \Mysql\Mysql;

$db = new Mysql('username', 'password', 'localhost');
$db->defaultDb('Sakila');

~~~

#### Запрос и получение данных

~~~php
$result = $db->query('
	select * from `Book`
	where `author` = :author
', [':author' => 'Мартин Фаулер']);

while ($row = $result->row()) {
	var_dump($row);
}
~~~

#### Массивы в параметрах

~~~php
$result = $db->query('
	select * from `Book`
	where `id` in (:ids)
', [':ids' => [1, 4, 10]]);
~~~

~~~php
$result = $db->query('
	insert into `Book` (`ISBN`, `title`, `author`)
	values :values
', [':values' => [
	['978-5-7502-0064-1', 'Совершенный код', 'Стив Макконнелл'],
	['978-5-93286-153-0', 'MySQL. Оптимизация производительности', 'Бэрон Шварц, Петр Зайцев, Вадим Ткаченко, Джереми Д. Зооднай, Дерек Дж. Баллинг, Арьен Ленц']
]]);
~~~
