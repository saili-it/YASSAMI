# YASSAMI

Boutique en ligne de sportswear premium pour le Maroc — commande via WhatsApp.

Stack : Laravel 13 · MySQL 8 · Blade + Tailwind 4 + Alpine.js · Docker.

```bash
docker compose up -d                                   # http://localhost:8020
docker compose run --rm --no-deps vite npm run build   # assets
docker compose exec app php artisan db:seed            # catalogue de démo (local)
docker compose exec app php artisan test
```

Configuration métier : variables `YASSAMI_*` du fichier `.env` (voir `.env.example`).
Documentation complète : [CLAUDE.md](CLAUDE.md).
