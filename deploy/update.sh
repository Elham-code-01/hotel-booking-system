#!/usr/bin/env bash
# Run on the server after you uploaded new code. It puts the site in maintenance mode while it works.
set -euo pipefail

cd "$(dirname "$0")/.."

./deploy/backup.sh                       # always a fresh backup before changing anything
php artisan down --retry=60
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan up

echo "Update finished."
