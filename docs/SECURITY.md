# Security

Each protection below lists the code that implements it and the test that proves it.
If a protection is not listed here, assume it does not exist yet.

## Authentication

| Protection | How | Code | Test |
|---|---|---|---|
| Passwords are hashed | The `hashed` cast on `User::$password` runs bcrypt automatically on save | `app/Models/User.php` | `CreateAdminUserCommandTest` |
| Strong password rules | Min 10 chars, mixed case, a number; in production also checked against known breaches (`uncompromised()`) | `AppServiceProvider::boot()` | `PasswordResetTest::test_reset_rejects_weak_passwords` |
| Login rate limiting | 5 failed attempts per email + IP, then a 60 second lock. The lockout is logged | `app/Http/Requests/Auth/LoginRequest.php` | `AuthenticationTest::test_login_is_rate_limited_after_repeated_failures` |
| Session fixation | `session()->regenerate()` gives a new session ID after login | `LoginController::store()` | `AuthenticationTest::test_login_regenerates_the_session_id` |
| Logout ends the session | `invalidate()` destroys session data, `regenerateToken()` issues a new CSRF token. Logout is POST only | `LoginController::destroy()` | `test_logout_ends_the_session`, `test_logout_requires_post` |
| Inactive accounts | Can't log in (`is_active` is part of the credentials) and are signed out on their next request | `LoginRequest`, `app/Http/Middleware/EnsureUserIsActive.php` | `test_inactive_user_cannot_log_in`, `test_deactivated_user_is_signed_out_on_next_request` |
| No account enumeration | Wrong password, unknown email and inactive account all show the same message; the reset form always gives the same reply | `LoginRequest`, `PasswordResetLinkController` | `test_unknown_and_inactive_emails_get_the_same_response_and_no_link` |
| Password reset | Laravel's password broker: single-use tokens, expire after 60 minutes, throttled | `PasswordResetLinkController`, `NewPasswordController`, `config/auth.php` | `PasswordResetTest` |
| Other sessions signed out after a password change | `auth.session` middleware compares the password hash stored in each session | `routes/web.php` | (framework behaviour) |
| No self-registration | Accounts are created by an administrator or with `php artisan users:create-admin` | `routes/web.php` | `AuthenticationTest` |
| Profile can't escalate privileges | `role` and `is_active` are not mass-assignable; profile routes have no user ID | `User::$fillable`, `ProfileController` | `ProfileTest::test_profile_update_cannot_change_role_or_status` |

## Authorization (roles)

There are two roles, stored in `users.role` and typed by `App\Enums\UserRole`. Permissions are checked
on the server for every request; hiding a button is only a convenience.

| Where | Used for |
|---|---|
| `app/Policies/*Policy.php` | Rules about one kind of record, e.g. `UserPolicy::update()`. Laravel finds the policy for each model by name |
| `AppServiceProvider::defineGates()` | Abilities not tied to a record: `adjust-stock`, `view-reports`, `view-activity-log` |
| Form Requests `authorize()` | Store/update requests check the policy before validation runs |
| `$this->authorize()` in controllers | All other actions |
| `@can` in Blade | Hides links and buttons the user cannot use |

User management rules (`UserPolicy`):

- Only administrators can list, create or edit users.
- Administrators cannot change their own role or deactivate themselves, so at least one active admin always remains.
- Users are never deleted; deactivation keeps their history intact.

Tests: `UserManagementTest`, including `test_staff_cannot_access_any_user_management_route`. That test
sends staff requests straight to admin URLs with other users' IDs (IDOR attempts) and expects 403.

## Input validation, SQL injection and XSS

- **Form Requests validate every write.** `app/Http/Requests/*Request.php` holds the rules: strict decimal
  prices (`decimal:0,2`, no negatives), integer quantities, SKU format and uniqueness, email format, and
  unique names.
- **List filters are validated too.** Index pages validate their GET parameters. Sorting only accepts
  column names from a whitelist (`ProductController::SORTABLE`), so a URL can never choose a column.
- **Queries are parameterised.** All queries use Eloquent or the query builder with bound parameters.
  Search goes through the `Searchable` trait, which passes the term as a binding. There is no raw SQL
  built from user input.
  Tests: `test_sorting_only_accepts_whitelisted_columns`, `test_search_input_is_treated_as_data_not_sql`.
- **Output is escaped.** Blade's `{{ }}` escapes all output. The only raw outputs are
  `{!! nl2br(e($text)) !!}` for supplier addresses and product descriptions. `e()` escapes first, so only
  the `<br>` tags added afterwards are real HTML.
  Tests: `test_category_names_are_escaped_in_html`, `test_supplier_address_is_escaped_while_keeping_line_breaks`,
  `test_product_text_is_escaped`.
- **Quantity can't be mass-assigned.** `quantity` is not in `Product::$fillable`, so a forged form field cannot
  change stock. Test: `test_quantity_cannot_be_set_through_the_product_form`.

## Inventory integrity

All quantity changes go through `app/Services/StockService.php`. Nothing else writes `products.quantity`.

