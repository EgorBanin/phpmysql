# Простая библиотека для работы с MySQL

## Подключение к MySQL и база данных по умолчанию

~~~
<?php

use \Mysql\Mysql;

$db = new Mysql('username', 'password', 'localhost');
$db->defaultDb('Sakila');

~~~

## Запрос и получение данных

~~~
$result = $db->query('
	select * from `Book`
	where `author` = :author
', [':author' => 'Мартин Фаулер']);

while ($row = $result->row()) {
	var_dump($row);
}
~~~
