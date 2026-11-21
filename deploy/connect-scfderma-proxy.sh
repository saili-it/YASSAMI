#!/bin/bash
# YASSAMI — branche yassami.ma (HTTPS) sur le Nginx du projet scfderma (serveur partagé).
#   cd /opt/yassami && bash deploy/connect-scfderma-proxy.sh
# Vérifie tout AVANT de modifier quoi que ce soit, sauvegarde la config scfderma,
# n'ajoute les blocs qu'une seule fois, et teste la config Nginx avant chaque rechargement.
set -u

CONF=/home/scfderma/docker/nginx/prod-ssl.conf
NGINX=scfderma-nginx-1
CERTBOT=scfderma-certbot-1
IP=147.93.84.224
DIR=$(cd "$(dirname "$0")" && pwd)

ok() { echo "  [OK] $*"; }
fail() { echo "  [STOP] $*"; echo "Rien d'autre n'a été modifié."; exit 1; }

echo "1/5 Vérifications"
[ -f "$CONF" ] || fail "config introuvable : $CONF"
for d in yassami.ma www.yassami.ma; do
    getent hosts "$d" | grep -q "$IP" && ok "DNS $d → $IP" || fail "DNS $d ne pointe pas vers $IP"
done
code=$(docker exec "$NGINX" sh -c 'wget -q -S -O /dev/null http://yassami-web/ 2>&1 | grep -m1 "HTTP/" | awk "{print \$2}"')
[ "$code" = "200" ] && ok "YASSAMI répond 200 au proxy" || fail "YASSAMI répond « ${code:-rien} » au proxy (voir : . deploy/_compose.sh && \$DC logs --tail=50 app)"

echo "2/5 Sauvegarde de la config scfderma"
BAK=/root/prod-ssl.conf.bak-$(date +%F-%H%M%S)
cp "$CONF" "$BAK" && ok "$BAK"

echo "3/5 Bloc HTTP (validation Let's Encrypt)"
if grep -q "YASSAMI (yassami.ma) — DÉBUT ÉTAPE 1" "$CONF"; then
    ok "déjà présent"
else
    cat "$DIR/scfderma-proxy/yassami-http.conf" >> "$CONF"   # >> : même fichier (montage Docker conservé)
    ok "ajouté"
fi
docker exec "$NGINX" nginx -t >/dev/null 2>&1 || { cat "$BAK" > "$CONF"; fail "nginx -t a échoué : config restaurée"; }
docker exec "$NGINX" nginx -s reload >/dev/null 2>&1 && ok "Nginx rechargé"

echo "4/5 Certificat HTTPS"
if docker exec "$CERTBOT" test -f /etc/letsencrypt/live/yassami.ma/fullchain.pem; then
    ok "certificat déjà présent"
else
    docker exec "$CERTBOT" certbot certonly --webroot -w /var/www/certbot \
        -d yassami.ma -d www.yassami.ma --non-interactive --agree-tos --register-unsafely-without-email \
        || fail "certbot a échoué (message ci-dessus). Le bloc HTTP reste en place, sans effet sur scfderma."
    ok "certificat obtenu"
fi

echo "5/5 Bloc HTTPS"
if grep -q "YASSAMI (yassami.ma) — DÉBUT ÉTAPE 2" "$CONF"; then
    ok "déjà présent"
else
    cat "$DIR/scfderma-proxy/yassami-https.conf" >> "$CONF"
    ok "ajouté"
fi
docker exec "$NGINX" nginx -t >/dev/null 2>&1 || { cat "$BAK" > "$CONF"; docker exec "$NGINX" nginx -s reload; fail "nginx -t a échoué : config restaurée"; }
docker exec "$NGINX" nginx -s reload >/dev/null 2>&1 && ok "Nginx rechargé"

# Rechargement quotidien : Nginx prend en compte les certificats renouvelés par certbot.
CRON='30 4 * * * docker exec scfderma-nginx-1 nginx -s reload >/dev/null 2>&1'
crontab -l 2>/dev/null | grep -qF "$CRON" || { (crontab -l 2>/dev/null; echo "$CRON") | crontab - && ok "cron de rechargement quotidien ajouté"; }

sleep 2
echo
echo "Résultat : $(curl -sI https://yassami.ma | head -1)"
echo "→ https://yassami.ma"
