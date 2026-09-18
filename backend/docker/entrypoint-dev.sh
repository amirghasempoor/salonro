#!/bin/sh
set -e

if [ -f composer.json ] && [ ! -d vendor ]; then
    echo "[entrypoint] vendor/ not found - running composer install..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

exec "$@"
