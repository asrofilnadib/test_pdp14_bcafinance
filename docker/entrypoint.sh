#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env
fi

echo "Menunggu MySQL siap..."
until mysqladmin ping -h "${DB_HOST:-mysql}" -P "${DB_PORT:-3306}" -u"${DB_USERNAME:-pdpbcaf}" -p"${DB_PASSWORD:-pdpbcaf}" --silent; do
    sleep 2
done

if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

php artisan migrate --force
php artisan db:seed --force
php artisan storage:link >/dev/null 2>&1 || true

echo "JKL Finance siap di http://localhost:8000"
exec php artisan serve --host=0.0.0.0 --port=8000
