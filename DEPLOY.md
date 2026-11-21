# Deploying YASSAMI on the VPS (yassami.ma)

Production runs entirely in Docker on the server, in `/opt/yassami`:

| Container | Role |
|---|---|
| `app` | PHP-FPM with the code, dependencies and compiled assets baked into the image |
| `web` | Nginx serving the site on `127.0.0.1:8020` only (not reachable from the internet) |
| `mysql` | MySQL 8, private (no open port) |
| `caddy` *(option A only)* | HTTPS with automatic Let's Encrypt certificates on ports 80/443 |

Data lives in Docker volumes and survives updates: `yassami-prod_mysql-data` (database) and `yassami-prod_uploads` (product photos).

---

## 0. Before you start

1. **DNS**: at your domain registrar, create two **A records** pointing to the VPS IP address:
   `yassami.ma` → `VPS_IP` and `www.yassami.ma` → `VPS_IP`. Propagation can take up to a few hours.
2. **Check the server** (as root):

   ```bash
   docker --version && docker compose version     # Docker installed?
   ss -tlnp | grep -E ':(80|443|8020)\s'          # Ports already used?
   free -h && df -h /                              # RAM / disk
   ```

   - No Docker → `curl -fsSL https://get.docker.com | sh`
   - **Nothing on ports 80/443** → use **option A (Caddy)** below.
   - **A Nginx installed directly on the server** owns 80/443 → use **option B (host Nginx)**.
   - **A Docker container** owns 80/443 (`docker-proxy` in the `ss` output; **this is the case on the current VPS**: `scfderma-nginx-1`) → use **option C (shared Docker proxy)**.
   - Never stop the service that already owns 80/443: another site depends on it.
   - Port 8020 already used → pick another one (`HTTP_PORT` in `.env`).

---

## 1. Get the code and the data onto the server

**Code: from GitHub (recommended).** The repository is **private**. The server reads it with a read-only *deploy key*:

```bash
# On the server: create the key, then show the public part
ssh-keygen -t ed25519 -f ~/.ssh/yassami_deploy -N "" -C "yassami-vps"
cat ~/.ssh/yassami_deploy.pub
```

On GitHub: repository → **Settings → Deploy keys → Add deploy key**, paste the line, leave **"Allow write access" unchecked**. Then on the server:

```bash
cat >> ~/.ssh/config <<'EOF'
Host github-yassami
    HostName github.com
    User git
    IdentityFile ~/.ssh/yassami_deploy
    IdentitiesOnly yes
EOF
ssh-keyscan github.com >> ~/.ssh/known_hosts
git clone git@github-yassami:OWNER/yassami.git /opt/yassami     # OWNER = GitHub account
```

If `/opt/yassami` already exists from an archive, keep its `.env`: `mv /opt/yassami /opt/yassami-old`, clone, then `cp /opt/yassami-old/.env /opt/yassami/`.

**Code: archive (alternative without GitHub):** on the PC `bash deploy/make-release.sh`, then `scp releases\yassami-release.tar.gz root@VPS_IP:/opt/` and `mkdir -p /opt/yassami && tar -xzf /opt/yassami-release.tar.gz -C /opt/yassami`.

**Data (first deployment only, never via GitHub):** the database and photos are private and are not in the repository. On the PC (Git Bash), then PowerShell:

```bash
bash deploy/export-data.sh      # → releases/yassami-data.tar.gz
```
```powershell
scp releases\yassami-data.tar.gz root@VPS_IP:/opt/
```

---

## 2. On the server — install

```bash
cd /opt/yassami
cp .env.production.example .env

# Generate the secrets (application key + database passwords)
sed -i "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|" .env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$(openssl rand -hex 24)|" .env
sed -i "s|^DB_ROOT_PASSWORD=.*|DB_ROOT_PASSWORD=$(openssl rand -hex 24)|" .env
chmod 600 .env
```

Keep `.env` safe: it holds the application key and passwords. Never share it.

### Option A — server dedicated to YASSAMI (ports 80/443 free): Caddy

```bash
touch .use-caddy                     # remembered by deploy/update.sh
docker compose -f docker-compose.prod.yml --profile caddy up -d --build
```

Caddy requests the HTTPS certificate on its own once the DNS points to the server.

### Option B — a Nginx already runs on the server: add a vhost

```bash
docker compose -f docker-compose.prod.yml up -d --build

cp deploy/nginx-host/yassami.ma.conf /etc/nginx/sites-available/yassami.ma
ln -s /etc/nginx/sites-available/yassami.ma /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx            # stop if "nginx -t" reports an error

apt install -y certbot python3-certbot-nginx  # if certbot is missing
certbot --nginx -d yassami.ma -d www.yassami.ma --redirect
```

The other sites on the server are not touched: this only adds a new `server` block for `yassami.ma`.

### Option C — another site's Nginx container owns 80/443 (current VPS: scfderma)

YASSAMI's `web` container joins the scfderma Docker network, and the scfderma Nginx gets two extra `server` blocks for `yassami.ma`. scfderma's own blocks, certificates and containers are not modified.

