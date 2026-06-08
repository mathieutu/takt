#!/bin/sh
set -e

php artisan optimize
php artisan migrate --force
php artisan app:demo-refresh

exec docker-php-entrypoint "$@"
