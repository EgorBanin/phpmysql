## Архитектура

Для синхронного интерфейса асинхронных запросов реализованно следующее взаимодействие.

### 1. Получение результатов асинхронного запроса

```plantuml
App -> Client: asyncQuery()
Client -> Pool: getFreeConnection()
Client -> Connection: asyncQuery
Connection --> Client: new AsyncResult
Client --> App
App -> AsyncResult: rows()
AsyncResult -> Connection: wait()
Connection --> AsyncResult: new Result
AsyncResult -> Result: rows()
AsyncResult --> App
```

### 2. Синхронизация асинхронных запросов, когда все соединения уже заняты

```plantuml
App -> Client: asyncQuery()
Client -> Pool: getFreeConnection()
Pool -> Pool: poll() // опрашивает все подключения
Pool -> Connection: sync() // первое ответившее синхронизируется
Connection -> AsyncResult: setResult()
Pool --> Client: найденое подключение теперь свободно и может выполнить следующий запрос
Client -> Connection: asyncQuery()
```