## Как работает Mysqli

Некоторые наблюдения о работе функций mysqli.

### query(..., MYSQLI_ASYNC)

|                                 | query(..., MYSQLI_ASYNC) | reap_async_query() |
| ------------------------------- | ------------------------ | ------------------ |
| Если закрыть соединение (close) | false + варнинг          | -                  |
| Если сервер упал                | true                     | false              | 
| Если ошибка в запросе           | true                     | false              |


### pool

Согласно [документации](https://www.php.net/manual/ru/mysqli.poll.php) массивы `$read`, `$error` и `$reject` будут заполнены объектами Mysqli.

```
public static mysqli::poll ( array &$read , array &$error , array &$reject , int $sec , int $usec = 0 ) : int
```

Опрос прерывается, как только получен ответ от хотя бы одного подключения. Если подключение не находится в состоянии асинхронного запроса, то в `$reject` оно попадёт только по истечении времени опроса. Если запрос содержит ошибку, то он попадает в `$read` и `reap_async_query()` для него вернёт false.