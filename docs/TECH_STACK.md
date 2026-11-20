# Tech Stack

The project deliberately uses few packages: Laravel already provides authentication helpers,
authorization, validation, caching, CSV streaming and testing.

## Backend

| Technology | Version | Purpose |
|---|---|---|
| PHP | 8.2 | Language. Enums, constructor property promotion and `match` are used throughout |
| Laravel | 12.x | Routing, controllers, Blade, Eloquent ORM, migrations, validation, policies and gates, password reset, cache, events |
| MariaDB / MySQL | 10.4 / 5.7+ | Main database. InnoDB is required for transactions and row locks |
| PHP GD | bundled | Decodes uploads and re-encodes them as WebP (see SECURITY.md) |
| Composer | 2.x | PHP dependency manager |

## Frontend

| Technology | Version | Purpose |
|---|---|---|
| Blade | (Laravel) | Server-rendered templates and reusable components in `resources/views/components` |
| Bootstrap | 5.3 | Layout, components and responsive grid. Its JavaScript handles the mobile sidebar, dropdowns, modals and collapsible rows |
| Bootstrap Icons | 1.13 | Icon font, bundled by Vite and served from this site (no CDN) |
| @popperjs/core | 2.11 | Positions Bootstrap dropdowns |
| Vite + laravel-vite-plugin | 7 / 2 | Bundles `resources/css/app.css` and `resources/js/app.js` into `public/build` |

Charts on the dashboard are Bootstrap progress bars. A chart library would add weight and JavaScript
for two small visualisations.

## Development and testing

| Package | Purpose |
|---|---|
| PHPUnit 11 | Test runner (`php artisan test`) |
| Laravel Pint | Code style fixer using Laravel's PSR-12-based preset |
| Faker | Fake data in model factories |
| Mockery, Collision | Testing helpers and readable console errors |
| Laravel Tinker | Interactive console (`php artisan tinker`) |
| Laravel Sail, Pail | Included with Laravel but unused here. Sail is a Docker setup; Pail needs `pcntl`, which Windows lacks |

## Packages added beyond a fresh Laravel install

Only `bootstrap`, `@popperjs/core` and `bootstrap-icons`, all front-end. Tailwind CSS and axios, which
come with Laravel, were removed because nothing uses them.

Things deliberately **not** added:

- **A permissions package.** Two fixed roles fit an enum column plus gates and policies.
- **An auth starter kit.** Breeze and Jetstream bring Tailwind and features this app doesn't need.
- **A CSV or Excel library.** `fputcsv` with a streamed response is enough.
- **An image library.** GD's handful of functions cover resizing and re-encoding.
