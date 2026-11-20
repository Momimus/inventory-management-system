# Inventory Management System

A web application for running a small business's stock room. It tracks products, categories and
suppliers, records stock in, stock out and adjustments, warns about low stock and produces reports.
Built with Laravel 12, MySQL/MariaDB and Bootstrap 5.

## Features

- **Sign-in and roles.** Administrator and Staff roles, login rate limiting, password reset by email,
  and deactivation instead of deletion.
- **Catalogue.** Products (unique SKU, purchase and selling price, reorder level, image, active flag),
  categories (with safe deletion and product reassignment) and suppliers.
- **Stock movements.** Stock In, Stock Out and admin-only Adjustments. The product row is locked during
  each movement, negative stock is refused, and every change is kept in a permanent history.
- **Low-stock alerts.** Calculated live from quantities, shown on the dashboard, in a sidebar badge and
  on a dedicated page.
- **Dashboard.** Totals, stock value, units by category, the last 7 days of movements, recent
  transactions and recent activity.
- **Reports.** Current inventory, stock in, stock out, adjustments, low stock and product history,
  with date, category, supplier and product filters and CSV export.
- **Activity log.** A permanent record of sign-ins, catalogue changes, stock movements, user management
  and exports.

## Quick start (Windows + XAMPP)

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# create the database "inventory_management" (utf8mb4) in MySQL first
php artisan migrate
php artisan storage:link
php artisan db:seed           # optional demo data
npm run build
php artisan serve             # http://127.0.0.1:8000
```

Demo accounts (local only): `admin@example.com` / `staff@example.com`, password `Password123`.
On a real installation, create the first account with `php artisan users:create-admin`.

Full instructions are in [docs/ENVIRONMENT_SETUP.md](docs/ENVIRONMENT_SETUP.md).

## Checks

```bash
php artisan test            # 170 tests
./vendor/bin/pint --test    # code style (PSR-12 based)
npm run build               # front-end assets
```

## Documentation

| Document | Contents |
|---|---|
| [ENVIRONMENT_SETUP](docs/ENVIRONMENT_SETUP.md) | Installing and configuring the development environment |
| [TECH_STACK](docs/TECH_STACK.md) | Technologies used and why |
| [PROJECT_ARCHITECTURE](docs/PROJECT_ARCHITECTURE.md) | Folder structure, request flow, modules and permissions |
| [DATABASE_DESIGN](docs/DATABASE_DESIGN.md) | Tables, relationships, indexes and deletion rules |
| [SECURITY](docs/SECURITY.md) | Each protection, where it is implemented and which test proves it |
| [TESTING](docs/TESTING.md) | What is tested and how to run the tests |
| [DEPLOYMENT](docs/DEPLOYMENT.md) | Production setup, hardening, backups and updates |
| [LEARNING_GUIDE](docs/LEARNING_GUIDE.md) | The Laravel concepts used, explained with this project's code |

## License

MIT. See [LICENSE](LICENSE).
