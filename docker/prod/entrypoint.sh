#!/bin/bash
# YASSAMI — démarrage du conteneur « app » en production.
set -e

APP_DIR=/var/www/html
log() { echo "[yassami] $*"; }
cd "$APP_DIR"

# Volumes (photos, logs) créés par root : php-fpm (www-data) doit pouvoir y écrire.
mkdir -p storage/app/public storage/logs storage/framework/{cache/data,sessions,views} bootstrap/cache
chown -R www-data:www-data storage/app/public storage/logs storage/framework bootstrap/cache

# Commandes ponctuelles (« docker compose run --rm app php artisan … ») : pas d'initialisation.
if [ "$1" != "php-fpm" ]; then
    exec "$@"
fi

if [ ! -f .env ]; then
    log "ERREUR : .env absent. Copiez .env.production.example en .env sur le serveur."
    exit 1
fi
if ! grep -q '^APP_KEY=base64:' .env; then
    log "ERREUR : APP_KEY vide dans .env. Voir DEPLOY.md (étape « clé d'application »)."
    exit 1
fi

log "Attente de MySQL…"
for i in $(seq 1 60); do
    if mysqladmin ping -h"${DB_HOST:-mysql}" -P3306 --silent >/dev/null 2>&1; then
        break
    fi
    sleep 2
done

# Toujours en www-data : un fichier de log ou de cache créé par root bloquerait php-fpm.
artisan() { su-exec www-data php artisan "$@"; }

artisan migrate --force
artisan storage:link >/dev/null 2>&1 || true

# Caches de production (config, routes, vues, événements) : reconstruits à chaque démarrage.
artisan optimize

log "YASSAMI prêt."
exec "$@"
