# Database Design

Database: `inventory_management` (utf8mb4). All tables are created by migrations in `database/migrations`.

## Relationships

```
categories 1 ──── * products 1 ──── * stock_transactions * ──── 1 users
```

- A **category** has many products. A product belongs to one category.
- A **product** has many stock transactions.
- A **stock transaction** belongs to one product and, optionally, to the user who recorded it.

## Tables

### categories

| Column | Type | Notes |
|--------|------|-------|
| id | bigint, PK | |
| name | varchar(255) | unique |
| description | text | nullable |
| created_at / updated_at | timestamp | |

### products

| Column | Type | Notes |
|--------|------|-------|
| id | bigint, PK | |
| category_id | bigint, FK → categories.id | `RESTRICT`: a category that still has products cannot be deleted |
| name | varchar(255) | |
| sku | varchar(64) | unique stock-keeping code, e.g. `PEN-01` |
| description | text | nullable |
| unit_price | decimal(10,2) | default 0 |
| quantity | unsigned int | current stock, default 0. Changed only by stock transactions |
| reorder_level | unsigned int | product counts as low stock when `quantity <= reorder_level` |
| created_at / updated_at | timestamp | |

### stock_transactions

Every stock movement is recorded here. This is the inventory history.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint, PK | |
| product_id | bigint, FK → products.id | `RESTRICT`: history is kept, so products with transactions cannot be deleted |
| user_id | bigint, FK → users.id | nullable, `SET NULL` if the user is deleted |
| type | varchar(20) | `in`, `out` or `adjustment` (see `App\Enums\StockTransactionType`) |
| quantity | int | signed change: `+10` for stock in, `-3` for stock out |
| balance_after | unsigned int | product quantity after this movement, for easy auditing |
| note | varchar(255) | nullable, e.g. supplier invoice or reason |
| created_at / updated_at | timestamp | |

Index on `(product_id, created_at)` speeds up "history for one product, newest first".

### Low stock alerts

There is no separate table. Low stock is calculated from `products`:

```php
Product::lowStock()->get();   // WHERE quantity <= reorder_level
```

### Laravel default tables

| Table | Used for |
|-------|----------|
| users, password_reset_tokens, sessions | login and sessions (`SESSION_DRIVER=database`) |
| cache, cache_locks | cache (`CACHE_STORE=database`) |
| jobs, job_batches, failed_jobs | queued jobs (`QUEUE_CONNECTION=database`) |
| migrations | records which migrations have run |

## Commands

```bash
php artisan migrate            # create missing tables
php artisan migrate:status     # see what has run
php artisan migrate:fresh      # drop everything and rebuild (deletes all data)
```
