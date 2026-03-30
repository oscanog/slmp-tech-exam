#!/bin/sh
set -eu

cd /var/www/html
rm -f /tmp/slmp-ready

if [ ! -f .env ]; then
  cp .env.example .env
fi

if [ ! -f vendor/autoload.php ] || [ composer.lock -nt vendor/autoload.php ] || [ composer.json -nt vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist
fi

until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');"; do
  echo "Waiting for MySQL..."
  sleep 2
done

if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --force
fi

php artisan migrate --force

if [ ! -f storage/oauth-private.key ] || [ ! -f storage/oauth-public.key ]; then
  php artisan passport:keys --force
fi

if [ -f storage/oauth-private.key ] && [ -f storage/oauth-public.key ]; then
  chown www-data:www-data storage/oauth-private.key storage/oauth-public.key
  chmod 600 storage/oauth-private.key storage/oauth-public.key
fi

CLIENT_COUNT="$(php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo \Illuminate\Support\Facades\DB::table('oauth_clients')->count();")"
CLIENT_COUNT="$(printf '%s' "$CLIENT_COUNT" | tr -dc '0-9')"

if [ "${CLIENT_COUNT:-0}" = "0" ]; then
  php artisan passport:client --personal --name="SLMP Personal Access Client" --no-interaction
  php artisan passport:client --password --name="SLMP Password Grant Client" --provider=users --no-interaction
fi

touch /tmp/slmp-ready
exec "$@"
