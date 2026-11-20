# Environment Setup

How to set up the project for development on Windows with XAMPP. The steps are the same on macOS or
Linux; only the paths differ.

## Requirements

| Tool | Version used | Minimum |
|---|---|---|
| PHP | 8.2.12 (XAMPP) | 8.2, with `gd`, `intl`, `zip`, `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` |
| Composer | 2.8.9 | 2.x |
| MariaDB | 10.4.32 (XAMPP) | MariaDB 10.3 / MySQL 5.7 |
| Node.js | 22.13.1 | 20.19 |
| npm | 11.3.0 | 10 |

Laravel 13 needs PHP 8.3, so this project uses **Laravel 12**.

## 1. Make PHP and MySQL available in the terminal

Add these folders to your **user** `PATH` (Windows Settings → "Edit environment variables for your account"):

```
C:\xampp\php
C:\xampp\mysql\bin
```

Open a **new** terminal and check: `php -v`, `composer --version`, `mysql --version`, `node -v`.

## 2. Enable PHP extensions

In `C:\xampp\php\php.ini`, remove the leading `;` from:

```ini
extension=gd     ; resizes and re-encodes product images
extension=intl   ; currency and number formatting
extension=zip    ; lets Composer unpack packages quickly
```

Run `php -m` to confirm `gd`, `intl` and `zip` are listed.

## 3. Start MySQL and create the database

Start **MySQL** in the XAMPP Control Panel (default login: `root`, empty password), then:

```bash
mysql -u root -e "CREATE DATABASE inventory_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

## 4. Install the project

```bash
composer install
npm install
cp .env.example .env          # skip if .env exists
php artisan key:generate
php artisan migrate
php artisan storage:link      # makes uploaded product images reachable at /storage/...
```

Then either load demo data or create your own administrator:

```bash
php artisan db:seed               # demo shop: admin@example.com / staff@example.com, password Password123
php artisan users:create-admin    # or: create a real admin account interactively
```

There is no public registration page. Other accounts are created by an administrator under **Users**.

## 5. Settings in `.env`

```dotenv
APP_NAME="Inventory Management"
APP_URL=http://127.0.0.1:8000
INVENTORY_CURRENCY=USD        # any ISO 4217 code, used to display prices

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_management
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=log               # password-reset emails are written to storage/logs/laravel.log
```

Run `php artisan config:clear` after changing `.env`.

## 6. Run the application

```bash
php artisan serve     # terminal 1: http://127.0.0.1:8000
npm run dev           # terminal 2: rebuilds CSS/JS as you edit (optional)
```

Without `npm run dev`, run `npm run build` once so that `public/build` exists.

## 7. Run the checks

```bash
php artisan test
./vendor/bin/pint --test
npm run build
```

See [TESTING.md](TESTING.md) for running the tests against MySQL as well.

## Troubleshooting

| Problem | Fix |
|---|---|
| `php: command not found` | PATH is missing `C:\xampp\php`, or the terminal was opened before PATH changed |
| `SQLSTATE[HY000] [2002]` | MySQL is not running; start it in XAMPP |
| `Unknown database` | Run the `CREATE DATABASE` command in step 3 |
| Product images show as broken | Run `php artisan storage:link` |
| "The image could not be read" or a GD error | `extension=gd` is not enabled (step 2) |
| `Vite manifest not found` | Run `npm run build`, or keep `npm run dev` running |
| `php artisan db:show` fails with `performance_schema` | Known MariaDB 10.4 limitation; the connection is fine. Use `php artisan migrate:status` |
| Composer `curl error 28` timeouts | Slow network or IPv6 DNS: `COMPOSER_IPRESOLVE=4 composer install` |
| `composer run dev` fails on `pail` | `laravel/pail` needs `pcntl`, which Windows lacks. Run `php artisan serve` and `npm run dev` separately |
| Password-reset email never arrives | With `MAIL_MAILER=log` it is in `storage/logs/laravel.log`. Configure SMTP for real email |
