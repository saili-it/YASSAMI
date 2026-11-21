#!/bin/bash
# YASSAMI — met en ligne une nouvelle version du code (sur le serveur).
#   cd /opt/yassami && bash deploy/update.sh                          (dépôt GitHub : git pull)
#   bash /opt/yassami/deploy/update.sh /opt/yassami-release.tar.gz    (archive envoyée par scp)
# Conserve .env, la base et les photos (volumes Docker). Sauvegarde avant la mise à jour.
set -e

ARCHIVE="$1"
APP_DIR=/opt/yassami
cd "$APP_DIR"

echo "Sauvegarde avant mise à jour…"
bash deploy/backup.sh || echo "(sauvegarde ignorée : première installation ?)"

if [ -n "$ARCHIVE" ]; then
    [ -f "$ARCHIVE" ] || { echo "Archive introuvable : $ARCHIVE"; exit 1; }
    echo "Extraction du code…"
    tar -xzf "$ARCHIVE" -C "$APP_DIR"
elif [ -d .git ]; then
    echo "Récupération du code (git pull)…"
    git pull --ff-only
else
    echo "Usage : bash deploy/update.sh [/chemin/yassami-release.tar.gz]  (sans argument : dossier git requis)"
    exit 1
fi

# Après mise à jour du code : la version à jour du helper est utilisée.
. deploy/_compose.sh

echo "Construction des images…"
$DC build

echo "Redémarrage…"
$DC up -d --remove-orphans
docker image prune -f >/dev/null

$DC ps
echo "Mise à jour terminée."
