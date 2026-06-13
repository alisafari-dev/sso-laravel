# Laravel Keycloak SSO

پکیج Laravel برای اتصال [Keycloak](https://www.keycloak.org/) با [Socialite](https://laravel.com/docs/socialite) و [SocialiteProviders/Keycloak](https://socialiteproviders.com/Keycloak/).

مسیرها با **prefix** ثبت می‌شوند تا با routeهای بقیه سرویس‌ها یا ماژول‌های پروژه تداخل نداشته باشند.

## ویژگی‌ها

- نصب خودکار `laravel/socialite` و `socialiteproviders/keycloak`
- ثبت خودکار درایور Keycloak در Socialite
- route، view و migration آماده
- prefix قابل تنظیم برای routeها (پیش‌فرض: `/sso`)
- sync کاربر محلی بر اساس claim قابل تنظیم (پیش‌فرض: `employeeID` → `personal_id`)
- logout کامل از Laravel و Keycloak

## پیش‌نیازها

- PHP 8.3+
- Laravel 11 / 12 / 13
- یک سرور Keycloak در دسترس

## نصب

### ۱. افزودن repository (اگر روی Packagist نیست)

در `composer.json` پروژه:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/YOUR_USER/laravel-keycloak-sso"
    }
],
"require": {
    "asafari/laravel-keycloak-sso": "^1.0"
}
```

### ۲. نصب پکیج

```bash
composer require asafari/laravel-keycloak-sso
php artisan vendor:publish --tag=keycloak-sso-config
php artisan vendor:publish --tag=keycloak-sso-migrations
php artisan migrate
```

وابستگی‌های زیر به‌صورت خودکار نصب می‌شوند:

- `laravel/socialite`
- `socialiteproviders/keycloak`

## معماری

```
کاربر → /sso/login → /sso/auth/keycloak → Keycloak
                                              ↓
                                    /sso/callback
                                              ↓
                              ایجاد/به‌روزرسانی User
                                              ↓
                              redirect_after_login
```

## تنظیم Keycloak

در **Clients → Create client** (OpenID Connect, confidential):

| فیلد | مقدار نمونه |
|---|---|
| Client ID | `laravel-app` |
| Valid redirect URIs | `http://your-host/sso/callback` |
| Valid post logout redirect URIs | `http://your-host/` |
| Root URL | `http://your-host` |

نکات:

- `KEYCLOAK_REDIRECT_URI` باید **دقیقاً** با Valid redirect URIs یکی باشد.
- post-logout معمولاً با `/` انتهایی ثبت می‌شود: `http://your-host/`
- claim شناسه کاربر (پیش‌فرض `employeeID`) باید در userinfo یا token mapper Keycloak موجود باشد.

## متغیرهای محیطی

```env
APP_URL=http://your-host

# Keycloak
KEYCLOAK_BASE_URL=http://keycloak-host:8080
KEYCLOAK_REALM=master
KEYCLOAK_CLIENT_ID=laravel-app
KEYCLOAK_CLIENT_SECRET=your-client-secret
KEYCLOAK_REDIRECT_URI="${APP_URL}/sso/callback"
KEYCLOAK_POST_LOGOUT_REDIRECT_URI="${APP_URL}/"

# پکیج (اختیاری)
KEYCLOAK_SSO_ROUTE_PREFIX=sso
KEYCLOAK_SSO_USER_MODEL=App\\Models\\User
KEYCLOAK_SSO_USER_IDENTIFIER_COLUMN=personal_id
KEYCLOAK_SSO_USER_IDENTIFIER_CLAIM=employeeID
KEYCLOAK_SSO_PLACEHOLDER_EMAIL_DOMAIN=sso.local
KEYCLOAK_SSO_REDIRECT_AFTER_LOGIN=dashboard
KEYCLOAK_SSO_ROUTES_ENABLED=true
KEYCLOAK_SSO_LOGIN_VIEW=keycloak-sso::login
```