| Rule | How | Test |
|---|---|---|
| No negative stock | Stock-out is refused when it asks for more than is on hand. `products.quantity` is also `UNSIGNED`, so the database rejects negatives too | `test_stock_out_cannot_make_stock_negative` |
| Quantity and history saved together | `DB::transaction()` wraps the quantity update, the transaction row and the activity-log row | `test_quantity_update_is_rolled_back_when_recording_the_transaction_fails` |
| No race conditions | The product row is locked with `lockForUpdate()` before it is read. The new quantity is calculated from that locked row, never from a form or an old model | `test_quantities_are_read_from_the_database_not_from_a_stale_model`, plus the MySQL experiment below |
| Product edits don't overwrite stock | Eloquent only saves changed columns, and `quantity` is not fillable | `test_product_edits_do_not_overwrite_stock_changed_in_the_meantime` |
| History is permanent | No edit or delete routes. `StockTransactionPolicy::update/delete` return `false`. The `IsAppendOnly` trait makes the model throw on update or delete. The same applies to activity logs | `test_transactions_cannot_be_modified_or_deleted`, `test_there_is_no_way_to_edit_or_delete_a_transaction_over_http` |
| Recorder can't be faked | `user_id` comes from the signed-in user, never from the form | `test_the_recording_user_cannot_be_spoofed` |
| Adjustments are restricted | Admin only (`adjust-stock` gate), and a reason is required | `test_staff_cannot_make_adjustments`, `test_admin_adjustment_requires_a_reason` |
| Deadlocks retried | `DB::transaction(..., attempts: 3)` retries on deadlock | — |

**Concurrency experiment (MariaDB 10.4).** Automated tests use SQLite, which ignores row locks. So the
locking was checked on MariaDB: 8 PHP processes, started at the same instant, each tried 10 one-unit
stock-outs on a product with 50 in stock. All 80 attempts landed within the same second.

| Version | Result |
|---|---|
| `StockService` (transaction + lock) | 50 succeeded, 30 refused, final quantity **0**, 50 distinct `balance_after` values (49 → 0) |
| Naive read-modify-write (no lock) | Workers believed they sold **80**; the database showed **12** left |

## File uploads (product images)

Code: `ProductRequest::rules()` and `app/Services/ProductImageStorage.php`. Tests: `ProductImageUploadTest`.

1. **File type.** Only `jpg`, `jpeg`, `png` and `webp` extensions are allowed, *and* the detected MIME
   type must be `image/jpeg`, `image/png` or `image/webp`. SVG, which can contain scripts, is not
   accepted.
2. **Size and dimensions.** The limit is 2 MB, with dimensions between 50 and 4000 px. Dimensions are read
   from the header before the image is decoded, which stops "decompression bomb" files.
3. **Re-encoding.** GD decodes the image and writes a new WebP file, at most 800 px on each side. Embedded
   scripts, EXIF data and polyglot payloads do not survive. A PHP file renamed to `.jpg` fails validation.
4. **File name.** The stored name is 40 random characters plus `.webp`. Nothing from the original file name
   is used.
5. **Storage.** Files go to `storage/app/public/products`, served through the `public/storage` link. They
   are always `.webp`, so the web server never executes them.
6. **Clean-up.** Replaced or removed images are deleted from disk.

## HTTP and browser protections

| Protection | How | Code | Test |
|---|---|---|---|
| CSRF | Laravel's `ValidateCsrfToken` middleware on every web route; forms use `@csrf` | `bootstrap/app.php` (framework default) | `HttpSecurityTest::test_post_without_csrf_token_is_rejected` |
| Clickjacking | `X-Frame-Options: DENY` and CSP `frame-ancestors 'none'` | `app/Http/Middleware/SetSecurityHeaders.php` | `HttpSecurityTest::test_security_headers_are_sent` |
| Content Security Policy | Scripts only from this site, no inline scripts. Not sent while `npm run dev` runs | `SetSecurityHeaders` | same |
| MIME sniffing | `X-Content-Type-Options: nosniff` | `SetSecurityHeaders` | same |
| HSTS | Sent in production over HTTPS | `SetSecurityHeaders` | — |
| Cookies | Session cookie is `HttpOnly` and `SameSite=Lax`; set `SESSION_SECURE_COOKIE=true` in production | `config/session.php` | `HttpSecurityTest::test_session_cookie_is_http_only` |
| Host header | Only hosts in `APP_TRUSTED_HOSTS` are accepted outside local development | `bootstrap/app.php`, `config/app.php` | — |
| HTTPS links | `URL::forceScheme('https')` in production | `AppServiceProvider` | — |

## Activity logging and secrets

Sign-ins, sign-outs, failed attempts, lockouts and password changes are written to `activity_logs`
by `app/Listeners/RecordAuthenticationActivity.php`.

`ActivityLogger` removes `password`, `password_confirmation`, `current_password`, `remember_token` and
`token` from logged data at any depth. A failed login records only the email that was tried.
Tests: `test_failed_login_is_logged_without_the_password`, `test_password_change_logs_never_contain_the_password`.
