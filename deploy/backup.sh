#!/bin/bash
# YASSAMI — sauvegarde quotidienne (base + photos), conservée 14 jours.
#   bash /opt/yassami/deploy/backup.sh
# Cron (tous les jours à 3h) :
#   0 3 * * * bash /opt/yassami/deploy/backup.sh >> /var/log/yassami-backup.log 2>&1
set -e

APP_DIR=/opt/yassami
DEST=/opt/yassami-backups
KEEP_DAYS=14
STAMP=$(date +%Y-%m-%d_%H%M)

cd "$APP_DIR"
. deploy/_compose.sh
mkdir -p "$DEST"
chmod 700 "$DEST"

$DC exec -T mysql sh -c \
  'exec mysqldump -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" --single-transaction --no-tablespaces --skip-lock-tables "$MYSQL_DATABASE"' \
  2>/dev/null | gzip > "$DEST/db_$STAMP.sql.gz"

$DC exec -T app tar -czf - -C /var/www/html/storage/app/public . > "$DEST/photos_$STAMP.tar.gz"

find "$DEST" -type f -mtime +$KEEP_DAYS -delete
echo "$(date '+%F %T') sauvegarde OK : $DEST/db_$STAMP.sql.gz + photos_$STAMP.tar.gz"