| متغیر | پیش‌فرض | توضیح |
|---|---|---|
| `KEYCLOAK_BASE_URL` | — | آدرس Keycloak بدون `/realms/...` |
| `KEYCLOAK_REALM` | `master` | نام realm |
| `KEYCLOAK_CLIENT_ID` | — | Client ID |
| `KEYCLOAK_CLIENT_SECRET` | — | Secret کلاینت confidential |
| `KEYCLOAK_REDIRECT_URI` | — | باید با callback در Keycloak یکی باشد |
| `KEYCLOAK_POST_LOGOUT_REDIRECT_URI` | — | URI بازگشت بعد از logout |
| `KEYCLOAK_SSO_ROUTE_PREFIX` | `sso` | prefix مسیرها |
| `KEYCLOAK_SSO_USER_IDENTIFIER_COLUMN` | `personal_id` | ستون شناسه در جدول users |
| `KEYCLOAK_SSO_USER_IDENTIFIER_CLAIM` | `employeeID` | claim در پاسخ Keycloak |
| `KEYCLOAK_SSO_REDIRECT_AFTER_LOGIN` | `dashboard` | route name بعد از login |
| `KEYCLOAK_SSO_PLACEHOLDER_EMAIL_DOMAIN` | `sso.local` | دامنه ایمیل موقت اگر email نباشد |

## مسیرها

پیش‌فرض با prefix `sso`:

| Method | URI | Route name |
|---|---|---|
| GET | `/sso/login` | `keycloak-sso.login` |
| GET | `/sso/auth/keycloak` | `keycloak-sso.redirect` |
| GET | `/sso/callback` | `keycloak-sso.callback` |
| POST | `/sso/logout` | `keycloak-sso.logout` |

برای تغییر prefix:

```env
KEYCLOAK_SSO_ROUTE_PREFIX=auth/sso
```

آنگاه callback می‌شود: `/auth/sso/callback` — همان را در Keycloak ثبت کنید.

## یکپارچه‌سازی با پروژه

### ۱. مدل User

ستون شناسه (از migration پکیج) و fillable:

```php
#[Fillable(['name', 'email', 'password', 'personal_id'])]
class User extends Authenticatable
{
    // ...
}
```

### ۲. redirect مهمان‌ها

در `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->redirectGuestsTo(fn () => route('keycloak-sso.login'));
})
```

### ۳. دکمه خروج

```blade
<form method="POST" action="{{ route('keycloak-sso.logout') }}">
    @csrf
    <button type="submit">خروج</button>
</form>
```

### ۴. (اختیاری) alias برای `/login`

اگر Breeze یا لینک‌های قدیمی `route('login')` دارند:

```php
Route::redirect('login', '/sso/login')->name('login');
```

## سفارشی‌سازی

### publish view

```bash
php artisan vendor:publish --tag=keycloak-sso-views
```

فایل‌ها در `resources/views/vendor/keycloak-sso/` قرار می‌گیرند.

### تغییر claim شناسه

اگر به‌جای `employeeID` از claim دیگری استفاده می‌کنید:

```env
KEYCLOAK_SSO_USER_IDENTIFIER_CLAIM=sub
KEYCLOAK_SSO_USER_IDENTIFIER_COLUMN=keycloak_id
```

### تغییر مدل User

```env
KEYCLOAK_SSO_USER_MODEL=App\\Models\\Admin
```

## رفع مشکل

### `Invalid redirect uri` در login

- `KEYCLOAK_REDIRECT_URI` را با Valid redirect URIs در Keycloak مقایسه کنید.
- پروتکل، host، port و مسیر باید دقیقاً یکی باشند.

### `Invalid redirect uri` در logout

- `KEYCLOAK_POST_LOGOUT_REDIRECT_URI` را در Keycloak ثبت کنید.
- معمولاً `http://host/` (با `/` انتهایی) لازم است.

### `شناسه کاربر در اطلاعات Keycloak یافت نشد`

- claim تنظیم‌شده (`employeeID`) در userinfo Keycloak وجود ندارد.
- در Keycloak برای client، mapper مناسب اضافه کنید یا `KEYCLOAK_SSO_USER_IDENTIFIER_CLAIM` را تغییر دهید.

### `NOT NULL constraint failed: users.email`

- کاربر Keycloak email ندارد؛ پکیج برای کاربر جدید ایمیل `{username}@sso.local` می‌سازد.
- اگر email اجباری است، در Keycloak برای کاربر email تنظیم کنید.

## License

MIT
