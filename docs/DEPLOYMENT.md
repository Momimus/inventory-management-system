# Deployment

A checklist for running the application on a Linux server with Nginx, PHP-FPM and MySQL/MariaDB.
Apache works the same way: point the document root at `public/`.

## 1. Server requirements

- PHP 8.2+ with `bcmath`, `ctype`, `curl`, `fileinfo`, `gd` (with WebP), `intl`, `mbstring`,
  `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`
- MySQL 5.7+/8 or MariaDB 10.3+ (InnoDB)
- Composer 2, and Node.js 20.19+ (only to build assets; Node isn't needed at runtime)
- A TLS certificate (for example from Let's Encrypt)

## 2. Database with least privilege

The application needs data access only. Don't use `root`.

```sql
CREATE DATABASE inventory_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Used by the running application
CREATE USER 'inventory_app'@'localhost' IDENTIFIED BY 'a-long-random-password';
GRANT SELECT, INSERT, UPDATE, DELETE ON inventory_management.* TO 'inventory_app'@'localhost';

-- Used only when running migrations during a deployment
CREATE USER 'inventory_migrate'@'localhost' IDENTIFIED BY 'another-long-random-password';
GRANT ALL PRIVILEGES ON inventory_management.* TO 'inventory_migrate'@'localhost';
```

Run migrations with the migration account, for example:
`DB_USERNAME=inventory_migrate DB_PASSWORD=... php artisan migrate --force`.
Keep MySQL bound to `127.0.0.1` unless another host really needs access.

## 3. Get the code and build

```bash
git clone https://github.com/Momimus/inventory-management-system.git /var/www/inventory
cd /var/www/inventory
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env
php artisan key:generate
```

## 4. Production `.env`

```dotenv
APP_NAME="Inventory Management"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://inventory.example.com
APP_TRUSTED_HOSTS=inventory.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=inventory_management
DB_USERNAME=inventory_app
DB_PASSWORD=a-long-random-password

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_LIFETIME=120

LOG_CHANNEL=daily
LOG_LEVEL=warning

MAIL_MAILER=smtp           # real SMTP settings, so password-reset emails arrive
MAIL_HOST=...
MAIL_FROM_ADDRESS=inventory@example.com
```

What these settings do:

| Setting | Effect |
|---|---|
| `APP_DEBUG=false` | Visitors see generic error pages; details go only to the log |
| `APP_ENV=production` | Enables HSTS, forced HTTPS links and breached-password checks. Turns off development-only strictness. The demo seeder refuses to run |
| `APP_TRUSTED_HOSTS` | Requests with any other `Host` header are rejected |
| `SESSION_SECURE_COOKIE` | The session cookie is only sent over HTTPS |

Protect the file with `chmod 640 .env` (owner: deploy user, group: web server). Never commit it.

## 5. Finish the installation

```bash
php artisan migrate --force          # with the migration DB account
php artisan storage:link
php artisan users:create-admin       # first administrator
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

File permissions: the web server user needs write access to `storage/` and `bootstrap/cache/` only.

```bash
chown -R deploy:www-data /var/www/inventory
chmod -R 775 storage bootstrap/cache
```

## 6. Nginx

```nginx
server {
    listen 80;
    server_name inventory.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name inventory.example.com;
    root /var/www/inventory/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/inventory.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/inventory.example.com/privkey.pem;

    client_max_body_size 4m;           # image uploads are limited to 2 MB by the app

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Never execute scripts from the uploads directory.
    location ^~ /storage/ {
        location ~ \.php$ { return 403; }
        try_files $uri =404;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known) { deny all; }
}
```

Also set `expose_php = Off` in `php.ini`.

## 7. Backups and recovery

Back up both the **database** and the **uploaded images** (`storage/app/public`).

```bash
# Daily at 02:00 (crontab of the deploy user)
0 2 * * * mysqldump --single-transaction --routines -u backup_user -p'...' inventory_management \
          | gzip > /backups/inventory-$(date +\%F).sql.gz
15 2 * * * tar -czf /backups/inventory-images-$(date +\%F).tar.gz -C /var/www/inventory/storage/app public
30 3 * * * find /backups -name 'inventory-*' -mtime +30 -delete
```

- Give `backup_user` only `SELECT, LOCK TABLES, SHOW VIEW, TRIGGER` on the database.
- `--single-transaction` takes a consistent snapshot without locking the shop.
- Copy backups to a different machine or cloud storage.

**Restore:**

```bash
php artisan down
gunzip < /backups/inventory-2026-11-20.sql.gz | mysql -u inventory_migrate -p inventory_management
tar -xzf /backups/inventory-images-2026-11-20.tar.gz -C /var/www/inventory/storage/app
php artisan up
```

Test a restore on a spare database regularly: an untested backup is not a backup.

## 8. Updating

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

Before each release, run `composer audit` and `npm audit`, and run the test suite against MySQL
([TESTING.md](TESTING.md)).

## 9. Monitoring

- Logs: `storage/logs/laravel-YYYY-MM-DD.log` (`LOG_CHANNEL=daily`). Forward them to your log service,
  or check them for `ERROR` lines.
- Health check: `GET /up` returns 200 when the application boots. Point an uptime monitor at it.
- Security events (sign-ins, failed attempts, lockouts, exports, user changes) are in the in-app
  **Activity log** (admins only).
