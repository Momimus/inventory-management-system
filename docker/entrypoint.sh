#!/bin/sh
# Prepares Laravel's writable directories and caches, then starts the server.
# Runs on every container start because environment variables (APP_KEY,
# database settings) only exist at runtime, not while the image is built.
set -eu

cd /app

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is not set. Generate one with 'php artisan key:generate --show' and add it to the environment." >&2
    exit 1
fi

mkdir -p \
    "$LARAVEL_STORAGE_PATH/app/private" \
    "$LARAVEL_STORAGE_PATH/app/public" \
    "$LARAVEL_STORAGE_PATH/framework/cache/data" \
    "$LARAVEL_STORAGE_PATH/framework/sessions" \
    "$LARAVEL_STORAGE_PATH/framework/testing" \
    "$LARAVEL_STORAGE_PATH/framework/views" \
    "$LARAVEL_STORAGE_PATH/logs" \
    "$(dirname "$APP_CONFIG_CACHE")" \
    "$XDG_CONFIG_HOME" \
    "$XDG_DATA_HOME"

# Caches configuration, routes, events and compiled views into /tmp.
php artisan optimize --no-interaction

# Opt-in only. Prefer running migrations once from a trusted machine
# (see docs/VERCEL_DEPLOYMENT.md); --isolated stops concurrent instances
# from migrating at the same time.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force --isolated --no-interaction
fi

exec "$@"
