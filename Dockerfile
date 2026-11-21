# syntax=docker/dockerfile:1
#
# Production image for the Inventory Management System.
# Runs Laravel on FrankenPHP (Caddy + PHP) and is built for Vercel's Container
# runtime, but works on any container host. See docs/VERCEL_DEPLOYMENT.md.
#
# Nothing secret is baked in: APP_KEY, database and storage credentials are
# runtime environment variables, and .env files are excluded by .dockerignore.

ARG FRANKENPHP_IMAGE=dunglas/frankenphp:1.13.4-php8.4-bookworm
ARG NODE_IMAGE=node:22.23.4-bookworm-slim
ARG COMPOSER_IMAGE=composer/composer:2.8.12-bin

FROM ${COMPOSER_IMAGE} AS composer-bin

# ---------------------------------------------------------------------------
# PHP runtime shared by the dependency and final stages
# ---------------------------------------------------------------------------
FROM ${FRANKENPHP_IMAGE} AS php-base

# gd must support WebP: uploaded product images are re-encoded to WebP.
RUN install-php-extensions bcmath gd intl opcache pdo_mysql zip \
    && php -r 'exit(function_exists("imagewebp") && (gd_info()["WebP Support"] ?? false) ? 0 : 1);' \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php/app.ini "$PHP_INI_DIR/conf.d/zz-app.ini"

WORKDIR /app

# ---------------------------------------------------------------------------
# Composer dependencies (no development packages)
# ---------------------------------------------------------------------------
FROM php-base AS vendor

COPY --from=composer-bin /composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction

# ---------------------------------------------------------------------------
# Vite production assets
# ---------------------------------------------------------------------------
FROM ${NODE_IMAGE} AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# ---------------------------------------------------------------------------
# Final image
# ---------------------------------------------------------------------------
FROM php-base AS app

# The image is read-only at runtime: everything Laravel writes (compiled
# views, config/route caches, logs) goes to /tmp, the only writable path.
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    LARAVEL_STORAGE_PATH=/tmp/storage \
    APP_CONFIG_CACHE=/tmp/bootstrap-cache/config.php \
    APP_ROUTES_CACHE=/tmp/bootstrap-cache/routes.php \
    APP_EVENTS_CACHE=/tmp/bootstrap-cache/events.php \
    APP_SERVICES_CACHE=/tmp/bootstrap-cache/services.php \
    APP_PACKAGES_CACHE=/tmp/bootstrap-cache/packages.php \
    XDG_CONFIG_HOME=/tmp/caddy/config \
    XDG_DATA_HOME=/tmp/caddy/data \
    PORT=80

COPY --from=vendor --chown=root:root /app /app
COPY --from=assets --chown=root:root /app/public/build /app/public/build
COPY --chmod=0755 docker/entrypoint.sh /usr/local/bin/app-entrypoint

# Run as an unprivileged user. The capability lets it bind to port 80,
# Vercel's default container port.
RUN rm -f /app/public/hot /app/public/storage \
    && if ! command -v setcap >/dev/null; then \
        apt-get update && apt-get install -y --no-install-recommends libcap2-bin && rm -rf /var/lib/apt/lists/*; \
    fi \
    && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp

USER www-data

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD php -r '$c = @fsockopen("127.0.0.1", (int) getenv("PORT"), $e, $s, 2); exit($c ? 0 : 1);'

ENTRYPOINT ["app-entrypoint"]
CMD ["sh", "-c", "exec frankenphp php-server --listen \":${PORT}\" --root /app/public"]
