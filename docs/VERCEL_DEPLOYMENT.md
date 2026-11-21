# Deploying to Vercel (Docker container)

This guide deploys the application to Vercel using the repository's `Dockerfile`.
For a traditional Linux server with Nginx and PHP-FPM, see [DEPLOYMENT.md](DEPLOYMENT.md).

## Status

| Check | Result |
|---|---|
| Laravel test suite | Passing (174 tests) |
| Production-mode run with the container's runtime settings, using local PHP | Passing. The checks covered sign-in, the dashboard, products, reports and the activity log. Forwarded HTTPS and client IP were handled correctly, and nothing was written inside the application directory. |
| `docker build` / `docker run` | **Not yet run.** Docker was not available on the machine where these files were prepared. Run [step 3](#3-build-and-test-the-image-locally) before deploying. |
| Deployment on Vercel | **Not yet tested.** Do not treat the application as deployed until the checks in [step 8](#8-verify-the-deployment) pass on a real Vercel URL. |

## How Vercel runs this application

Vercel has no Laravel-specific runtime. This setup uses Vercel's general **Container runtime**,
which builds the `Dockerfile` at the repository root and sends all traffic to it.
Vercel lists this runtime as **Beta** (["Container runtime" documentation][vercel-container],
checked November 2026). Re-read that page before deploying because beta behaviour can change.

The facts that shape this setup come from Vercel's documentation:

- **The filesystem is not durable.** Functions get a read-only filesystem with writable `/tmp`.
  Files are lost when an instance is replaced, and instances are not shared.
- **Port.** The container must serve HTTP on port `80`. Setting a `PORT` environment variable in the
  project settings changes this.
- **Request bodies are limited to 4.5 MB.** The application limits product images to 2 MB.
- **Duration.** Each request may run for 300 seconds by default.
- **Instances scale automatically and are stopped when idle.** A request after an idle period
  starts a new instance (a cold start).
- **Environment variables are set in the Vercel project.** Changes only apply to deployments made
  after the change.

### What the image contains

The `Dockerfile` has three build stages:

1. **vendor**: Composer installs production dependencies only (`--no-dev`) and builds an optimised autoloader.
2. **assets**: Node 22 runs `npm ci && npm run build` to produce the Vite assets in `public/build`.
3. **app**: [FrankenPHP][frankenphp] (Caddy with PHP 8.4 built in) with the extensions `bcmath`,
   `gd` (WebP support is checked during the build), `intl`, `opcache`, `pdo_mysql` and `zip`.
   The build fails if GD cannot write WebP.

At runtime:

- **Unprivileged user.** The server runs as `www-data` and listens on `$PORT` (default `80`).
  `public/` is the document root.
- **Writable paths are in `/tmp`.** Laravel writes compiled views, caches and logs to
  `/tmp/storage` and `/tmp/bootstrap-cache`. The image itself is never written to.
- **Every start runs [`docker/entrypoint.sh`](../docker/entrypoint.sh).** It checks that `APP_KEY`
  is set, creates the `/tmp` directories and runs `php artisan optimize`. That caches configuration,
  routes, events and views using the runtime environment variables. This adds roughly one to two
  seconds to a cold start.
- **Logs** go to stderr and appear in the Vercel runtime logs.
- **PHP settings** are in [`docker/php/app.ini`](../docker/php/app.ini). OPcache is on and does not
  re-check files, uploads are capped at 4 MB, and PHP's version is hidden.
- **`.dockerignore`** keeps `.env` files, Git history, tests, docs and local caches out of the image.
- **`vercel.json`** sets `"framework": "container"`. Without it, Vercel may detect `vite.config.js`
  and deploy only the static assets.

## How sessions, cache and uploads work in production

| Concern | Production setting | Why |
|---|---|---|
| Sessions | `SESSION_DRIVER=database` (MySQL `sessions` table) | File sessions would be lost between instances. The cookie is `Secure`, `HttpOnly` and `SameSite=Lax`. |
| Cache and rate limits | `CACHE_STORE=database` | Dashboard figures, login throttling and report throttling are shared by every instance. |
| Product images | `PRODUCT_IMAGE_DISK=s3` | Local files disappear when an instance is replaced. Images are re-encoded to WebP and written to an S3-compatible bucket. |
| Client IP and HTTPS | `APP_TRUSTED_PROXIES=*` | Vercel's proxy forwards each request. Without this, every visitor appears to share one IP address and one login rate limit, and HTTPS is not detected. |
| Logs | `LOG_CHANNEL=stderr` (already set in the image) | Visible in Vercel's runtime logs. |
| Queues and scheduler | Not used | The application dispatches no queued jobs and defines no scheduled tasks. Password-reset emails are sent during the request. |

Configuration changes made for this deployment (no business logic or data was changed):

- **`config/inventory.php`**: the image disk is read from `PRODUCT_IMAGE_DISK`. The default is still `public`.
- **`config/app.php` and `AppServiceProvider`**: trusted proxies are read from `APP_TRUSTED_PROXIES`.
  The default is still none.
- **`SetSecurityHeaders`**: the Content Security Policy also allows images from the image disk's
  URL origin (scheme and host only). Without this, browsers would block bucket images.
- **`league/flysystem-aws-s3-v3`** was added. It is Laravel's standard S3 driver.
- **Tests** cover all three behaviours.

## 1. Prerequisites

1. **A Vercel account** with access to the Container runtime (Beta, available on all plans).
2. **A MySQL 8 database reachable from the internet over TLS**, for example AWS RDS,
   Google Cloud SQL, Aiven, DigitalOcean or TiDB Cloud.
   - Put it in or near the Vercel region the project uses (Vercel's default is `iad1`,
     US East, Washington D.C.). Every page makes several queries, so latency between the app and the
     database adds up quickly.
   - Vercel instances have no fixed outbound IP address, so the database cannot be restricted to
     one IP address. Vercel's Static IPs feature would allow that, but its documentation contradicts
     itself on whether custom containers support Static IPs. Confirm with Vercel before relying on it.
     Otherwise, rely on TLS, strong passwords and least-privilege accounts.
3. **An S3-compatible bucket** for product images, for example AWS S3 or Cloudflare R2.
   Images must be publicly readable. Use a public bucket URL or a custom domain.
   Vercel Blob is not S3-compatible, so it does not work with this setup.
4. **An SMTP service** (or another Laravel mail driver) for password-reset emails.
5. **Docker** on your machine for [step 3](#3-build-and-test-the-image-locally).
6. **The Vercel CLI** (optional): `npm i -g vercel`.

## 2. Create the database and run migrations

Create two database users, as in [DEPLOYMENT.md](DEPLOYMENT.md#2-database-with-least-privilege):
`inventory_app` for the running application (SELECT, INSERT, UPDATE, DELETE only) and `inventory_migrate`
for migrations. Allow connections from any host (`'%'`) and require TLS
(`ALTER USER ... REQUIRE SSL`).

Vercel has no release command for containers, so run migrations once from your own machine,
pointing a temporary environment at the production database. Never commit this file.

```bash
# .env.production.local: not committed (.env* files are ignored by Git and Docker)
APP_ENV=production
APP_KEY=base64:...        # the same key you will give Vercel
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=inventory_management
DB_USERNAME=inventory_migrate
DB_PASSWORD=...
MYSQL_ATTR_SSL_CA=/path/to/your/ca-bundle.pem
```

```bash
php artisan migrate --force --env=production.local
php artisan users:create-admin --env=production.local   # creates the first administrator interactively
```

Run `php artisan migrate --force` the same way before deploying any release that adds migrations.
The container can run migrations itself when `RUN_MIGRATIONS=true`, using `--isolated` so only
one instance migrates at a time. This is off by default. Turning it on would mean giving the
application's database user schema privileges.

Do **not** run `db:seed --class=DemoDataSeeder` against production. It creates demo accounts with
a published password.

## 3. Build and test the image locally

```bash
docker build -t inventory-app .

# A throwaway key for the local test only (bypasses the entrypoint, which requires APP_KEY)
docker run --rm --entrypoint php inventory-app artisan key:generate --show
```

Run it against a local or staging MySQL database. Do not use production for this test.

```bash
docker run --rm -p 8080:80 \
  -e APP_KEY=base64:... \
  -e APP_URL=http://localhost:8080 \
  -e DB_CONNECTION=mysql -e DB_HOST=host.docker.internal -e DB_PORT=3306 \
  -e DB_DATABASE=inventory_management -e DB_USERNAME=root -e DB_PASSWORD=... \
  -e SESSION_DRIVER=database -e CACHE_STORE=database \
  --read-only --tmpfs /tmp \
  inventory-app
```

`--read-only --tmpfs /tmp` imitates Vercel's filesystem. Check that:

- `http://localhost:8080/up` returns 200.
- You can sign in, and the dashboard loads with styles applied.
- `docker logs` shows request errors (if any) and nothing about unwritable paths.
- The image size is reasonable: `docker image ls inventory-app`.

## 4. Configure image storage

Create a bucket and an access key that can only read and write that bucket. Then set the
variables listed in [step 6](#6-set-environment-variables).

| Provider | `AWS_ENDPOINT` | `AWS_DEFAULT_REGION` | `AWS_URL` (public base URL) | `AWS_USE_PATH_STYLE_ENDPOINT` |
|---|---|---|---|---|
| AWS S3 | leave empty | the bucket's region, e.g. `us-east-1` | `https://<bucket>.s3.<region>.amazonaws.com` or a CDN domain | `false` |
| Cloudflare R2 | `https://<account-id>.r2.cloudflarestorage.com` | `auto` | the bucket's public `r2.dev` URL or a custom domain | `false` |

Images are stored under `products/` with random names. Make `AWS_URL` publicly readable.
The application's Content Security Policy automatically allows images from that origin.

## 5. Create the Vercel project

1. In Vercel, choose **Add New → Project** and import the GitHub repository
   `Momimus/inventory-management-system`. Keep the root directory as the repository root.
2. Make sure the framework preset is **Container**. `vercel.json` sets this. With the CLI,
   `vercel project update --framework container --yes` does the same.
3. Do not set a build command or output directory. Vercel builds the `Dockerfile`.
4. Add the environment variables from [step 6](#6-set-environment-variables) **before** the first
   deployment, because they only apply to deployments made after they are saved.

Pushing to `main` creates a production deployment. Other branches create preview deployments.

## 6. Set environment variables

Set these in **Project → Settings → Environment Variables** (or with `vercel env add NAME production`).
Mark credentials as **Sensitive**. Never commit them.

### Required

| Variable | Example | Notes |
|---|---|---|
| `APP_KEY` | `base64:…` | Generate it with `php artisan key:generate --show`. Keep it the same across deployments, or sessions and encrypted values stop working. **Secret.** |
| `APP_NAME` | `Inventory Management` | Shown in page titles and emails. |
| `APP_URL` | `https://your-project.vercel.app` | Your production domain, with `https://`. |
| `APP_TRUSTED_HOSTS` | `your-project.vercel.app,inventory.example.com` | Host names the app answers to. Set it in the Production environment only. Preview URLs change with every deployment. |
| `APP_TRUSTED_PROXIES` | `*` | Required behind Vercel's proxy (see above). |
| `DB_CONNECTION` | `mysql` | |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE` | | Your managed MySQL database. |
| `DB_USERNAME`, `DB_PASSWORD` | `inventory_app`, … | The data-only user. **Secret.** |
| `MYSQL_ATTR_SSL_CA` | `/etc/ssl/certs/ca-certificates.crt` | The image's system CA bundle. Use it when your provider's certificate is publicly trusted. |
| `SESSION_DRIVER` | `database` | |
| `SESSION_SECURE_COOKIE` | `true` | |
| `CACHE_STORE` | `database` | |
| `PRODUCT_IMAGE_DISK` | `s3` | |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` | | Bucket-scoped key. **Secret.** |
| `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_URL` | | See [step 4](#4-configure-image-storage). |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | `smtp`, … | Needed for password resets. Password is a **Secret.** |

### Optional

| Variable | Default | Notes |
|---|---|---|
| `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT` | empty, `false` | For S3-compatible services such as R2. |
| `INVENTORY_CURRENCY` | `USD` | ISO 4217 code. |
| `LOG_LEVEL` | `debug` | Use `warning` or `error` in production to reduce log volume. |
| `SESSION_LIFETIME` | `120` | Minutes. |
| `QUEUE_CONNECTION` | `database` | Nothing is queued today. Use `sync` to make that explicit. |
| `RUN_MIGRATIONS` | `false` | See [step 2](#2-create-the-database-and-run-migrations). |
| `PORT` | `80` | Change it only if port 80 cannot be bound (see Troubleshooting). Vercel and the container both read it. |

Already set by the image, so don't override them: `APP_ENV=production`, `APP_DEBUG=false`,
`LOG_CHANNEL=stderr`, `LARAVEL_STORAGE_PATH` and the `APP_*_CACHE` paths.

## 7. Deploy

From the dashboard, push to `main`, or use the CLI:

```bash
vercel link            # once, to connect this folder to the project
vercel deploy          # preview deployment
vercel deploy --prod   # production deployment
```

The first build takes several minutes because it installs PHP extensions. Later builds reuse cached layers.

## 8. Verify the deployment

The deployment only counts as working once all of these pass on the real Vercel URL:

1. `https://<domain>/up` returns 200.
2. The sign-in page loads with styles and fonts. The browser console shows no Content Security Policy errors.
3. An administrator can sign in, and the dashboard shows real figures.
4. **Client IP:** the **Activity log** shows your own public IP for the sign-in, not a Vercel
   address. If it shows a Vercel address, `APP_TRUSTED_PROXIES` is not working and every visitor
   shares one login rate limit.
5. **Uploads:** upload a product image (under 2 MB). It appears on the product page, and the file
   exists in the bucket under `products/`.
6. **Stock:** record a stock in and a stock out. Quantities and the dashboard update.
7. The CSV export of a report downloads.
8. **Password reset:** the email arrives and the link opens on the production domain.
9. **Staff permissions:** a Staff account gets the 403 page for reports, users and the activity log.
10. Vercel's runtime logs show no errors.

## Limits and caveats

- **Beta runtime.** The Container runtime is Beta. Check the [documentation][vercel-container] for changes.
- **Cold starts.** These include Vercel's container start plus about 1–2 s for `php artisan optimize`.
  The first request after a long idle period can take several seconds.
- **Upload size.** Vercel rejects requests over 4.5 MB before they reach the application.
  The 2 MB image limit stays well below this.
- **Existing images.** Images stored on a previous server's local disk are not moved automatically.
  Copy the `storage/app/public/products/` folder into the bucket's `products/` prefix.
- **Long requests.** Requests stop after the function duration limit (300 s by default).
  No current page comes close.
- **Region.** Keep the Vercel region and the database close together.

## Troubleshooting

| Symptom | Likely cause and fix |
|---|---|
| Deployment serves only static files or a 404 for every page | Vercel used a different framework preset. Set it to **Container** (`vercel.json` or `vercel project update --framework container --yes`). |
| Container exits with "APP_KEY is not set" | Add `APP_KEY` and redeploy. |
| Logs show `permission denied` binding to port 80 | Set `PORT=8080` in the project's environment variables and redeploy. Vercel and the server both follow it. |
| Every page returns 404 "Not Found" or a host error | The request's host is not in `APP_TRUSTED_HOSTS`. |
| Forms fail with "Page expired" (419) | Check `APP_URL` (`https://`), `SESSION_SECURE_COOKIE=true`, `APP_TRUSTED_PROXIES=*`, and that the `sessions` table exists (migrations ran). |
| Sign-in throttling locks out everyone at once | `APP_TRUSTED_PROXIES` is missing, so all visitors share the proxy's IP address. |
| Images upload but don't display | `AWS_URL` is wrong or not public. Check the browser console for Content Security Policy errors and that `PRODUCT_IMAGE_DISK=s3`. |
| Database connection errors or timeouts | The database isn't reachable from the internet, TLS is required but `MYSQL_ATTR_SSL_CA` is not set, or the region is far away. |
| Environment variable change has no effect | Variables apply to new deployments only. Redeploy. |

## Rollback

Every Vercel deployment is kept. In **Project → Deployments**, open an earlier deployment and
choose **Promote to Production** (or run `vercel rollback`). Migrations are not reversed. Before
deploying, check that new migrations are compatible with the previous release.

[vercel-container]: https://vercel.com/docs/functions/runtimes/container
[frankenphp]: https://frankenphp.dev/
