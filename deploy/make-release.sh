#!/bin/bash
# YASSAMI — crée l'archive du code à envoyer sur le serveur (à lancer sur le PC, dans Git Bash).
#   bash deploy/make-release.sh   →   releases/yassami-release.tar.gz
set -e
cd "$(dirname "$0")/.."

mkdir -p releases
OUT=releases/yassami-release.tar.gz

tar -czf "$OUT" \
    --exclude=./.git \
    --exclude=./.env \
    --exclude=./node_modules \
    --exclude=./vendor \
    --exclude=./releases \
    --exclude=./backups \
    --exclude="./products images" \
    --exclude=./public/build \
    --exclude=./public/hot \
    --exclude=./public/storage \
    --exclude="./storage/app/public/*" \
    --exclude="./storage/app/private/*" \
    --exclude="./storage/framework/cache/data/*" \
    --exclude="./storage/framework/sessions/*" \
    --exclude="./storage/framework/views/*" \
    --exclude="./storage/logs/*" \
    --exclude="./bootstrap/cache/*.php" \
    --exclude=./.phpunit.cache \
    .

echo "Archive du code : $OUT ($(du -h "$OUT" | cut -f1))"
