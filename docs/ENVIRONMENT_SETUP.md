# Environment Setup

How this project was set up on Windows with XAMPP, and how to set it up again on a new machine.

## Requirements

| Tool     | Version used        | Minimum for this project |
|----------|---------------------|--------------------------|
| PHP      | 8.2.12 (XAMPP)      | 8.2                      |
| Composer | 2.8.9               | 2.x                      |
| MariaDB  | 10.4.32 (XAMPP)     | 10.3 / MySQL 5.7         |
| Node.js  | 22.13.1             | 20.19                    |
| npm      | 11.3.0              | 10                       |

Laravel 13 needs PHP 8.3, so Composer installed **Laravel 12**. Upgrade XAMPP's PHP first if you want Laravel 13.

## 1. Make PHP and MySQL available in the terminal

XAMPP ships PHP and MariaDB, but does not add them to `PATH`. Add these two folders to your **user** `PATH`
(Windows Settings → "Edit environment variables for your account"):

```
C:\xampp\php
C:\xampp\mysql\bin
```

Open a new terminal and check:

```bash
php -v
composer --version
mysql --version
node -v
npm -v
```

## 2. Enable the required PHP extensions

Open `C:\xampp\php\php.ini` and remove the leading `;` from these lines:

```ini
extension=intl
extension=zip
```

`zip` lets Composer unpack packages quickly. `intl` is used by Laravel's number and currency helpers.
Run `php -m` to confirm they are loaded.

## 3. Start MySQL

Start **MySQL** from the XAMPP Control Panel. The XAMPP default login is user `root` with an empty password.

Create the database:

```bash
mysql -u root -e "CREATE DATABASE inventory_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

## 4. Install the project

From the project folder:

```bash
composer install
npm install
cp .env.example .env      # skip if .env already exists
php artisan key:generate
php artisan migrate
npm run build
```

## 5. Database settings in `.env`

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_management
DB_USERNAME=root
DB_PASSWORD=
```

After changing `.env`, run `php artisan config:clear`.

## 6. Run the application

Use two terminals:

```bash
php artisan serve     # http://127.0.0.1:8000
npm run dev           # Vite: reloads CSS/JS while you edit
```

If you only run `php artisan serve`, run `npm run build` first so the compiled assets exist in `public/build`.

## Useful checks

```bash
php artisan migrate:status     # which migrations have run
php artisan route:list         # all registered routes
php artisan test               # run the test suite
./vendor/bin/pint --test       # code style check (PSR-12 based)
```

## Troubleshooting

| Problem | Fix |
|---------|-----|
| `php: command not found` | PATH is missing `C:\xampp\php`, or the terminal was opened before PATH was changed. |
| `SQLSTATE[HY000] [2002]` connection refused | MySQL is not running. Start it in XAMPP. |
| `Unknown database 'inventory_management'` | Run the `CREATE DATABASE` command in step 3. |
| `php artisan db:show` fails with `performance_schema.session_status` | Known MariaDB 10.4 limitation. The connection is fine; use `php artisan migrate:status` instead. |
| Composer `curl error 28` timeouts | Slow network or IPv6 DNS. Retry with `COMPOSER_IPRESOLVE=4 composer install`. |
| `Vite manifest not found` | Run `npm run build` or keep `npm run dev` running. |
| `composer run dev` fails on `pail` | `laravel/pail` needs the `pcntl` extension, which Windows lacks. Run `php artisan serve` and `npm run dev` separately. |
