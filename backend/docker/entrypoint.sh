#!/usr/bin/env bash
set -e

# Ensure an app key exists.
if ! grep -q "^APP_KEY=base64" .env 2>/dev/null; then
  php artisan key:generate --force
fi

# For SQLite, make sure the database file exists.
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
  mkdir -p database
  touch database/database.sqlite
fi

# Wait for and run migrations (+ seed on first boot).
php artisan migrate --force --seed

php artisan config:clear

echo "Starting Laravel API on 0.0.0.0:8000"
exec php artisan serve --host=0.0.0.0 --port=8000
