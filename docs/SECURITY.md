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
