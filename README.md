# ЛР №3–5. Laravel: галерея картин

Приложение «АртГалерея» на Laravel 12. Bootstrap/SCSS (галерея) и Tailwind (страницы Breeze) собираются через Laravel Mix.

- **ЛР3** — CRUD картин: миграции, Eloquent, контроллер, Blade, валидация, загрузка и обработка изображений, Soft Deletes.
- **ЛР4** — многопользовательское приложение: Laravel Breeze, владелец картины, администратор, права доступа.
- **ЛР5** — связи Eloquent: комментарии к картинам, дружба пользователей, лента.

## Запуск

    composer install
    npm install
    copy .env.example .env
    php artisan key:generate
    type nul > database\database.sqlite
    php artisan migrate --seed
    php artisan storage:link
    npm run build
    php artisan serve

Сайт: http://127.0.0.1:8000/paintings

Тесты (используют отдельную БД в памяти): `php artisan test`

## ЛР4. Многопользовательское приложение

### Тестовые пользователи

Создаются сидером `UserSeeder`, пароль у всех `password`.

| Имя | username | E-mail |
|---|---|---|
| Администратор | admin | admin@example.com |
| Анна Иванова | anna | anna@example.com |
| Пётр Смирнов | petr | petr@example.com |

Картины, добавленные до ЛР4, сидер распределяет между `anna` и `petr`.

Флаг администратора выставляется через **tinker**:

    php artisan tinker
    >>> $u = App\Models\User::where('username', 'admin')->first();
    >>> $u->is_admin = true;
    >>> $u->save();

### Что реализовано

- **Миграции**: `paintings.user_id` (внешний ключ на `users`), `users.username` (уникальный) и `users.is_admin` (по умолчанию `false`).
- **Laravel Breeze** (Blade): `/login`, `/register`, `/profile`, сброс пароля. В регистрацию и профиль добавлено поле `username`.
- **Маршруты** (`routes/web.php`):
  - `GET /paintings`, `GET /paintings/{id}` — просмотр, доступен всем;
  - `GET /users` — все пользователи; `GET /users/{username}` — картины пользователя (ссылка строится по username, а не по ID);
  - `create/store/edit/update/destroy` — только для авторизованных (`auth`) и с проверкой Gate (`can:`);
  - `PATCH /paintings/{id}/restore`, `DELETE /paintings/{id}/force` — восстановление и удаление без возврата, только администратор.
- **Правила доступа** (`AppServiceProvider`, Gates):
  1. создавать картины может любой авторизованный пользователь;
  2. редактировать — только автор записи (или администратор);
  3. мягко удалить — автор записи (или администратор);
  4. восстановить и удалить без возврата — только администратор.
- **Проверка прав на трёх уровнях**:
  - интерфейс — `@can` в шаблонах, кнопки без прав не показываются;
  - маршрут и контроллер — middleware `can:` и `Gate::authorize()`;
  - модель — события Eloquent (`creating`, `updating`, `deleting`, `restoring`) в `Painting::booted()`; модель записывает `user_id` текущего пользователя и не даёт изменить чужую картину даже в обход контроллера.
- **Soft Deletes**: автор и администратор видят на странице пользователя и удалённые картины (красная рамка); администратору доступны кнопки «Восстановить» и «Удалить навсегда».
- **Навигация по пользователям**: список пользователей сбоку и кнопки «предыдущий / следующий».
- **Тесты**: `tests/Feature/PaintingAuthorizationTest.php`.
