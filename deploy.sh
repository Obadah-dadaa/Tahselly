#!/usr/bin/env bash
# Run on the server (over SSH) from the project folder after uploading a new version.
# First deploy: create .env from .env.production.example, then run with --first.
set -euo pipefail

PHP="${PHP:-php}"
COMPOSER="${COMPOSER:-composer}"

echo "→ Maintenance mode on"
$PHP artisan down --retry=30 || true

echo "→ PHP dependencies"
$COMPOSER install --no-dev --optimize-autoloader --no-interaction

if [[ "${1:-}" == "--first" ]]; then
    echo "→ First deploy: app key, storage link, platform data"
    $PHP artisan key:generate --force
    $PHP artisan storage:link || true
    $PHP artisan migrate --force
    $PHP artisan db:seed --class=PlatformSeeder --force
else
    echo "→ Database migrations"
    $PHP artisan migrate --force
fi

echo "→ Caches"
$PHP artisan optimize:clear
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache
$PHP artisan event:cache

echo "→ Back online"
$PHP artisan up

if [[ "${1:-}" == "--first" ]]; then
    echo
    echo "Next: create your accounts (password is typed hidden):"
    echo "  $PHP artisan platform:admin you@your-domain.ae --role=platform --name=\"Your name\""
    echo "  $PHP artisan platform:admin dev@your-domain.ae --role=developer"
    echo "And add the scheduler cron (every minute):"
    echo "  cd $(pwd) && $PHP artisan schedule:run >> /dev/null 2>&1"
fi
