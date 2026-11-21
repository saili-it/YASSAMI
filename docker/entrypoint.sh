#!/bin/bash
set -e

APP_DIR=/var/www/html
log() { echo -e "\033[0;36m[yassami]\033[0m $*"; }

cd "$APP_DIR"

# storage/framework et bootstrap/cache sont des volumes nommés créés par root :
# php-fpm (www-data) doit pouvoir y écrire, sinon Laravel renvoie 500.
mkdir -p storage/framework/{cache/data,sessions,views} storage/logs storage/app/public bootstrap/cache
chown -R www-data:www-data storage/framework bootstrap/cache storage/app/public storage/logs 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

# Commandes ponctuelles (ex. « docker compose run --rm app composer … ») :
# on n'exécute pas l'initialisation applicative.
if [ "$1" != "php-fpm" ]; then
    exec "$@"
fi

if [ ! -f vendor/autoload.php ]; then
    log "Installation des dépendances Composer…"
    composer install --no-interaction --prefer-dist --no-progress
fi

if [ ! -f .env ]; then
    log "Création du .env depuis .env.example"
    cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    log "Génération de l'APP_KEY"
    php artisan key:generate --force
fi

if [ -n "${WAIT_FOR_DB_HOST:-}" ]; then
    log "Attente de MySQL ${WAIT_FOR_DB_HOST}:3306…"
    for i in $(seq 1 60); do
        if mysqladmin ping -h"$WAIT_FOR_DB_HOST" -P3306 --silent >/dev/null 2>&1; then
            log "MySQL disponible."
            break
        fi
        sleep 2
    done
fi

php artisan config:clear >/dev/null 2>&1 || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    log "Migrations…"
    php artisan migrate --force
fi

if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    log "Seeders…"
    php artisan db:seed --force
fi

[ -L public/storage ] || php artisan storage:link || true

log "Application prête → http://localhost:${APP_PORT:-8020}"
exec "$@"
