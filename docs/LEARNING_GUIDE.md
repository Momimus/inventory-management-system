# Learning Guide

This guide walks through the Laravel concepts the project uses, in roughly the order a request meets
them. Each section points at real files, so you can read the explanation with the code open next to it.

---

## 1. Configuration: `.env` and `config/`

`.env` holds settings that differ per machine: database login, URL, debug mode. Files in `config/`
read them with `env()` and give every setting a default. The application code always calls
`config('...')`, never `env()` directly, because `php artisan config:cache` (used in production)
stops `.env` being read at runtime.

**In this project:**
- `config/inventory.php`: the app's own settings (currency, image limits, dashboard cache time).
- `config/app.php` → `trusted_hosts`: reads `APP_TRUSTED_HOSTS` as a comma-separated list.

## 2. Routes and middleware: `routes/web.php`, `bootstrap/app.php`

A route maps a URL and an HTTP method to a controller method. **Middleware** wraps routes and can stop
a request before it reaches the controller.

```php
Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::resource('products', ProductController::class);            // 7 standard routes
    Route::get('stock/{type}/create', [StockTransactionController::class, 'create']);
});
```

- `auth` redirects guests to `/login`. `auth.session` signs out other sessions after a password change.
- `Route::resource` creates `index`, `create`, `store`, `show`, `edit`, `update` and `destroy` with
  conventional names (`products.store` and so on).
- `{type}` is **bound to an enum**. Because the controller asks for `StockTransactionType $type`,
  Laravel converts `in`, `out` or `adjustment` automatically and returns 404 for anything else.
- `bootstrap/app.php` adds two global web middlewares written for this project:
  `SetSecurityHeaders` and `EnsureUserIsActive`.

Run `php artisan route:list` to see every route with its middleware.

## 3. Controllers: `app/Http/Controllers`

Controllers stay thin. Each method:
1. checks permission,
2. lets a Form Request validate input,
3. calls a model or service,
4. returns a view or a redirect.

Business rules do not live here.

- **Resource controllers** (`ProductController`) follow the seven resource actions.
- **Invokable controllers** (`DashboardController`, `LowStockController`) have a single `__invoke()`
  for single-purpose pages.
- **Dependency injection:** `public function __construct(private StockService $stock) {}`. Laravel's
  service container creates the service and passes it in, so you never write `new StockService(...)`.

## 4. Validation with Form Requests: `app/Http/Requests`

A Form Request class holds the rules for one form. Laravel runs it **before** the controller method.
If validation fails, the user is sent back with error messages and their previous input.

```php
// ProductRequest
'sku' => ['required', 'string', 'max:64', 'regex:/^[A-Z0-9][A-Z0-9._-]*$/',
          Rule::unique('products', 'sku')->ignore($this->product())],
'purchase_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
```

- `prepareForValidation()` tidies input first, for example upper-casing the SKU and trimming names.
- `authorize()` checks permission, so "may this user do this?" and "is this input valid?" sit together.
- The controller only uses `$request->validated()`. Fields not listed in the rules, such as a forged
  `quantity` or `user_id`, never reach the database.

Read: `ProductRequest`, `StockTransactionRequest` (rules change with the transaction type),
`DeleteCategoryRequest` (`requiredIf` the category still has products).

## 5. Authorization: policies and gates

**Policies** (`app/Policies`) answer questions about one kind of record. Laravel matches
`ProductPolicy` to `Product` by name.

```php
public function delete(User $user, Product $product): bool
{
    return $user->isAdmin();
}
```

You can call it in four places:
- **Controller:** `$this->authorize('delete', $product)`. A refusal returns a 403 page.
- **Form Request:** `$this->user()->can('update', $product)`.
- **Blade:** `@can('delete', $product) ... @endcan`, which hides the button.
- **Routes:** `->middleware('can:view-reports')`.

**Gates** (`AppServiceProvider::defineGates()`) are named abilities that aren't about one record:
`adjust-stock`, `view-reports`, `view-activity-log`.

The key rule: **hiding a button is not security.** Every route checks permission on the server.
`UserManagementTest::test_staff_cannot_access_any_user_management_route` sends requests straight to
admin URLs to prove it.

## 6. Eloquent models: `app/Models`

A model is a PHP class for one table.

- **Relationships:** `Product::category()` is `belongsTo`, `Category::products()` is `hasMany`, and
  `ActivityLog::subject()` is `morphTo` (it can point at any model).
- **`$fillable`:** the attributes that may be mass-assigned (`Product::create([...])`). `quantity` and
  `image_path` are left out on purpose.
- **Casts:** `'type' => StockTransactionType::class` turns the stored string into an enum.
  `'password' => 'hashed'` hashes automatically. `'selling_price' => 'decimal:2'` keeps money exact.
- **Local scopes** are reusable query pieces: `Product::active()->lowStock()->get()`.
- **Eager loading:** `Product::with(['category', 'supplier'])` loads relationships in two extra queries
  instead of one query per row (the "N+1 problem"). `Model::preventLazyLoading()` in
  `AppServiceProvider` makes a forgotten `with()` throw during development.

### Model traits: `app/Models/Concerns`

Traits share behaviour between models. Laravel automatically calls a static `boot<TraitName>()` method.

- `Searchable` adds `->search($term)` across the columns listed in `$searchable`.
- `IsAppendOnly` throws on update or delete. It's used by `StockTransaction` and `ActivityLog`.
- `FlushesInventoryStatistics` clears the dashboard cache after the change commits.

