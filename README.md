# BiblioSmart Backend API (Laravel)

Backend-only API for BiblioSmart.

## Quick Start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

Base URL: `http://127.0.0.1:8000`
API root: `http://127.0.0.1:8000/api/v1`

## Seeded Database Included

This repository includes a seeded SQLite database at:

- `database/database.sqlite`

So after clone, you can run directly:

```bash
php artisan serve
```

If you want to reset and reseed:

```bash
php artisan migrate:fresh --seed
```

## Default Accounts (Seeded)

- Admin
  - Email: `admin@biblio.test`
  - Password: `Admin123!`
- Librarian
  - Email: `librarian@biblio.test`
  - Password: `Librarian123!`
- Reader (example)
  - Email: `reader1@biblio.test`
  - Password: `Reader123!`

## What Seeder Populates

Seeder fills core and support tables, including:

- `users`, `authors`, `genres`, `books`, `book_authors`, `book_genres`, `book_copies`
- `loans`, `reservations`, `ratings`, `notifications`
- `audit_logs`, `refresh_tokens`, `revoked_access_tokens`
- framework tables (`password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`)

## Run Tests

```bash
php artisan test
```

## Postman

Import collection:

- `postman/BiblioSmart_API_Local.postman_collection.json`