```bash
cd /opt/yassami
sed -i 's|^PROXY_NETWORK=.*|PROXY_NETWORK=scfderma_scfderma|' .env
. deploy/_compose.sh && $DC up -d --build          # $DC includes docker-compose.proxy.yml
```

Then follow **section 2C** below (DNS check, Nginx blocks, certificate).

#### 2C. Connect yassami.ma to the scfderma Nginx

```bash
CONF=/home/scfderma/docker/nginx/prod-ssl.conf
getent hosts yassami.ma www.yassami.ma               # must show this VPS's IP, otherwise wait for DNS
cp $CONF /root/prod-ssl.conf.bak-$(date +%F)         # backup of the scfderma config

# Stage 1: HTTP block (Let's Encrypt validation)
cat /opt/yassami/deploy/scfderma-proxy/yassami-http.conf >> $CONF
docker exec scfderma-nginx-1 nginx -t && docker exec scfderma-nginx-1 nginx -s reload

# Certificate, issued by the existing certbot container
docker exec scfderma-certbot-1 certbot certonly --webroot -w /var/www/certbot \
  -d yassami.ma -d www.yassami.ma --non-interactive --agree-tos --register-unsafely-without-email

# Stage 2: HTTPS block
cat /opt/yassami/deploy/scfderma-proxy/yassami-https.conf >> $CONF
docker exec scfderma-nginx-1 nginx -t && docker exec scfderma-nginx-1 nginx -s reload

curl -I https://yassami.ma                            # HTTP/2 200
```

Rules for this file:
- **Only append with `cat … >>`.** The file is bind-mounted alone into the container: `sed -i` or some editors replace the file, and the container then keeps the old version.
- **Always run `nginx -t` before `reload`.** If the test fails, nothing is reloaded and the sites keep working on the previous config.
- **To undo:** `cat /root/prod-ssl.conf.bak-DATE > $CONF`, then `nginx -t` and reload.
- **If scfderma is redeployed** and its `prod-ssl.conf` is overwritten (e.g. from its repository), the YASSAMI blocks disappear: append them again, or add them to scfderma's source.
- **Renewal:** the scfderma certbot container renews every certificate, including yassami.ma. So that Nginx picks up renewed certificates, add a daily graceful reload (harmless for both sites):
  `crontab -e` → `30 4 * * * docker exec scfderma-nginx-1 nginx -s reload >/dev/null 2>&1`

If `yassami.ma` returns 502: YASSAMI is stopped (`$DC ps`). The scfderma site is not affected, since the block resolves YASSAMI at request time.

### Check

```bash
. deploy/_compose.sh && $DC ps                       # all "running" / "healthy"
curl -I http://127.0.0.1:8020                        # HTTP/1.1 200
```

---

## 3. First deployment — import the catalog from your PC

Imports the products, photos, categories, settings and the admin account(s) from your PC:

```bash
cd /opt/yassami
bash deploy/import-data.sh /opt/yassami-data.tar.gz
```

The script refuses to run if the production database already contains orders, so real data is never overwritten by mistake.

**Optional cleanup:** the import includes the PC's test order(s) (for example YS-000001). To keep them out of production, cancel them in the admin.

**Admin login:** use the same email and password as on your PC. To create another admin:

```bash
. deploy/_compose.sh && $DC exec -u www-data app php artisan yassami:create-admin
```

Then open **https://yassami.ma/admin**, set prices and stock, and switch on the products.

---

## 4. Daily backups

```bash
crontab -e
# add this line:
0 3 * * * bash /opt/yassami/deploy/backup.sh >> /var/log/yassami-backup.log 2>&1
```

Backups (database + photos) go to `/opt/yassami-backups` and are kept for 14 days. Copy them to another machine from time to time: a backup stored only on the same server does not protect against losing the server.

---

## 5. Updating the site later

On the PC: commit and `git push`. On the server:

```bash
cd /opt/yassami && bash deploy/update.sh            # git pull + backup + rebuild + restart
```

(Archive mode: `bash /opt/yassami/deploy/update.sh /opt/yassami-release.tar.gz`.)

The update script backs up first, then rebuilds and restarts. Migrations run automatically. `.env`, the database and the photos are kept.
Never run `deploy/import-data.sh` again after launch: it is for the first deployment only.

---

## 6. Firewall (recommended)

```bash
ufw allow OpenSSH && ufw allow 80/tcp && ufw allow 443/tcp && ufw enable
```

Check first that SSH stays allowed, or you lose access to the server. MySQL and port 8020 are never exposed anyway (Docker binds them to the server only).

---

## 7. Useful commands

```bash
cd /opt/yassami
. deploy/_compose.sh                   # sets $DC (with the proxy network if configured)
$DC ps                                # status
$DC logs -f --tail=100 app            # PHP / Laravel errors
$DC logs -f --tail=100 web            # access log
$DC exec app tail -n 50 storage/logs/laravel-$(date +%F).log
$DC restart app                       # after editing .env
$DC exec -u www-data app php artisan down   # maintenance mode (…up to reopen)
```

Always run artisan as `www-data` (`exec -u www-data`): a log file created by root would stop the site from writing its logs.
