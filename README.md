# PHP Education API (UUID + JWT + PostgreSQL)

Мини-проект для практики REST API на PHP:
- PostgreSQL (UUID, связи)
- JWT авторизация (Bearer token)
- CRUD постов (только владелец может менять/удалять)
- Soft delete
- Локальный dev-сервер `php -S`

---

## Структура проекта
```
cd ./Ваша папка
php-example/
public/
index.php
router.php
src/
config.php
db.php
helpers.php
jwt.php
logger.php
api/
auth.php
users.php
posts.php
ping.php
migrations/
schema.sql
insert.sql
```

---

## Запуск

Перейти в папку проекта:

```bash
cd php-example

php -S 127.0.0.1:8000 -t public public/router.php 

```

Открыть в браузере:
```	http://127.0.0.1:8000```  или	```http://127.0.0.1:8000/api/ping```