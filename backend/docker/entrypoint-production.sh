#!/bin/sh
set -e

if [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] vendor/ not found in image - refusing to start." >&2
    exit 1
fi

if [ -f artisan ]; then
    php artisan package:discover --ansi || true
    php artisan optimize || true
fi

exec "$@"
