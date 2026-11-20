# Testing

## Running the checks

```bash
php artisan test                         # all tests (fast, in-memory SQLite)
php artisan test --filter=StockService   # one class or test name
./vendor/bin/pint --test                 # code style; drop --test to fix files
npm run build                            # front-end assets compile
```

Run all three before every commit.

### Against MySQL/MariaDB

The default run uses in-memory SQLite (`phpunit.xml`), which is fast and never touches your data.
SQLite ignores row locks, though, and has some type differences, so before a release also run the
suite against a **separate, empty** MySQL database:

```bash
mysql -u root -e "CREATE DATABASE inventory_management_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
DB_CONNECTION=mysql DB_DATABASE=inventory_management_testing php artisan test
```

`RefreshDatabase` drops and rebuilds that database, so never point it at real data.
On 2026-11-20 all tests passed on both SQLite and MariaDB 10.4.32.

## How the tests are organised

- **Feature tests** (`tests/Feature`) send real HTTP requests through routes, middleware, validation,
  policies and views, then check the response and the database.
- **Unit tests** (`tests/Unit`) check small pieces of pure logic without a database.

`RefreshDatabase` gives each test an empty, freshly migrated database. Factories in `database/factories`
create test records (`Product::factory()->lowStock()->create()`).

| File | Tests | What it covers |
|---|---:|---|
| `Auth/AuthenticationTest` | 12 | Login success and failure, inactive users, **rate limiting**, **session regeneration**, logout invalidation, POST-only logout, deactivated users signed out, failed attempts logged without passwords |
| `Auth/PasswordResetTest` | 5 | Reset link sent, no account enumeration, valid and invalid tokens, weak password rejected |
| `ProfileTest` | 7 | Profile update, **no role escalation through the profile**, unique email, password change rules, no password in logs |
| `UserManagementTest` | 10 | Admin CRUD, validation, admins can't demote or deactivate themselves, **staff blocked from every user route including IDOR attempts** |
| `CategoryManagementTest` | 10 | CRUD, unique names, deletion only with reassignment, FK guard, staff restrictions, **XSS escaping** |
| `SupplierManagementTest` | 9 | CRUD, email/phone validation, filters, deletion rules, staff restrictions, escaped address |
| `ProductManagementTest` | 17 | CRUD, **quantity can't be set from forms**, **duplicate SKU rejected**, invalid input, inactive suppliers, filters, **whitelisted sorting**, **SQL-injection-style search**, **unauthorized deletion**, deletion blocked by history, escaping, no lazy loading |
| `ProductImageUploadTest` | 8 | WebP re-encoding and random names, **PHP files and disguised PHP rejected**, SVG rejected, size and dimension limits, old files removed |
| `Inventory/StockServiceTest` | 13 | **Stock in, out, adjustment**, **negative stock prevented**, **rollback when saving history fails**, **stale-read safety**, unbroken balance chain, product edits don't overwrite stock, **immutable history** |
| `Inventory/StockTransactionHttpTest` | 14 | Forms, quantity validation, inactive products, **user can't be spoofed**, staff can't adjust, reason required, unknown types 404, **no edit/delete routes**, history filters |
| `LowStockTest` | 5 | Detection at or below reorder level, urgency order, filters, alert cleared after restock, sidebar badge |
| `DashboardTest` | 6 | **Every figure checked against known data**, daily movements, **cache cleared after changes**, admin-only activity |
| `ReportTest` | 11 | **Totals and calculations**, **date ranges**, **staff blocked from every report and export**, CSV contents, **formula injection neutralised**, invalid filters |
| `ActivityLogTest` | 6 | Logged fields, filters, subject links, admin-only, escaping, entries can't be deleted |
| `Security/HttpSecurityTest` | 4 | Security headers, HttpOnly/SameSite cookie, **CSRF rejection and acceptance** |
| `Security/ProductionHardeningTest` | 12 | No error details with debug off, custom error pages, HSTS, **exact trusted hosts**, no CORS, strict attributes, **every route requires login** |
| `CreateAdminUserCommandTest` | 2 | Admin command creates an admin and rejects weak passwords |
| `DemoDataSeederTest` | 3 | Seeder is consistent, idempotent, and refuses production |
| `Unit/CsvExportTest` | 3 (+data sets) | Formula prefixes, numbers stay numeric |
| `Unit/ProductStockLevelTest` | 1 (+7 data sets) | In stock / low / out boundaries |

Total: **170 test cases** (data providers count each data set).

## Testing techniques worth knowing

- **Enabling CSRF in a test.** Laravel switches CSRF checks off during tests. `HttpSecurityTest` binds
  a subclass of `ValidateCsrfToken` that turns them back on, to prove a token-less POST is rejected.
- **Simulating a failure.** `StockServiceTest` registers a `StockTransaction::creating` listener that
  throws. The test then checks that the quantity update rolled back as well.
- **Stale models.** Two copies of the same product are loaded and one is changed. This proves
  `StockService` re-reads the locked row instead of trusting the copy it was given.
- **Time travel.** `$this->travelTo(now()->subDays(10))` creates records in the past for date-range tests.
- **Streamed CSV.** `$response->streamedContent()` returns the downloaded file's contents.
- **N+1 detection.** `Model::preventLazyLoading()` is on in tests, so a missing `with()` makes a page
  test fail instead of silently running extra queries.

## Concurrency check (manual, MariaDB)

Row locking can't be shown on SQLite. The experiment in [SECURITY.md](SECURITY.md#inventory-integrity)
ran 8 PHP processes at once against MariaDB:
- **With `StockService`:** the result was exactly correct.
- **With a naive unlocked version:** 80 units were "sold" from 50 and the final quantity was wrong.

Repeat this experiment after changing `StockService`.
