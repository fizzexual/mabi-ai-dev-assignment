# MABI Member API

WordPress REST API плъгин за платформата МАБИ. Регистрира endpoint `/wp-json/mabi/v1/member/{user_id}`, който връща данни за членството на потребител.

## Инсталация

1. Копирайте папката `task-a-wordpress-plugin` в `wp-content/plugins/mabi-member-api/` (или поставете `mabi-member-api.php` директно в `wp-content/mu-plugins/` за автоматично активиране).
2. В WordPress админ панела отидете на **Plugins** и активирайте **MABI Member API**.
3. Endpoint-ът е достъпен на `https://your-site.com/wp-json/mabi/v1/member/{user_id}`.

## Автентикация

Endpoint-ът изисква логнат потребител. Автентикация може да се осигури чрез:

- **Cookie автентикация** (по подразбиране при логнат потребител в браузъра).
- **Application Passwords** (WordPress 5.6+).
- **Всеки автентикационен плъгин**, който задава текущия потребител за REST заявки (JWT, OAuth и т.н.).

### Правила за достъп

| Извикващ          | Заявен потребител | Резултат |
|-------------------|-------------------|----------|
| Нелогнат          | Всеки             | `401`    |
| Subscriber (id 5) | id 5              | `200`    |
| Subscriber (id 5) | id 10             | `403`    |
| Administrator     | Всеки             | `200`    |

## Тестване с curl

### С Application Passwords

Генерирайте Application Password от **Users > Profile > Application Passwords**, след което:

```bash
# Собствени данни (заместете USER и APP_PASSWORD)
curl -u USER:APP_PASSWORD https://your-site.com/wp-json/mabi/v1/member/1

# Като админ — данни на друг потребител
curl -u ADMIN:APP_PASSWORD https://your-site.com/wp-json/mabi/v1/member/42
```

### С cookie автентикация (nonce)

```bash
# Първо вземете nonce (от логната браузър сесия), след което:
curl -H "X-WP-Nonce: <nonce_value>" \
     --cookie "wordpress_logged_in_xxx=..." \
     https://your-site.com/wp-json/mabi/v1/member/1
```

### Тестване с Postman

1. Метод: **GET**.
2. URL: `https://your-site.com/wp-json/mabi/v1/member/1`.
3. В **Authorization** изберете **Basic Auth** и въведете потребителско име и Application Password.
4. Изпратете заявката.

### Очакван отговор (200)

```json
{
  "user_id": 1,
  "display_name": "Иван Петров",
  "membership_active": true,
  "membership_level": "level_2",
  "membership_expires": "2026-12-31",
  "courses_completed": 4,
  "courses_total": 8,
  "last_login": "2026-05-28T14:32:00",
  "days_member": 187
}
```

### Отговори при грешка

```
401 — {"code":"rest_not_logged_in","message":"Трябва да сте влезли в профила си..."}
403 — {"code":"rest_forbidden","message":"Нямате права да преглеждате данните..."}
404 — {"code":"rest_user_not_found","message":"Потребител с ID 99 не е намерен."}
```

## User meta полета

Плъгинът чете следните `usermeta` ключове (всички са опционални — при липса се ползват стойности по подразбиране):

| Meta ключ                  | Тип     | По подразбиране                |
|----------------------------|---------|--------------------------------|
| `mabi_membership_active`   | bool    | `true`                         |
| `mabi_membership_level`    | string  | `level_1`                      |
| `mabi_membership_expires`  | string  | Една година от текущата дата    |
| `mabi_courses_completed`   | int     | `0`                            |
| `mabi_courses_total`       | int     | `8`                            |
| `mabi_last_login`          | string  | Текущ timestamp                |
| `mabi_registration_date`   | string  | Полето `user_registered`       |

Могат да се задават чрез `update_user_meta()` или през админ панела с плъгин за custom полета.

## Unit тестове

Изисква настроен [WordPress test suite](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/).

```bash
# От корена на WordPress, с настроен test suite:
phpunit --filter Test_MABI_Member_API
```

## Кеширане

Отговорите се кешират чрез WordPress transients за **5 минути** на потребител. Формат на ключа: `mabi_member_{user_id}`. За програмно изчистване:

```php
MABI_Member_API::clear_cache( $user_id );
```

## Какво бих подобрил с повече време

- **JSON Schema**: Добавяне на пълна схема чрез `get_item_schema()` за автоматична документация.
- **Реална LMS интеграция**: Заместване на mock данните с реални заявки към LMS плъгин (LearnDash, LifterLMS, Tutor LMS).
- **Cache invalidation hooks**: Автоматично изчистване на кеша при промяна на usermeta чрез `updated_user_meta` / `added_user_meta` хукове.
- **Rate limiting**: Ограничаване на заявките по IP или потребител.
- **Пагинация и филтриране**: Списък на множество членове за админ панели (`/mabi/v1/members?level=level_2&page=1`).
- **Object caching**: Използване на `wp_cache_*` функции (Redis/Memcached) вместо transients за по-добра производителност.
