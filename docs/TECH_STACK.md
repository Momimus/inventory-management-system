# Tech Stack

## Backend

| Technology | Version | Purpose |
|------------|---------|---------|
| PHP | 8.2.12 | Language the application runs on. |
| Laravel | 12.73 | Framework: routing, controllers, Blade, Eloquent, migrations, validation, auth. |
| MariaDB (MySQL compatible) | 10.4.32 | Stores products, categories, stock movements, users and sessions. |
| Composer | 2.8.9 | Installs PHP packages listed in `composer.json`. |

## Frontend

| Technology | Version | Purpose |
|------------|---------|---------|
| Blade | (Laravel) | Server-side HTML templates in `resources/views`. |
| Bootstrap | 5.3.9 | CSS components (grid, cards, forms, tables, alerts) and small JS widgets (navbar collapse, dismissible alerts). |
| @popperjs/core | 2.11.8 | Positioning engine Bootstrap needs for dropdowns and tooltips. |
| Vite | 7.3 | Bundles `resources/css/app.css` and `resources/js/app.js` into `public/build`. |
| laravel-vite-plugin | 2.1 | Connects Vite with Laravel's `@vite` Blade directive. |
| Node.js / npm | 22.13 / 11.3 | Runs Vite and installs frontend packages. |

Tailwind CSS and axios come with a fresh Laravel install. They were removed because this project uses
Bootstrap, and no page sends AJAX requests yet.

## Development tools (included with Laravel)

| Package | Purpose |
|---------|---------|
| laravel/pint 1.30 | Code style fixer. `./vendor/bin/pint` formats code to Laravel's PSR-12-based style. |
| phpunit/phpunit 11.5 | Test runner used by `php artisan test`. |
| laravel/tinker 2.11 | Interactive console (`php artisan tinker`) for trying Eloquent queries. |
| fakerphp/faker 1.24 | Fake data for factories and tests. |
| mockery/mockery 1.6 | Mocking in tests. |
| nunomaduro/collision 8.9 | Readable error output in the console. |
| laravel/pail 1.3 | Log tailing. Does not work on native Windows (needs `pcntl`). |
| laravel/sail 1.71 | Docker environment. Not used here, because XAMPP provides PHP and MySQL. |

No extra packages were added beyond Bootstrap and Popper.
