#!/bin/bash
# YASSAMI — importe les données du PC sur le serveur (PREMIÈRE mise en ligne uniquement).
#   cd /opt/yassami && bash deploy/import-data.sh /opt/yassami-data.tar.gz
# Refuse de s'exécuter si la base de production contient déjà des commandes réelles
# (sauf --force), pour ne jamais écraser des données de production par erreur.
set -e
cd "$(dirname "$0")/.."

ARCHIVE="$1"
FORCE="$2"
. deploy/_compose.sh

[ -f "$ARCHIVE" ] || { echo "Usage : bash deploy/import-data.sh /chemin/yassami-data.tar.gz"; exit 1; }

ORDERS=$($DC exec -T mysql sh -c 'mysql -N -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "SELECT COUNT(*) FROM orders" 2>/dev/null' || echo 0)
if [ "${ORDERS:-0}" -gt 0 ] && [ "$FORCE" != "--force" ]; then
    echo "ARRÊT : la base de production contient déjà $ORDERS commande(s)."
    echo "L'import remplacerait toutes les données. Ajoutez --force seulement si vous êtes sûr."
    exit 1
fi

TMP=$(mktemp -d)
tar -xzf "$ARCHIVE" -C "$TMP"

echo "Import de la base…"
$DC exec -T mysql sh -c 'exec mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' < "$TMP/database.sql"

# Sessions et cache du PC : inutiles (et liés à une autre clé d'application).
$DC exec -T mysql sh -c 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "TRUNCATE sessions; TRUNCATE cache; TRUNCATE cache_locks;"'

echo "Copie des photos…"
# Flux tar (et non « docker cp dossier/. ») : copie le CONTENU, sans sous-dossier parasite.
tar -C "$TMP/uploads" -cf - . | $DC exec -T app tar -xf - -C /var/www/html/storage/app/public
$DC exec -T app chown -R www-data:www-data /var/www/html/storage/app/public

rm -rf "$TMP"
$DC restart app
echo "Import terminé."
