#!/bin/sh
set -eu

cd /var/www/html

if [ ! -f .env ]; then
  cp .env.example .env
fi

if [ ! -d vendor ]; then
  composer install --no-interaction --prefer-dist
fi

until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');"; do
  echo "Waiting for MySQL..."
  sleep 2
done

php artisan key:generate --force
php artisan migrate --force

if [ ! -f storage/oauth-private.key ] || [ ! -f storage/oauth-public.key ]; then
  php artisan passport:keys --force
fi

CLIENT_COUNT="$(php artisan tinker --execute='echo \Illuminate\Support\Facades\DB::table("oauth_clients")->count();')"
CLIENT_COUNT="$(printf '%s' "$CLIENT_COUNT" | tr -dc '0-9')"

if [ "${CLIENT_COUNT:-0}" = "0" ]; then
  php artisan passport:client --personal --name="SLMP Personal Access Client" --no-interaction
  php artisan passport:client --password --name="SLMP Password Grant Client" --provider=users --no-interaction
fi

exec "$@"
