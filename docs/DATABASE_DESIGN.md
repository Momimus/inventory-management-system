# Database Design

Database: `inventory_management`, utf8mb4, InnoDB. Every table is created by a migration in
`database/migrations`. Later changes are made by **new** migrations; migrations that have already been
published are never edited.

## Relationships

```
                 ┌──────────────┐
                 │  categories  │
                 └──────┬───────┘
                        │ 1
                        │ *
┌───────────┐ 1   * ┌───┴──────┐ 1   * ┌────────────────────┐ *   1 ┌─────────┐
│ suppliers ├───────┤ products ├───────┤ stock_transactions ├───────┤  users  │
└─────┬─────┘       └──────────┘       └─────────┬──────────┘       └────┬────┘
      │ 1                                        │ *                     │ 1
      └──────────────────────────────────────────┘                       │ *
              (supplier of a stock-in delivery)                  ┌───────┴───────┐
                                                                 │ activity_logs │──► subject (any model)
                                                                 └───────────────┘
```

## Tables

### users

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name, email | varchar | email unique |
| password | varchar | bcrypt hash (`hashed` cast) |
| role | varchar(20), indexed | `admin` or `staff` (`App\Enums\UserRole`) |
| is_active | boolean | inactive users cannot sign in |
| last_login_at | timestamp, nullable | set on every sign-in |
| remember_token, email_verified_at, timestamps | | Laravel defaults |

### categories

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | varchar unique | |
| description | text, nullable | |

### suppliers

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | varchar(150) unique | |
| contact_person | varchar(150), nullable | |
| email | varchar, nullable, indexed | |
| phone | varchar(30), nullable | |
| address | text, nullable | |
| is_active | boolean, indexed | inactive suppliers can't be chosen for new links |

### products

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| category_id | FK → categories, **RESTRICT** | a category with products can't be deleted |
| supplier_id | FK → suppliers, nullable, **RESTRICT** | primary supplier |
| name | varchar, indexed | |
| sku | varchar(64) **unique** | stored in upper case |
| description | text, nullable | |
| purchase_price, selling_price | **decimal(10,2)** | exact money values (never float) |
| quantity | **unsigned int** | current stock; the database rejects negatives |
| reorder_level | unsigned int | low stock when `quantity <= reorder_level` |
| image_path | varchar, nullable | `products/<40 random chars>.webp` on the public disk |
| is_active | boolean, indexed | inactive products are kept but can't move stock |

### stock_transactions

One row per movement. **Rows are never updated or deleted.**

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| product_id | FK → products, **RESTRICT** | products with history can't be deleted |
| user_id | FK → users, nullable, SET NULL | who recorded it |
| supplier_id | FK → suppliers, nullable, RESTRICT | for stock-in deliveries |
| type | varchar(20) | `in`, `out`, `adjustment` (`StockTransactionType`) |
| quantity | signed int | the change: `+25` in, `-4` out, `±n` adjustment |
| balance_after | unsigned int | product quantity after this movement |
| reference | varchar(100), nullable, indexed | invoice, delivery note or order number |
| note | varchar, nullable | required for adjustments (the reason) |
| created_at, updated_at | timestamp | |

Indexes: `(product_id, created_at)` for product history, `(type, created_at)` for movement reports.

Checks:
- For each product, `SUM(quantity)` over its transactions equals `products.quantity`.
- Each `balance_after` equals the previous balance plus `quantity`.

`StockServiceTest::test_balances_form_an_unbroken_chain` and `DemoDataSeederTest` verify both.

### activity_logs

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| user_id | FK → users, nullable, SET NULL | who did it |
| action | varchar(60), indexed | e.g. `product.updated`, `stock.out`, `auth.failed` |
| subject_type, subject_id | nullable morph, indexed | the record concerned. The type is a short alias (`product`, `stock_transaction`) from the morph map |
| description | varchar | human-readable summary |
| properties | json, nullable | old/new values, filters, etc. Secrets are removed before saving |
| ip_address | varchar(45) | IPv4 or IPv6 |
| created_at | timestamp, indexed | no `updated_at`: entries never change |

### Laravel tables

`password_reset_tokens`, `sessions` (database session driver), `cache`, `cache_locks`, `jobs`,
`job_batches`, `failed_jobs`, `migrations`.

## Deletion rules at a glance

| Deleting… | Allowed when | Otherwise |
|---|---|---|
| Category | It has no products, or its products are moved to another category in the same request | Refused by validation; the FK is the final guard |
| Supplier | No products or transactions reference it | Deactivate it |
| Product | It has no stock transactions (admins only) | Deactivate it |
| User | Never (no route) | Deactivate the account |
| Stock transaction, activity log | Never | Record a correcting adjustment |

## Migration history

| Migration | Change |
|---|---|
| `0001_01_01_*` | Laravel defaults: users, cache, jobs |
| `2026_11_20_*` | categories, products (with `unit_price`), stock_transactions |
| `2026_11_21_000001` | users: role, is_active, last_login_at |
| `2026_11_21_000002` | activity_logs |
| `2026_11_21_000003` | suppliers; supplier_id on products and stock_transactions |
| `2026_11_21_000004` | products: `unit_price` renamed to `selling_price` (data kept), purchase_price, image_path, is_active |
| `2026_11_21_000005` | stock_transactions: reference, `(type, created_at)` index |

```bash
php artisan migrate            # apply new migrations
php artisan migrate:status     # what has run
php artisan migrate:fresh      # DELETES ALL DATA and rebuilds. Development only
```
