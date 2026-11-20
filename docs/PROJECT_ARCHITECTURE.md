# Project Architecture

The application follows Laravel's MVC structure, with a few extra layers where they earn their place:
**Form Requests** for validation, **Policies and Gates** for permissions, a **service** for stock
changes, and **report classes** for queries shared by HTML and CSV.

## Request flow

Example: a staff member records a stock-out.

```
POST /stock/out
  │
  ├─ public/index.php → bootstrap/app.php                    boot Laravel
  ├─ web middleware                                          session, CSRF check, security headers,
  │                                                          EnsureUserIsActive
  ├─ routes/web.php                                          auth + auth.session middleware;
  │                                                          {type} bound to StockTransactionType::StockOut
  ├─ StockTransactionRequest                                 authorize(): StockTransactionPolicy::create
  │                                                          rules(): product active, quantity 1–1,000,000
  ├─ StockTransactionController::store()                     picks the right StockService method
  ├─ StockService::stockOut()                                DB transaction → lock product row →
  │                                                          check stock → update quantity →
  │                                                          create StockTransaction → ActivityLogger
  ├─ after commit                                            dashboard cache cleared
  └─ redirect to products/{id} with a success message
```

When something fails, the user is sent back to the form with an error message:
- **Validation failed:** Laravel returns the user to the form with the errors.
- **Not allowed:** the policy refuses, and the user gets a 403 page.
- **Not enough stock:** `StockOperationException` is caught and shown under the quantity field.

## Folder map

```
app/
  Console/Commands/CreateAdminUser.php    php artisan users:create-admin
  Enums/                                  UserRole, StockTransactionType, StockLevel (+ HasOptions for selects)
  Exceptions/StockOperationException.php  business-rule refusals with user-facing messages
  Http/
    Controllers/                          one controller per area; Auth/ for sign-in and password reset
    Middleware/                           SetSecurityHeaders, EnsureUserIsActive
    Requests/                             one Form Request per form (validation + authorization)
  Listeners/RecordAuthenticationActivity  logs sign-in, sign-out, failures, lockouts, resets
  Models/                                 Eloquent models
    Concerns/                             Searchable, IsAppendOnly, FlushesInventoryStatistics
  Policies/                               User, Category, Product, Supplier, StockTransaction
  Providers/AppServiceProvider.php        gates, password rules, morph map, strict-model settings
  Reports/                                Report base class + 4 reports (query + CSV columns)
  Services/
    StockService.php                      the only code that changes product quantities
    ActivityLogger.php                    writes activity_logs with secrets removed
    InventoryStatistics.php               cached dashboard figures
    ProductImageStorage.php               validates, re-encodes and stores images
  Support/CsvExport.php                   streamed CSV with formula-injection protection
config/inventory.php                      currency, image limits, dashboard cache time
database/
  migrations/                             schema (see DATABASE_DESIGN.md)
  factories/                              test data builders
  seeders/                                DemoDataSeeder (refuses to run in production)
resources/
  css/app.css, js/app.js                  Bootstrap + small custom styles; confirm dialog, loading buttons
  views/
    components/                           layouts/app, layouts/guest, form/*, stat-card, filter-bar, ...
    partials/sidebar-nav.blade.php        navigation, filtered with @can
    errors/                               self-contained error pages
    <area>/                               index, create, edit, show, _form per module
routes/web.php                            every route, grouped by middleware
tests/Feature, tests/Unit                 see TESTING.md
```

## Modules

| Module | Routes | Controller | Key classes |
|---|---|---|---|
| Authentication | `login`, `logout`, `forgot-password`, `reset-password` | `Auth\LoginController`, `Auth\PasswordResetLinkController`, `Auth\NewPasswordController` | `LoginRequest` (rate limit), `RecordAuthenticationActivity` |
| Profile | `profile` | `ProfileController` | `ProfileUpdateRequest`, `PasswordUpdateRequest` |
| Users | `users` | `UserController` | `UserPolicy`, `StoreUserRequest`, `UpdateUserRequest` |
| Dashboard | `/` | `DashboardController` (invokable) | `InventoryStatistics` |
| Categories | `categories` (resource) | `CategoryController` | `CategoryPolicy`, `CategoryRequest`, `DeleteCategoryRequest` |
| Suppliers | `suppliers` (resource) | `SupplierController` | `SupplierPolicy`, `SupplierRequest` |
| Products | `products` (resource) | `ProductController` | `ProductPolicy`, `ProductRequest`, `ProductImageStorage` |
| Stock | `stock`, `stock/{type}/create`, `stock/{type}`, `stock/transactions/{id}` | `StockTransactionController` | `StockService`, `StockTransactionPolicy`, `StockTransactionRequest` |
| Low stock | `low-stock` | `LowStockController` (invokable) | `Product::lowStock()` scope |
| Reports | `reports/*` | `ReportController` | `app/Reports/*`, `ReportRequest`, `CsvExport` |
| Activity log | `activity` | `ActivityLogController` | `ActivityLog`, `ActivityLogger` |

## Roles and permissions

| Action | Admin | Staff | Enforced by |
|---|:---:|:---:|---|
| View dashboard, products, categories, suppliers, transactions, low stock | ✓ | ✓ | `auth` middleware, `viewAny`/`view` policies |
| Create and edit products | ✓ | ✓ | `ProductPolicy` |
| Delete products | ✓ | ✗ | `ProductPolicy::delete` |
| Create, edit and delete categories and suppliers | ✓ | ✗ | `CategoryPolicy`, `SupplierPolicy` |
| Stock in, stock out | ✓ | ✓ | `StockTransactionPolicy::create` |
| Stock adjustments | ✓ | ✗ | `adjust-stock` gate |
| Edit or delete stock transactions | ✗ | ✗ | `StockTransactionPolicy` returns `false`, model throws, no routes |
| Reports and CSV export | ✓ | ✗ | `view-reports` gate (route + request) |
| Users, activity log | ✓ | ✗ | `UserPolicy`, `view-activity-log` gate |

To change a rule, edit the policy method or gate. The sidebar (`@can`) and the server follow automatically.

## Design decisions

- **Stock changes go through one service.** Quantities only change in `StockService`, so locking,
  validation and history can't be skipped by accident. `quantity` is not in `Product::$fillable`.
- **Stock history is append-only.** Mistakes are corrected with a new adjustment. That keeps an
  honest audit trail, and `balance_after` always forms an unbroken chain.
- **Alerts are calculated, not stored.** Low stock is a query (`quantity <= reorder_level`), so it is
  never stale and never duplicated.
- **Users are deactivated, not deleted.** History keeps pointing at real people, and a deactivated
  user is signed out on their next request.
- **Roles are an enum plus gates and policies.** Roles/permissions tables would be unnecessary for two
  fixed roles. Moving to tables later would only change the gate and policy bodies.
- **One query per report.** Each report class's `query()` feeds both the HTML table and the CSV export,
  so the two can't disagree.
- **Dashboard cache cleared after commit.** Figures are cached, and model events clear the cache only
  after the database transaction commits.
