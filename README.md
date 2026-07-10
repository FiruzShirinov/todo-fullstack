# Список задач — Laravel API + Nuxt 3 (SPA/SSR)

Мини-приложение «Список задач», выполненное в рамках тестового задания.
**Laravel** отвечает за данные, авторизацию и бизнес-правила; **Nuxt 3** — за
интерфейс и взаимодействие с API.

- **Backend:** Laravel 12, PHP 8.3, Eloquent, миграции, сиды, Form Request-валидация, политики доступа, Sanctum.
- **Frontend:** Nuxt 3 (рантайм Nuxt 4), Vue 3 Composition API, Pinia, `vue-router`, composables.
- **База данных:** SQLite (без настройки; MySQL/PostgreSQL поддерживаются через `.env`).
- **Авторизация:** **Bearer-токены Laravel Sanctum** (см. [Подход к авторизации](#подход-к-авторизации)).

---

## Содержание

- [Быстрый старт (Docker)](#быстрый-старт-docker)
- [Быстрый старт (локально)](#быстрый-старт-локально)
- [Тестовые аккаунты](#тестовые-аккаунты)
- [Подход к авторизации](#подход-к-авторизации)
- [Запуск тестов](#запуск-тестов)
- [Стандарты кода](#стандарты-кода)
- [Документация API](#документация-api)
- [Структура проекта](#структура-проекта)
- [Реализованные возможности](#реализованные-возможности)

---

## Быстрый старт (Docker)

Требуется Docker Desktop.

```bash
# из корня репозитория
cp backend/.env.example backend/.env
docker compose up --build
```

- Frontend: <http://localhost:3000>
- Backend API: <http://localhost:8000/api>

Контейнер backend при первом запуске сам создаёт базу SQLite, применяет миграции и
загружает тестовые данные (сиды).

---

## Быстрый старт (локально)

Требования: **PHP 8.2+** (с расширениями `pdo_sqlite`, `mbstring`, `openssl`, `curl`),
**Composer 2**, **Node 20+** и **npm**.

### 1. Backend (Laravel API) — <http://localhost:8000>

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

# создать файл SQLite, применить миграции и загрузить тестовые данные
php artisan migrate --seed

php artisan serve                   # http://localhost:8000
```

> На Windows/macOS, если файла `database/database.sqlite` ещё нет:
> `php -r "touch('database/database.sqlite');"`, затем снова `php artisan migrate --seed`.

### 2. Frontend (Nuxt) — <http://localhost:3000>

```bash
cd frontend
npm install
cp .env.example .env                # NUXT_PUBLIC_API_BASE=http://localhost:8000/api
npm run dev                         # http://localhost:3000
```

Откройте <http://localhost:3000> и войдите под одним из тестовых аккаунтов ниже.

---

## Тестовые аккаунты

Создаются сидером `database/seeders/DatabaseSeeder.php`. Пароль у всех один — **`password`**.

| Роль         | Email                | Пароль     | Видит                          | Может редактировать/удалять |
|--------------|-----------------------|------------|--------------------------------|------------------------------|
| Админ        | `admin@example.com`  | `password` | задачи **всех** пользователей  | любую задачу                 |
| Наблюдатель  | `viewer@example.com` | `password` | задачи **всех** пользователей  | только свои задачи           |
| Пользователь | `user@example.com`   | `password` | только свои задачи             | только свои задачи           |
| Пользователь | `user2@example.com`  | `password` | только свои задачи             | только свои задачи           |

Каждому пользователю создаётся 10 задач (4 «Ожидает», 3 «В работе», 3 «Выполнено»).

Роль **«наблюдатель» (`viewer`)** — дополнительный тип пользователя: видит задачи
всех пользователей (как админ), но может редактировать/удалять только свои
собственные — в отличие от админа, у которого есть полный доступ ко всем задачам.

---

## Подход к авторизации

**Laravel Sanctum, режим bearer-токенов (stateless).**

- `POST /api/auth/login` проверяет учётные данные и возвращает **персональный
  access-токен** (`$user->createToken()`) вместе с объектом пользователя.
- Nuxt хранит токен в cookie (`auth_token`) через `useCookie`, поэтому он
  сохраняется между перезагрузками **и доступен во время SSR**.
- Каждый запрос к API передаёт заголовок `Authorization: Bearer <token>`
  (см. `app/composables/useApi.ts`).
- `POST /api/auth/logout` отзывает текущий токен (`currentAccessToken()->delete()`).
- При любом ответе `401` клиент очищает токен и перенаправляет на `/login`.

**Почему bearer-токены, а не cookie/SPA-режим Sanctum?** Frontend работает на другом
origin (`:3000`), чем API (`:8000`). Bearer-токены избавляют от настройки cross-site
cookie, CSRF и `SESSION_DOMAIN`, сохраняют API полностью stateless и одинаково работают
для SSR, браузера и инструментов вроде `curl`/Postman.

Защита маршрутов на клиенте реализована через middleware Nuxt
(`app/middleware/auth.ts`, `guest.ts`), которые выполняются и на сервере, и на клиенте.

---

## Запуск тестов

### Backend — feature-тесты PHPUnit (22 теста)

```bash
cd backend
php artisan test
```

Покрывают авторизацию (успешный/неуспешный вход, валидация, защита 401, выход),
CRUD задач, политики доступа (403 на чужие задачи), доступ администратора и
наблюдателя (видит все, редактирует только своё), фильтры по статусу и поиск,
а также JSON-контракт `404`. Используется SQLite в памяти.

### Frontend — Vitest (22 теста)

```bash
cd frontend
npm run test
```

Покрывают правила клиентской валидации, хелперы форматирования (в т.ч. русскую
плюрализацию) и компоненты `TaskForm` / `StatusBadge` (ошибки валидации, payload
отправки, предзаполнение при редактировании).

---

## Стандарты кода

```bash
# Backend — стиль кода (Laravel Pint / PSR-12)
cd backend && ./vendor/bin/pint --test

# Frontend — проверка типов и линтер
cd frontend
npm run typecheck     # vue-tsc, 0 ошибок
npm run lint          # ESLint (typescript-eslint + eslint-plugin-vue), 0 замечаний
```

---

## Документация API

> **Полная спецификация:** [`docs/openapi.yaml`](docs/openapi.yaml) (OpenAPI 3.0) —
> откройте в [editor.swagger.io](https://editor.swagger.io) (File → Import file) или
> просмотрите локально: `npx @redocly/cli preview-docs docs/openapi.yaml`.
> В задании OpenAPI и краткое описание в README были заданы как альтернатива
> («OpenAPI/Swagger-документация **или** краткое описание API в README») — ниже
> дублируем краткое описание для удобства чтения прямо здесь, а полный
> машиночитаемый контракт со схемами лежит в `openapi.yaml`.

Базовый URL: `http://localhost:8000/api`. Все ответы — JSON. Защищённые эндпоинты
требуют заголовок `Authorization: Bearer <token>`.

### Эндпоинты

| Метод  | Эндпоинт            | Доступ                    | Назначение                                         |
|--------|---------------------|---------------------------|-----------------------------------------------------|
| POST   | `/auth/login`       | публичный                 | Авторизация, возвращает `{ token, user }`          |
| POST   | `/auth/logout`      | auth                      | Отзыв текущего токена                              |
| GET    | `/user`             | auth                      | Текущий пользователь                               |
| GET    | `/tasks`            | auth                      | Список задач (поиск, фильтр, сортировка, пагинация)|
| POST   | `/tasks`            | auth                      | Создание задачи                                    |
| GET    | `/tasks/{id}`       | владелец/админ/наблюдатель | Просмотр одной задачи                             |
| PUT/PATCH | `/tasks/{id}`    | владелец/админ             | Редактирование задачи                             |
| DELETE | `/tasks/{id}`       | владелец/админ             | Удаление задачи                                   |

### Параметры запроса `GET /tasks`

| Параметр    | Значения                                           | По умолчанию |
|-------------|----------------------------------------------------|--------------|
| `search`    | произвольный текст (по заголовку и описанию)        | —            |
| `status`    | `pending` \| `in_progress` \| `completed`          | —            |
| `sort`      | `due_date` \| `status` \| `title` \| `created_at`  | `due_date`   |
| `direction` | `asc` \| `desc`                                    | `asc`        |
| `page`      | целое число                                        | `1`          |
| `per_page`  | 1–100                                              | `10`         |

### Пример: авторизация

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'
```

```json
{
  "token": "3|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "user": { "id": 2, "name": "Иван Петров", "email": "user@example.com", "role": "user" }
}
```

### Пример: список задач

```bash
curl "http://localhost:8000/api/tasks?status=pending&sort=due_date&page=1" \
  -H "Accept: application/json" -H "Authorization: Bearer <token>"
```

```json
{
  "data": [
    {
      "id": 12,
      "user_id": 2,
      "title": "Купить продукты",
      "description": null,
      "due_date": "2026-08-01",
      "status": "pending",
      "created_at": "2026-07-10T09:00:00+00:00",
      "updated_at": "2026-07-10T09:00:00+00:00",
      "can": { "update": true, "delete": true }
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "last_page": 2, "per_page": 10, "total": 14, "from": 1, "to": 10 }
}
```

Объект `can` позволяет интерфейсу скрывать кнопки редактирования/удаления, недоступные пользователю.

### Формат ошибок

Все ошибки имеют единый JSON-формат (см. `bootstrap/app.php`):

| Статус | Когда                                  | Тело                                              |
|--------|----------------------------------------|---------------------------------------------------|
| 401    | нет/невалидный токен                   | `{ "message": "Unauthenticated." }`               |
| 403    | действие с чужой задачей               | `{ "message": "This action is unauthorized." }`   |
| 404    | задача не найдена                      | `{ "message": "Resource not found." }`            |
| 422    | ошибка валидации                       | `{ "message": "...", "errors": { "title": [...] } }` |
| 500    | непредвиденная ошибка                  | `{ "message": "Server error." }`                  |

### Модель задачи

| Поле          | Тип           | Примечание                                   |
|---------------|---------------|----------------------------------------------|
| `id`          | integer       |                                              |
| `user_id`     | integer       | владелец                                     |
| `title`       | string        | обязательный, 3–255 символов                 |
| `description` | text, nullable|                                              |
| `due_date`    | date, nullable| `YYYY-MM-DD`                                 |
| `status`      | enum          | `pending` \| `in_progress` \| `completed`    |
| `created_at` / `updated_at` | datetime |                                    |

> Значения `status` намеренно остаются на английском на уровне API/БД (как в
> техническом задании); в интерфейсе они отображаются по-русски (Ожидает / В работе / Выполнено).

---

## Структура проекта

```
todo-fullstack/
├── backend/                 # Laravel API
│   ├── app/
│   │   ├── Enums/           # TaskStatus, UserRole
│   │   ├── Http/
│   │   │   ├── Controllers/Api/   # AuthController, TaskController
│   │   │   ├── Requests/          # LoginRequest, Store/UpdateTaskRequest
│   │   │   └── Resources/         # TaskResource, UserResource
│   │   ├── Models/          # User, Task
│   │   └── Policies/        # TaskPolicy (владелец/админ)
│   ├── bootstrap/app.php    # единый JSON-формат ошибок
│   ├── database/            # миграции, фабрики, сиды
│   ├── routes/api.php
│   └── tests/Feature/       # AuthTest, TaskTest
├── frontend/                # приложение Nuxt 3
│   └── app/
│       ├── components/      # TaskCard, TaskForm, TaskToolbar, PaginationBar, …
│       ├── composables/     # useApi, useTasks
│       ├── middleware/      # auth, guest
│       ├── pages/           # login, index (список задач)
│       ├── plugins/         # auth (загрузка текущего пользователя)
│       ├── stores/          # auth (Pinia)
│       └── utils/           # validation, format (чистые функции, покрыты тестами)
├── docs/
│   └── openapi.yaml         # полная OpenAPI 3.0 спецификация
├── docker-compose.yml
└── README.md
```

---

## Реализованные возможности

**Обязательное**

- [x] REST API для авторизации и задач, модели Eloquent, миграции, фабрики и сиды
- [x] Валидация через Form Request
- [x] Единый JSON-формат ошибок: 401 / 403 / 404 / 422 / 500
- [x] Редактирование/удаление ограничено владельцем задачи (Policy)
- [x] Страница входа с обработкой ошибок, хранение токена, редирект на `/login` при 401
- [x] Список задач: отображение, сортировка по дедлайну и статусу, фильтр по статусу
- [x] Создание / редактирование / удаление через модальное окно, без перезагрузки
- [x] Состояния загрузки, ошибки и пустого списка
- [x] Клиентская валидация форм

**Дополнительное**

- [x] Роли admin/viewer/user — админ видит и может редактировать все задачи;
      наблюдатель видит все задачи, но редактирует/удаляет только свои;
      пользователь видит и редактирует только свои
- [x] Кнопки редактирования/удаления скрыты при отсутствии доступа (флаги `can`)
- [x] Поиск с debounce, синхронизированный с query-параметрами URL, с кнопкой очистки
- [x] Пагинация (backend + frontend), с равномерной высотой карточек задач
- [x] Feature-тесты backend (22) + тесты frontend (22)
- [x] Документация API: OpenAPI 3.0 (`docs/openapi.yaml`) + краткое описание в README
- [x] Docker Compose для запуска одной командой
- [x] Совместимость с SSR (токен доступен при серверном рендеринге)
- [x] Стандарты кода: Laravel Pint, ESLint, проверка типов vue-tsc
