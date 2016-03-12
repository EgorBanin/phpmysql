## Простая библиотека для работы с MySQL

Простейшая обёртка mysqli, которая умеет экранировать и подставлять данные
в запрос.

### Подключение, база данных по умолчанию и кодировка

~~~php
<?php

$db = Mysql\Client::init('username', 'password')
    ->defaultDb('Sakila')
    ->charset('utf8');
~~~

Подключение к базе данных создаётся не сразу, а при первом запросе.

### Запрос и получение данных

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

$id = $result->insertedId();
$affectedRows = $result->affectedRows();
~~~

### Массивы в параметрах

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

### Транзакции

~~~php
$sum = 100;
$db->transaction([
    ['
        update `accounts`
        set `amount` = `amount` - :sum
        where `id` = :from
    ', [':sum' => $sum, ':from' => 1]],
    ['
        update `accounts`
        set `amount` = `amount` + :sum
        where `id` = :to
    ', [':sum' => $sum, ':to' => 2]]
]);
~~~

### Обработка ошибок

В случае возникновения ошибки при выполнении запроса, методы query и transaction
бросают исключение Mysql\Exception.

### Вспомогательный класс Table

Для сокращения объёма похожего кода запросов можно воспользоваться вспомогательным классом Table.

~~~php
$table = $db->table('books');
$book = $table->get(1);
$books = $table->select(['author' => 'Мартин Фаулер']);
$favoriteBook = $table->selectOne(['title' => 'Совершенный код']);
$table->set($favoriteBook['id'], ['ISBN' => '978-5-469-00822-4']);
$table->update(['ISBN' => '978-5-469-00822-4'], ['title' => 'Совершенный код']);
$id = $table->insert([
    'ISBN' => '978-5-459-01720-5',
    'title' => 'Приемы объектно-ориентированного проектирования',
    'author' => 'Эрих Гамма, Ричард Хелм, Ральф Джонсон, Джон Влиссидес'
]);
$table->rm($id);
$table->delete(['ISBN' => '978-5-459-01720-5']);
~~~

#### Методы

##### get($id)
Выбирает строку по первичному ключу

##### set($id, array $fields)
Обновляет указанные ячейки строки по первичному ключу.

##### rm($id)
Удаляет строку по первичному ключу.

##### insert(array $row)
Вставляет строку. Возвращает id добавленой записи.

##### select([array $query = [][, array $fields = ['*'][, mixed $sort = null[, mixed $limit = null]]]])
Выбирает строки соответствующие запросу.

##### selectOne([array $query = [][, array $fields = ['*']]])
Выбирает только одну строку соответствующую запросу.

##### update(array $fields, array $query)
Обновляет строки соответствующие запросу.

##### delete(array $query)
Удаляет строки соответствующие запросу.

#### Операторы поддерживаемые в запросах select, update и delete

##### $and
`['gender' => 'f', 'age' => ['$between' => [18, 25]]]`
или `['$and' => ['gender' => 'f', 'age' => ['$between' => [18, 25]]]]`
=> ``…where `gender` = 'f' and `age` between 18 and 25``

##### $or
`['$or' => ['sheIs' => 'pretty', 'iAm' => 'drunk']]`
=> ``… where `sheIs` = 'pretty' or `iAm` = 'drunk'``

##### $eq
`['id' => 100]`
или `['id' => ['$eq' => 100]]`
=> ``…where `id` = 100``

##### $ne
`['id' => ['$ne' => 100]]`
=> ``…where `id` != 100``

##### $lt
`['id' => ['$lt' => 100]]`
=> ``…where `id` < 100``

##### $lte
`['id' => ['$lte' => 100]]`
=> ``…where `id` <= 100``

##### $gt
`['id' => ['$gt' => 100]]`
=> ``…where `id` > 100``

##### $gte
`['id' => ['$gte' => 100]]`
=> ``…where `id` >= 100``

##### $between
`['id' => ['$between' => [100, 200]]`
=> ``…where `id` between 100 and 200``

##### $in
`['id' => ['$in' => [1, 2, 3]]]`
=> ``…where `id` in (1, 2, 3)``

##### $nin
`['id' => ['$nin' => [1, 2, 3]]]`
=> ``…where `id` not in (1, 2, 3)``

#### Нетривиальные запросы

Несмотря на богатство возможностей Table,
в случае нетривиальных запросов следует предпочесть Mysql\Client::query.
Сравните:

~~~php
$table = new Mysql\Table($db, 'posts');
$archive = $table->select([
    'active' => true,
    '$or' => [
        'archive' => true,
        'ctime' => ['$lt' => time() - (60 * 60 * 24 * 365)],
    ],
    'id' => ['$nin' => [1, 4, 10]]
], ['id', 'title', 'ctime'], ['ctime' => -1], ['limit' => 10, 'offset' => 20]);

$archive = $db->query('
    select
        `id`,
        `title`,
        from_unixtime(`ctime`) as `cdate`
    from `posts`
    where
        `active` = true
        and (
            `archive` = true
            or `ctime` < :timeLimit
        )
        and `id` not in :exclusions
    order by `ctime` desc
    limit :limit offset :offset
', [
    ':timeLimit' => time() - (60 * 60 * 24 * 365),
    ':exclusions' => [1, 4, 10],
    ':limit' => 10,
    ':offset' => 20,
]);
~~~

Хотя первый варинат более компактный, он не такой очевидный как SQL-запрос.

### Советы по написанию SQL-запросов

- Не стесняйтесь переносить строку запроса и использовать отступы для улучшения читаемости;
- Не пишите запросы капсом, в этом нет смысла (хотя вот github подсветил первый запрос :-);
- Заключайте в апострофы имена таблиц, столбцов и алиасов;
- Используйте осмысленные имена алиасов;
- По возможности используйте одинаковый стиль написания имён переменных php и столбцов в базе.

~~~php
$hotPosts = $db->query('SELECT t1.*, t2.name as autor_name FROM posts as t1 INNER JOIN authors as t2 ON t1.author_id = t2.id WHERE t1.active = 1 AND t1.views > 10000 ORDER BY t1.views DESC LIMIT 10')->rows();

$hotPosts = $db->query('
    select
        `posts`.*,
        `authors`.`name` as `authorName`
    from `posts`
    inner join `authors` on `authors`.`id` = `posts`.`authorId`
    where
        `posts`.`active` = true
        and `posts`.`views` > 10000
    order by `posts`.`views` desc
    limit 10
')->rows();
~~~

### Похожие библиотеки
Стандартные mysqli и PDO интерфейсы несколько неуклюжи, поэтому обёртки подобные этой пишет
каждый второй разработчик. Возможно вас заинтересует что нибудь из следующего:

- [MysqliDb](https://github.com/joshcam/PHP-MySQLi-Database-Class) -- обёртка mysqli, GPLv3
- [MeekroDB](https://github.com/SergeyTsalkov/meekrodb) -- обёртка mysqli, LGPLv3
- [DByte](https://github.com/Xeoncross/DByte) -- компактная реализация обёртки PDO, MIT
- [DbSimple](https://github.com/DmitryKoterov/dbsimple) -- довольно известная в рунете библиотека, умеет работать с разными расширениями для работы с бд, LGPL
