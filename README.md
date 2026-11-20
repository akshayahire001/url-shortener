# URL Shortener

Laravel 10 service for one company, several users, and short URLs. The rules below follow the assignment text as written.

## Rules

- The service cannot contain more than one company.
- Roles are SuperAdmin, Admin, Member, Sales, and Manager.
- The SuperAdmin account is inserted by a database seeder with raw SQL.
- Users log in and log out with session authentication.
- A user is not assigned the Admin or Member role. Those roles exist so the authorization rules can be tested.
- SuperAdmin cannot invite an Admin into a new company.
- An Admin cannot invite another Admin or Member into their own company.
- Admin and Member cannot create short URLs.
- SuperAdmin cannot create short URLs.
- Sales and Manager can create short URLs. They are the only roles the assignment does not forbid.
- SuperAdmin cannot see the list of short URLs for every company.
- An Admin sees only short URLs that were not created in their own company.
- A Member sees only short URLs that were not created by themselves.
- A short URL is stored, but `/s/{code}` is not public and does not redirect to the original URL.

Validation lives in form requests and in the domain services. `UrlShortenerServiceProvider` binds the service interfaces in Laravel's service container, and the controllers receive those interfaces.

## Setup

Requirements: PHP 8.1, Composer, and MySQL (XAMPP is enough).

```bash
cd url-shortener
composer install
copy .env.example .env
php artisan key:generate
```

Create the database, then migrate and seed:

```sql
CREATE DATABASE url_shortener CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan migrate --seed
php artisan test
```

Open [http://localhost/url-shortener/public](http://localhost/url-shortener/public).

`.env` defaults:

- `DB_CONNECTION=mysql`
- `DB_DATABASE=url_shortener`
- `DB_USERNAME=root`
- `DB_PASSWORD=` empty

Tests use SQLite in memory and do not touch the MySQL database.

## Seeded accounts

Password for each account is `password`.

| Email | Role |
| --- | --- |
| superadmin@example.com | SuperAdmin |
| sales@example.com | Sales |
| manager@example.com | Manager |

Sales and Manager can create short URLs. SuperAdmin can invite Sales or Manager into the existing company. The invitation link is shown after the invite is created.

## AI tools used

Cursor (Grok 4.7) was used to scaffold this Laravel 10 project and to implement validation, the service container, the service provider, and the assignment rules.
