# MABI Member API

WordPress REST API plugin for the MABI platform. Exposes a `/wp-json/mabi/v1/member/{user_id}` endpoint that returns membership data for a given user.

## Installation

1. Copy the `mabi-task-a` folder into `wp-content/plugins/mabi-member-api/` (or place `mabi-member-api.php` directly into `wp-content/mu-plugins/` for auto-activation).
2. In the WordPress admin dashboard go to **Plugins** and activate **MABI Member API**.
3. The endpoint is now available at `https://your-site.com/wp-json/mabi/v1/member/{user_id}`.

## Authentication

The endpoint requires a logged-in user. Authentication can be provided via:

- **Cookie authentication** (default when using the browser while logged in).
- **Application Passwords** (WordPress 5.6+).
- **Any authentication plugin** that sets the current user for REST requests (JWT, OAuth, etc.).

### Access rules

| Caller            | Requested user | Result |
|-------------------|---------------|--------|
| Not logged in     | Any           | `401`  |
| Subscriber (id 5) | id 5          | `200`  |
| Subscriber (id 5) | id 10         | `403`  |
| Administrator     | Any           | `200`  |

## Testing with curl

### Using Application Passwords

Generate an Application Password in **Users > Profile > Application Passwords**, then:

```bash
# Fetch your own data (replace USER and APP_PASSWORD)
curl -u USER:APP_PASSWORD https://your-site.com/wp-json/mabi/v1/member/1

# As admin, fetch another user's data
curl -u ADMIN:APP_PASSWORD https://your-site.com/wp-json/mabi/v1/member/42
```

### Using cookie auth (nonce)

```bash
# Obtain a nonce first (logged-in browser session), then:
curl -H "X-WP-Nonce: <nonce_value>" \
     --cookie "wordpress_logged_in_xxx=..." \
     https://your-site.com/wp-json/mabi/v1/member/1
```

### Testing with Postman

1. Set method to **GET**.
2. URL: `https://your-site.com/wp-json/mabi/v1/member/1`.
3. Under **Authorization** choose **Basic Auth** and enter your username and Application Password.
4. Send the request.

### Expected response (200)

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

### Error responses

```
401 — {"code":"rest_not_logged_in","message":"Трябва да сте влезли в профила си..."}
403 — {"code":"rest_forbidden","message":"Нямате права да преглеждате данните..."}
404 — {"code":"rest_user_not_found","message":"Потребител с ID 99 не е намерен."}
```

## Populating user meta

The plugin reads the following `usermeta` keys (all optional — sensible defaults are used when absent):

| Meta key                    | Type    | Default                        |
|----------------------------|---------|--------------------------------|
| `mabi_membership_active`   | bool    | `true`                         |
| `mabi_membership_level`    | string  | `level_1`                      |
| `mabi_membership_expires`  | string  | One year from current date     |
| `mabi_courses_completed`   | int     | `0`                            |
| `mabi_courses_total`       | int     | `8`                            |
| `mabi_last_login`          | string  | Current timestamp              |
| `mabi_registration_date`   | string  | `user_registered` field        |

You can set these via `update_user_meta()` or through the admin UI with a custom fields plugin.

## Running unit tests

Requires the [WordPress test suite](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/) to be set up.

```bash
# From the WordPress root, with the test suite configured:
phpunit --filter Test_MABI_Member_API
```

## Caching

Responses are cached using WordPress transients for **5 minutes** per user. The cache key format is `mabi_member_{user_id}`. To clear a user's cache programmatically:

```php
MABI_Member_API::clear_cache( $user_id );
```

## What I would improve with more time

- **Schema definition**: Add a full JSON Schema via `get_item_schema()` on the REST controller for auto-documentation and client code generation.
- **Real LMS integration**: Replace mock meta keys with queries to an actual LMS plugin (LearnDash, LifterLMS, Tutor LMS) for `courses_completed` and `courses_total`.
- **Cache invalidation hooks**: Automatically clear the transient when relevant usermeta is updated via `updated_user_meta` / `added_user_meta` hooks.
- **Rate limiting**: Add per-IP or per-user rate limiting to protect against abuse.
- **Pagination and filtering**: Support listing multiple members for admin dashboards (`/mabi/v1/members?level=level_2&page=1`).
- **Internationalization**: Load a `.po`/`.mo` translation file so all translatable strings are properly localized.
- **OpenAPI / Swagger spec**: Auto-generate API documentation from the registered schema.
- **Object caching support**: Use `wp_cache_*` functions (backed by Redis/Memcached) instead of transients for better performance in scaled environments.
- **Webhook notifications**: Fire a webhook or action when membership status changes so external systems can stay in sync.
