# Learning Guide

What was configured, why, and where to find it. Read this alongside the code.

## 1. Environment configuration: `.env`

**What:** settings that change between machines (database login, app URL, debug mode).
**Why:** secrets and local settings stay out of the code. `.env` is in `.gitignore`. `.env.example` is the
template you commit.
**Where:** `.env`, read by the files in `config/`, for example `config/database.php` reads `DB_HOST`.

Changed from the defaults:

- `APP_NAME="Inventory Management"`: shown in the page title and navbar via `config('app.name')`.
- `APP_URL=http://127.0.0.1:8000`: matches `php artisan serve`.
- `DB_CONNECTION=mysql` and `DB_DATABASE=inventory_management`: Laravel 12 defaults to SQLite.

## 2. Migrations: version control for the database

**What:** PHP files that create or change tables.
**Why:** everyone gets the same schema by running `php artisan migrate`. Nobody has to share SQL dumps.
**Where:** `database/migrations/`. Example: `..._create_products_table.php`.

Things to notice in the products migration:

- `foreignId('category_id')->constrained()` creates the column **and** the foreign key to `categories.id`.
- `->restrictOnDelete()` makes MySQL refuse to delete a category that still has products.
- `->unique()` on `sku` stops duplicate product codes at the database level.

## 3. Models and Eloquent

**What:** one class per table. You work with PHP objects instead of SQL.
**Where:** `app/Models/`.

```php
$category->products;              // hasMany: all products in a category
$product->category->name;         // belongsTo: the product's category
$product->stockTransactions;      // movement history
Product::lowStock()->count();     // local scope defined in Product.php
```

- `$fillable` lists the fields that may be mass-assigned from a form. `quantity` is left out on purpose,
  so stock can only change through a stock transaction.
- `casts()` converts values when they are read. For example, `StockTransaction::type` becomes a
  `StockTransactionType` enum instead of a plain string.

## 4. Enums

**What:** `app/Enums/StockTransactionType.php` lists the allowed transaction types.
**Why:** `StockTransactionType::StockIn` cannot be mistyped, unlike the string `'in'`. Later it can feed both
validation (`Rule::enum(...)`) and dropdown labels (`->label()`).

## 5. Routes and controllers

**Where:** `routes/web.php` and `app/Http/Controllers/DashboardController.php`.

```php
Route::get('/', DashboardController::class)->name('dashboard');
```

- An **invokable** controller has a single `__invoke` method. It suits single-page actions like a dashboard.
- `->name('dashboard')` lets views link with `route('dashboard')`, so URLs can change without breaking links.
- The controller only gathers data and returns a view. Queries live in models (`lowStock()`), not in the view.

## 6. Blade layouts

**Where:** `resources/views/layouts/app.blade.php`.

- `@vite([...])` adds the compiled CSS and JS tags.
- `@include('partials.navbar')` reuses the navbar on every page.
- `@yield('content')` is filled by each page with `@section('content') ... @endsection`.
- `partials/flash.blade.php` shows `session('success')` messages and validation errors in one place, so
  individual pages don't repeat that code.

## 7. Frontend: Bootstrap with Vite

**Where:** `package.json`, `vite.config.js`, `resources/css/app.css`, `resources/js/app.js`.

- `app.css` imports Bootstrap's CSS. Add your own styles below the import.
- `app.js` imports Bootstrap's JavaScript, which powers the mobile navbar toggle and dismissible alerts.
  It is also exposed as `window.bootstrap` for use in inline scripts.
- `npm run dev` serves assets with live reload. `npm run build` writes minified files to `public/build/`,
  which is git-ignored and rebuilt on each machine.

## 8. Tests and code style

- `tests/Feature/DashboardTest.php` loads the dashboard and checks the counts, including the low-stock rule.
- Tests use an in-memory SQLite database (`phpunit.xml`), so they never touch your MySQL data.
  `RefreshDatabase` runs the migrations before each test.
- Run `php artisan test` after each change and `./vendor/bin/pint` before committing.

## 9. Machine changes made outside the project

- `C:\xampp\php\php.ini`: enabled `extension=intl` and `extension=zip`. A backup was saved as
  `php.ini.bak-before-laravel`.
- User `PATH`: added `C:\xampp\php` and `C:\xampp\mysql\bin`.

## Next step

Build authentication next, then categories → products → stock transactions → low-stock page.
`PROJECT_ARCHITECTURE.md` lists where each module goes.
