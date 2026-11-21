# YASSAMI — commande « docker compose » de production, partagée par les scripts deploy/*.sh.
# À sourcer depuis le dossier de l'application : . deploy/_compose.sh
DC="docker compose -f docker-compose.prod.yml"

# Réseau d'un reverse proxy existant (serveur partagé) : voir docker-compose.proxy.yml.
if grep -qE '^PROXY_NETWORK=.+' .env 2>/dev/null; then
    DC="$DC -f docker-compose.proxy.yml"
fi

# HTTPS par le Caddy de YASSAMI (serveur dédié uniquement).
if [ -f .use-caddy ]; then
    DC="$DC --profile caddy"
fi
