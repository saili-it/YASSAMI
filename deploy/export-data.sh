#!/bin/bash
# YASSAMI — exporte les DONNÉES du PC (base + photos) pour la PREMIÈRE mise en ligne.
# À lancer sur le PC, dans Git Bash, avec « docker compose up -d » démarré.
#   bash deploy/export-data.sh   →   releases/yassami-data.tar.gz
# Contient : produits, catégories, photos, paramètres, comptes admin, commandes de test.
set -e
cd "$(dirname "$0")/.."

TMP=$(mktemp -d)
mkdir -p releases "$TMP/uploads"

echo "Export de la base…"
docker compose exec -T mysql sh -c \
  'exec mysqldump -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" --single-transaction --no-tablespaces --skip-lock-tables "$MYSQL_DATABASE"' \
  > "$TMP/database.sql" 2>/dev/null

[ -s "$TMP/database.sql" ] || { echo "ERREUR : export vide (MySQL démarré ?)"; exit 1; }

echo "Copie des photos…"
cp -r storage/app/public/. "$TMP/uploads/" 2>/dev/null || true

tar -czf releases/yassami-data.tar.gz -C "$TMP" database.sql uploads
rm -rf "$TMP"

echo "Archive des données : releases/yassami-data.tar.gz ($(du -h releases/yassami-data.tar.gz | cut -f1))"
