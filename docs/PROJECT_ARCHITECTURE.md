# Project Architecture

The project follows Laravel's MVC pattern:

- **Model**: a PHP class that represents a database table (`app/Models`).
- **View**: a Blade template that renders HTML (`resources/views`).
- **Controller**: receives the request, asks models for data, and returns a view (`app/Http/Controllers`).

## Request flow

```
Browser  GET /
   │
   ▼
public/index.php            every request enters here
   │
   ▼
bootstrap/app.php           boots Laravel, loads middleware and routes
   │
   ▼
routes/web.php              matches "/" → DashboardController
   │
   ▼
DashboardController         asks the Product and Category models for counts
   │
   ▼
Product / Category models   Eloquent builds SQL and queries MySQL
   │
   ▼
resources/views/dashboard.blade.php   extends layouts/app.blade.php
   │
   ▼
HTML response back to the browser
```

## Folder map

```
app/
  Enums/StockTransactionType.php      in / out / adjustment
  Http/Controllers/
    Controller.php                    base controller
    DashboardController.php           dashboard counts
  Models/
    User.php
    Category.php
    Product.php                       includes the lowStock() scope
    StockTransaction.php
database/migrations/                  table definitions, run with php artisan migrate
resources/
  css/app.css                         imports Bootstrap CSS
  js/app.js                           imports Bootstrap JS
  views/
    layouts/app.blade.php             page shell: <head>, navbar, flash messages
    partials/navbar.blade.php
    partials/flash.blade.php          success message and validation errors
    dashboard.blade.php
routes/web.php                        all browser routes
tests/Feature/DashboardTest.php
docs/                                 this documentation
```

## Planned modules

Only the dashboard is built. The other modules will follow this layout, so every developer knows where to put new code:

| Module | Controller | Views folder | Model(s) |
|--------|------------|--------------|----------|
| Authentication | `Auth\LoginController` (+ register/logout as needed) | `views/auth` | `User` |
| Dashboard | `DashboardController` (done) | `views/dashboard.blade.php` | `Product`, `Category` |
| Categories | `CategoryController` (resource) | `views/categories` | `Category` |
| Products | `ProductController` (resource) | `views/products` | `Product` |
| Stock management | `StockTransactionController` (`create`, `store`) | `views/stock` | `StockTransaction`, `Product` |
| Inventory transactions | `StockTransactionController@index` | `views/stock` | `StockTransaction` |
| Low stock alerts | `LowStockController` (invokable) | `views/low-stock.blade.php` | `Product::lowStock()` |

Conventions to keep:

- **Resource controllers** (`php artisan make:controller ProductController --resource --model=Product`) and
  `Route::resource()` give standard route names such as `products.index` and `products.store`.
- **Form Requests** (`php artisan make:request StoreProductRequest`) hold validation rules, which keeps controllers small.
- **Stock changes go through one place.** A `StockService` class (or a method on `Product`) should create the
  `StockTransaction` row and update `products.quantity` inside a DB transaction. `quantity` is deliberately
  missing from `Product::$fillable`, so a product form cannot change stock directly.
- Once authentication exists, wrap all inventory routes in `Route::middleware('auth')->group(...)`.
- Every page extends `layouts.app` and sets `@section('title')` and `@section('content')`.