## 7. Enums: `app/Enums`

PHP 8.1 enums replace "magic strings" such as `'adjustment'`.

```php
enum StockTransactionType: string
{
    case StockIn = 'in';
    case StockOut = 'out';
    case Adjustment = 'adjustment';

    public function label(): string { /* "Stock In" ... */ }
}
```

The same enum provides the validation rule (`Rule::enum(...)`), the model cast, the route binding,
dropdown options (`::options()` from the `HasOptions` trait) and badge colours, so the values are
defined in one place.

## 8. Services and database transactions: `app/Services/StockService.php`

When an operation touches several tables and has rules that must never be skipped, it goes in a
service class.

```php
DB::transaction(function () {
    $locked = Product::whereKey($id)->lockForUpdate()->firstOrFail();   // SELECT ... FOR UPDATE
    // check, update quantity, create the transaction row, write the activity log
}, attempts: 3);
```

- **Transaction:** all of the writes succeed, or none do.
- **`lockForUpdate()`:** another request that wants the same product row waits until this one commits,
  so two simultaneous sales can't both see "10 in stock".
- **Calculate from the locked row:** the new quantity comes from the freshly locked value, never from
  the model the controller passed in, which may be out of date.

[SECURITY.md](SECURITY.md#inventory-integrity) shows what happens without these. The experiment there
"sold" 80 units from a stock of 50.

## 9. Blade components and layouts: `resources/views/components`

Blade components are reusable pieces of HTML with their own small set of inputs.

```blade
<x-layouts.app title="Products">
    <x-page-header title="Products">
        <a href="..." class="btn btn-primary">Add product</a>
    </x-page-header>

    <x-form.input name="sku" label="SKU" :value="$product->sku" required />
</x-layouts.app>
```

- `@props([...])` declares a component's inputs. Any other attributes (`required`, `class`) are passed on
  through `$attributes`.
- `:value="..."` passes a PHP expression. `value="..."` passes a plain string.
- The `form/input` component handles the label, old input, the error state and the error message, so no
  form repeats that markup.
- `{{ $text }}` **escapes** output, which prevents XSS. `{!! !!}` doesn't escape and is used only as
  `{!! nl2br(e($text)) !!}`, where `e()` has already escaped the text.

## 10. Authentication pieces

- `LoginRequest::authenticate()` uses `Auth::attempt()` with `is_active => true` in the credentials.
  `RateLimiter` counts failures per email and IP address.
- `$request->session()->regenerate()` after login prevents session fixation.
  `invalidate()` + `regenerateToken()` on logout destroy the old session completely.
- **Password reset** uses Laravel's `Password` broker. It creates single-use, expiring tokens in
  `password_reset_tokens` and sends the `ResetPassword` notification.
- **Events and listeners:** Laravel fires `Login`, `Logout`, `Failed`, `Lockout` and `PasswordReset`.
  `app/Listeners/RecordAuthenticationActivity.php` has a `handle*` method for each, and Laravel finds
  them automatically (event discovery).

## 11. Caching: `app/Services/InventoryStatistics.php`

```php
Cache::remember('inventory.statistics', 300, fn () => [/* expensive queries */]);
```

The first request runs the queries and stores the result; later requests read the stored copy.
Model events clear the cache after a change commits (`DB::afterCommit`). This avoids a race: without
it, another request could re-cache old figures just before the transaction finished. The cache store
is the `cache` database table (`CACHE_STORE=database`).

## 12. File uploads: `app/Services/ProductImageStorage.php`

Laravel's `Storage` facade writes to "disks" defined in `config/filesystems.php`. The `public` disk is
`storage/app/public`, which `php artisan storage:link` exposes at `/storage`. Uploaded images are:
1. validated,
2. decoded with GD,
3. resized,
4. re-encoded as WebP,
5. saved under a random name.

The original file is never stored.

## 13. Reports and streamed downloads: `app/Reports`, `app/Support/CsvExport.php`

Each report extends `Report` and defines `query()`, `headings()` and `row()`.
- **HTML page:** `$report->query()->paginate(25)`.
- **CSV file:** `$report->query()->lazy(500)` streams rows in chunks through
  `response()->streamDownload()`, so memory use stays flat however large the export.

## 14. Artisan commands, factories and seeders

- `app/Console/Commands/CreateAdminUser.php` uses Laravel Prompts (`text()`, `password()`) and the
  normal validator. Laravel registers commands in that folder automatically.
- **Factories** (`database/factories`) describe how to build fake records. **States** like
  `->lowStock()` or `->admin()` set up common cases.
- **Seeders** (`database/seeders`) fill the database. `DemoDataSeeder` records stock through
  `StockService`, so the demo history is as consistent as real data.

## 15. Testing

See [TESTING.md](TESTING.md). A typical feature test reads like a user story:

```php
$this->actingAs($staff)
    ->post('/stock/out', ['product_id' => $product->id, 'quantity' => 21])
    ->assertSessionHasErrors(['quantity' => 'Only 20 in stock; cannot remove 21.']);

$this->assertSame(20, $product->fresh()->quantity);
```

## Where to go next

Good next features, each building on what is here:
- **Purchase orders:** a table of orders whose "receive" action calls `StockService::stockIn`.
- **Email low-stock alerts:** a scheduled command that reads `Product::active()->lowStock()` and
  sends a notification.
- **Multiple warehouses:** a `locations` table, with quantity per product and location.
