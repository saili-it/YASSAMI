# CLAUDE.md — YASSAMI

Permanent specification and development guide for the YASSAMI e-commerce platform.
**Keep this file up to date** whenever the architecture, conventions or business rules change.

---

## 1. Project overview

| | |
|---|---|
| Brand | **YASSAMI** — premium sportswear, sports clothing and accessories |
| Market | Morocco |
| Currency | MAD, displayed as `DH` (e.g. `1 299 DH`) |
| Site language | French (all UI text, URLs, validation messages) |
| Ordering | **Orders are placed and saved on the platform** and processed in the admin (decision 2026-11-18, replaces WhatsApp ordering). No online payment gateway, ever |
| Reference for UX inspiration only | https://adrifit.ma/ (never copy its branding or design) |

### What the site does NOT do
- No online payment, no customer accounts, no customer-facing order tracking (yet).
- No return policy or delivery delays are shown: they are **not known** ("confirmed with the customer").
- **Delivery fee (since 2026-11-21):** one fixed price for all of Morocco, set by the admin in `/admin/parametres` (setting `delivery_fee`, `App\Support\Delivery`). Empty = "À confirmer" (previous behaviour), `0` = "Gratuite", otherwise added to the total in the cart, checkout and confirmation. Never invent this price.

---

## 2. Business requirements

### Customer journey (critical path)
1. Browse products (home, shop, category, search).
2. Select **size, color, quantity** on the product page.
3. Add to cart (persisted in `localStorage`, survives refreshes).
4. Review the cart (`/panier`).
5. Enter **full name, phone, city, delivery address**.
6. Click **"Valider ma commande"** → the server validates, saves the order, reserves stock → the cart is emptied and the browser goes to `/commande/confirmation` (order number `YS-000123`, recap, "notre équipe vous contacte…").
7. The admin processes the order in `/admin/commandes` (calls or WhatsApps the customer, sets the status and delivery fee).

**Direct order from the product page (since 2026-11-25):** the product page embeds the order form ("Commander maintenant" section, button "Passer la commande") under the size/color/quantity selectors, so a single product can be ordered without the cart. It is `checkoutForm(endpoint, { direct: true })` nested inside `productForm` (Alpine scopes merge: it reads `product`, `size`, `color`, `quantity`, `validate()`), posts the same `POST /commande` with one line, and **does not touch the cart**. For the same reason the confirmation page no longer clears the cart; only the cart checkout clears it. "Ou ajouter au panier" stays as a secondary button. The customer fields are the shared partial `pages/partials/checkout-fields.blade.php` (cart page + product page).

**Product gallery:** horizontal scroll-snap strip (`[data-gallery]`, swipe on touch, trackpad, ←/→ keys, arrows on `sm+`, thumbnails, dots on mobile, `n / total` counter). `productForm.syncImage()` follows the scroll and `goToImage()` scrolls smoothly (instant when `prefers-reduced-motion`).

### Orders
- `App\Services\OrderService` is the **only** place that creates orders or changes their status.
- `place()`: inside a transaction, products are locked (`lockForUpdate`), stock is re-checked and decremented, prices come from the DB (`effective_price`), lines are snapshotted (`order_items`: name, SKU, size, color, unit price, quantity, line total) so later product edits/deletions never change past orders. Reference = `YS-` + zero-padded id.
- Statuses (`App\Enums\OrderStatus`, stored as French values): `nouvelle` → `confirmee` → `expediee` → `livree`, or `annulee`. Cancelling **restores stock**; reactivating a cancelled order reserves it again (refused with a flash error if stock is insufficient).
- `orders.delivery_fee` is **snapshotted at placement** from `Delivery::fee()` (null when the setting is empty = "À confirmer"); changing the setting never alters past orders, and the admin can still adjust it per order. `total` = subtotal + delivery fee. `admin_note` is internal only.
- The cart display reads the fee from `<meta name="yassami-delivery-fee">` (layout) into the Alpine store (`deliveryFee`, `deliveryLabel`, `total`). Display only: the server fee is authoritative.
- Customer phone is stored as `06XXXXXXXX` (`App\Support\Phone` handles formatting and the `212…` wa.me format).
- The confirmation page shows only the order id kept in the visitor's session — never an id from the URL.

### Product fields
name, slug, description, price, optional `sale_price`, multiple images, category (one per product), sizes (JSON list), colors (JSON list of `{name, hex}`), stock quantity (per product, not per variant), SKU, featured flag, active flag.

### Category fields
name, slug, description, image, active flag, display order (`sort_order`).

### Admin (protected) — `/admin`
Custom Blade UI in the brand style (decision 2026-11-10: **custom, not Filament**).
- Login `/admin/connexion` (only `users.is_admin = true`, 5 attempts/min per email+IP, plus a route throttle of 10/min). Create or promote an admin with `docker compose exec app php artisan yassami:create-admin` (12+ character password, no default account).
- Dashboard: banner for new orders to process, total products, total categories, active products, out of stock, latest orders, plus out-of-stock, low-stock and latest product lists.
- Orders (`/admin/commandes`, added 2026-11-18): list with status tabs (with counts) and search (order number, name, phone in any format), detail page with items, customer, "Appeler" / "Écrire sur WhatsApp" buttons, and a form to set status, delivery fee and internal note. The sidebar shows a red badge with the number of `nouvelle` orders. Orders are never deleted from the admin (cancel instead).
- Categories: CRUD, image (optional), slug auto-generated if empty, active toggle, display order. **Deletion is refused if the category has products** (flash error).
- Products: a product cannot be activated (form or quick toggle) while its price is 0, so a draft can never be ordered for free. CRUD, filters (search, category, status), quick active/featured toggles, up to 10 photos (upload, drag & drop, reorder, delete), sizes (presets + custom), colors (name + color picker + presets), price/sale price with live discount preview, stock.
- Uploaded images are resized to 1600 px max, EXIF-rotated and converted to WebP (`App\Services\ImageStorage`). Files are stored at `storage/app/public/products/{id}/` and `categories/`. Deleting a product or image deletes the files.
- Destructive actions always go through a confirmation dialog (`<x-admin.confirm-delete>`).
- **Floating WhatsApp button** (`<x-whatsapp-float>`, in `layouts/app` only, never in the admin; added 2026-11-21): square `bg-whatsapp` button bottom-right (z-45, below the menu/drawer, hidden while they are open), shown only when the WhatsApp number is set in Paramètres. Prefilled message: generic by default; on product pages, the layout prop `whatsapp-message` carries the product name, SKU and URL.
- Settings (`/admin/parametres`, added 2026-11-11): **delivery fee** (fixed price for all of Morocco, accepts `35`, `35,50`, `35 DH`; 0 = free; empty = to confirm), WhatsApp contact number (optional, only for the footer "Nous écrire sur WhatsApp" link since 2026-11-18), contact phone/email/address, Instagram/Facebook/TikTok URLs. The WhatsApp number accepts any Moroccan format and is normalized to `212XXXXXXXXX`; the page has a "Tester" link.

### Business information — NEVER invent
WhatsApp number, phone, email, address, social accounts, delivery fees/delays, return policy, real prices.
They are managed by the admin in `/admin/parametres` (table `settings`, key → value). `App\Services\SiteSettings` (run by the `ApplySiteSettings` web middleware, one query per request) writes them over `config('yassami.*')`, so the rest of the code only ever reads `config('yassami.*')`. `.env` values are the initial defaults only: once a field is saved in the admin, the admin value wins, even if empty. Empty values are hidden on the site.

---

## 3. Technical stack

| Layer | Technology |
|---|---|
| Backend | **Laravel 13** (PHP 8.4) — chosen by the client, do not switch stacks |
| Database | **MySQL 8.0** (utf8mb4) |
| Frontend | Blade (anonymous components) + **Tailwind CSS 4** + **Alpine.js 3**, bundled by **Vite 8** |
| Fonts | Barlow Condensed (display) + Inter (body), self-hosted at build time via `laravel-vite-plugin/fonts` + `@fonts` |
| Runtime | **Docker only** — PHP/Composer/Node are NOT installed on the host |
| Tests | PHPUnit (feature tests) on in-memory SQLite |
| Code style | Laravel Pint |

No Laravel Boost, no Filament, no Livewire, no SPA framework. Keep it that way unless explicitly decided.

---

## 4. Running the project

```bash
docker compose up -d                       # app (php-fpm) + nginx + mysql → http://localhost:8020
docker compose --profile dev up -d vite    # optional: Vite dev server with HMR (port 5180)

# Assets (production build, written to public/build)
docker compose run --rm --no-deps vite npm run build

# Artisan / Composer / tests — always inside the container
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed      # demo catalog (local env only)
docker compose exec app php artisan test
docker compose exec app vendor/bin/pint
docker compose exec app composer require <pkg>
```

| Service | Host port |
|---|---|
| Website (nginx) | 8020 |
| MySQL | 3310 (user/pass/db: `yassami`) |
| Vite dev server | 5180 |

Ports 3307/5434/5435/8000/5173 belong to the developer's other projects — don't reuse them.

### Production (VPS, yassami.ma) — full guide in `DEPLOY.md`
- `docker-compose.prod.yml` (project name **`yassami-prod`**, never `yassami`, so it can never share the dev volumes) + `docker/prod/` (multi-stage `Dockerfile` with targets `app` and `web`, `entrypoint.sh`, `php.ini`, `nginx.conf`, `Caddyfile`).
- The image bakes in the code, `composer --no-dev` and the Vite build. The entrypoint runs `migrate --force` and `optimize` **as root** (on the server `.env` is `chmod 600` root-only: as `www-data` the cached config had an empty APP_KEY → 500, seen on 2026-11-21), then `chown -R www-data storage bootstrap/cache`. Once config is cached, php-fpm never reads `.env`. Run later artisan commands with `exec -u www-data` (never `config:clear` as www-data).
- `web` listens on `127.0.0.1:${HTTP_PORT:-8020}` only and MySQL has no port. HTTPS comes from one of three options: the `caddy` service (profile `caddy`, when ports 80/443 are free), the host Nginx (`deploy/nginx-host/yassami.ma.conf` + certbot), or **an existing Docker proxy (current VPS)**. `bootstrap/app.php` trusts the proxy (`trustProxies(at: '*')`).
- **Current VPS (shared, 2026-11-21):** ports 80/443 belong to the `scfderma-nginx-1` container (another project in `/home/scfderma`, network `scfderma_scfderma`, certbot container `scfderma-certbot-1`, config `/home/scfderma/docker/nginx/prod-ssl.conf` bind-mounted as a single file).
  - YASSAMI uses `PROXY_NETWORK=scfderma_scfderma` → `docker-compose.proxy.yml` puts only `web` on that network under the alias `yassami-web`. The blocks `deploy/scfderma-proxy/yassami-{http,https}.conf` are appended with `cat >>` (never `sed -i`: single-file bind mount).
  - The HTTPS block resolves `yassami-web` at request time (`resolver 127.0.0.11` + variable), so a stopped YASSAMI never prevents the scfderma Nginx from starting or reloading (verified in a local simulation).
  - **Never name the PHP upstream `app` in prod**: `web` sits on two networks and `app` resolved to scfderma's PHP container in the simulation. It is `yassami-app` (compose alias).
- **Git (since 2026-11-21):** private GitHub repository, branch `main`. The server clones it read-only via a deploy key (SSH host alias `github-yassami`); `deploy/update.sh` with no argument does `git pull --ff-only`, backup, build, up. Never commit `.env`, `releases/` (DB dumps), `backups/` or `products images/` (all git-ignored). Data goes to the server only via `export-data.sh` + scp.
- Always build `$DC` via `. deploy/_compose.sh` (adds the proxy override when `PROXY_NETWORK` is set, and the `caddy` profile when `.use-caddy` exists).
- Volumes: `mysql-data`, `uploads` (`storage/app/public`, mounted read-only in `web`), `logs`.
- Scripts in `deploy/`: `make-release.sh` and `export-data.sh` (PC, Git Bash, output to `releases/`, git-ignored because it contains the DB); `import-data.sh` (server, first deployment only, refuses if orders exist unless `--force`); `update.sh` (backup, extract, build, up); `backup.sh` (daily cron, 14 days in `/opt/yassami-backups`).
- `.env.production.example` → `.env` on the server (`APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `QUEUE_CONNECTION=sync`, `LOG_CHANNEL=daily`). Passwords alphanumeric only (compose interpolation).
- Verified locally on 2026-11-21 with an isolated `yassami-prodtest` stack: build, migrations, data import (12 products, 26 photos), photos served, order YS-000002 placed, admin login, stock decremented, `.env` / `/storage/*.php` blocked, https URLs behind proxy.

### Docker rules (learned the hard way)
- `vendor/`, `storage/framework`, `bootstrap/cache` and `node_modules` are **named volumes** (Windows bind mounts are too slow). The entrypoint runs `composer install` if `vendor/` is empty.
- The `app` service must **never** receive `APP_*` or `DB_*` environment variables from docker-compose. They live in `.env`. Docker env vars land in `$_SERVER`, which Laravel reads before `$_ENV`, so they would override `phpunit.xml` and `RefreshDatabase` would **wipe the dev database** (this happened once on 2026-11-09).
- `tests/TestCase.php` refuses to boot unless the connection is `sqlite` / `:memory:` in the `testing` env. **Never remove this guard.**

---

## 5. Architecture

Single Laravel application, server-rendered (SEO-friendly). No separate API backend.

```
Browser ──GET──▶ Controllers ──▶ Eloquent ──▶ MySQL
   │                │
   │                └─▶ Blade pages + components (HTML, JSON-LD, meta)
   │
   ├─ Alpine store "cart" (localStorage) — display only
   └─POST /commande (JSON) ──▶ StoreOrderRequest (validation, stock, variants)
                            ──▶ OrderService::place (lock, DB prices, stock, orders + order_items)
                            ◀── 201 { reference, redirect } → cart cleared → /commande/confirmation

Admin ──▶ /admin/commandes ──▶ OrderService::changeStatus (stock restored/reserved)
```

**Security principle:** prices in the browser are for display only. The server re-reads every product, re-checks active status / variant / stock, and uses database prices for the order.

### Routes (French slugs)
| Method | URI | Name | Purpose |
|---|---|---|---|
| GET | `/` | `home` | Homepage |
| GET | `/boutique` | `shop.index` | All products. Query: `q`, `tri` (`nouveautes`\|`prix-asc`\|`prix-desc`), `promo=1`, `page` |
| GET | `/boutique/{category:slug}` | `shop.category` | Category listing (same controller) |
| GET | `/produit/{product:slug}` | `products.show` | Product detail |
| GET | `/panier` | `cart` | Cart + checkout form |
| POST | `/commande` | `orders.store` | JSON, CSRF-protected, `throttle:6,1` (each order reserves stock) |
| GET | `/commande/confirmation` | `orders.confirmation` | Last order of this session (`noindex`), else redirect to the shop |

### Admin routes (prefix `/admin`, name `admin.`; middleware `admin` = `App\Http\Middleware\EnsureUserIsAdmin`)
| Method | URI | Name |
|---|---|---|
| GET/POST | `/admin/connexion` | `admin.login` / `admin.login.store` (guest only) |
| POST | `/admin/deconnexion` | `admin.logout` |
| GET | `/admin` | `admin.dashboard` |
| GET/POST | `/admin/categories`, `/admin/categories/nouvelle` | `admin.categories.index/create/store` |
| GET/PUT/DELETE | `/admin/categories/{id}/modifier`, `/admin/categories/{id}` | `admin.categories.edit/update/destroy` |
| GET/POST | `/admin/produits`, `/admin/produits/nouveau` | `admin.products.index/create/store` (index query: `q`, `categorie`, `statut`) |
| GET/PUT/DELETE | `/admin/produits/{id}/modifier`, `/admin/produits/{id}` | `admin.products.edit/update/destroy` |
| PATCH | `/admin/produits/{id}/basculer/{is_active\|is_featured}` | `admin.products.toggle` |
| GET | `/admin/commandes`, `/admin/commandes/{id}` | `admin.orders.index/show` (index query: `statut`, `q`) |
| PUT | `/admin/commandes/{id}` | `admin.orders.update` (status, delivery_fee, admin_note) |
| GET/PUT | `/admin/parametres` | `admin.settings.edit` / `admin.settings.update` |

Admin routes bind models **by id** (`{product:id}`), because the slug can change while editing. Public routes bind by slug.

---

## 6. Folder structure

```
app/
  Console/Commands/        CreateAdmin (yassami:create-admin)
  Enums/                   OrderStatus (labels, badge classes, holdsStock)
  Http/Controllers/        HomeController, ShopController, ProductController, OrderController
  Http/Controllers/Admin/  AuthController, DashboardController, OrderController, CategoryController, ProductController, SettingsController
  Http/Middleware/         EnsureUserIsAdmin (alias "admin"), ApplySiteSettings (web group)
  Http/Requests/           StoreOrderRequest · Admin/{LoginRequest, OrderRequest, CategoryRequest, ProductRequest, SettingsRequest}
  Models/                  Category, Product, ProductImage, Order, OrderItem, Setting, User (is_admin, default false)
  Services/                OrderService, ImageStorage (resize/WebP), ProductImageService (sync photos), SiteSettings
  Support/                 Money (MAD formatting + mad() helper), Slug (unique slugs), Phone (Moroccan numbers), Delivery (fixed delivery fee + label)
lang/fr/                   validation.php, auth.php, pagination.php
config/yassami.php         ALL business settings (single source of truth)
database/
  migrations/              2026_11_09_* = YASSAMI schema
  seeders/DemoCatalogSeeder.php   FAKE demo data — local only, and skipped by DatabaseSeeder as soon as any product exists
products images/           Source photos supplied by the client (WhatsApp exports), imported 2026-11-18
docker/                    entrypoint, nginx, php.ini, php-fpm pool (dev) · docker/prod/ (production image + config)
deploy/                    make-release, export-data, import-data, update, backup scripts + nginx-host vhost
DEPLOY.md                  Step-by-step VPS deployment guide
resources/
  css/app.css              Design tokens (@theme) + shared component classes
  js/app.js                Alpine bootstrap
  js/cart.js               Cart store (localStorage) + formatMAD()
  js/components/           product-form.js, checkout-form.js, admin-product-form.js (Alpine.data)
  views/components/        layouts/{app,admin}, logo, icon, price, product-card, product-media, section-heading
  views/components/admin/  flash, toggle, confirm-delete, page-header, order-status
  views/layouts/partials/  header, footer, mobile-menu, cart-drawer
  views/pages/             home, shop, product, cart, order-confirmation · partials/checkout-fields (shared customer fields)
  views/admin/             auth/login, dashboard, settings, orders/{index,show}, categories/{index,form}, products/{index,form}, partials/
public/images/brand/       Logo variants (logo.png, logo-light.png, mark-light.png), favicons, og-image.jpg
resources/brand/           Original official logo file (source for the variants)
tests/Feature/             StorefrontTest, OrderTest, Admin/{AdminAuthTest, AdminCatalogTest, AdminOrderTest, AdminSettingsTest}
```

---

## 7. Coding conventions

- PHP 8.4, Laravel 13 conventions: model attributes via `#[Fillable([...])]`, `casts()` method, `Attribute` accessors.
- Controllers stay thin. Validation goes in Form Requests. Business logic goes in `app/Services`.
- Business settings: `config('yassami.*')` only. **Never call `env()` outside `config/`.**
- Money formatting: `mad($amount)` in PHP/Blade, `formatMAD()` / `$store.cart.format()` in JS. Both must produce the same output.
- `Model::shouldBeStrict()` is on outside production. Always eager-load (`with(['images', 'category'])`) and never access unselected columns.
- Code comments in French (team language). Identifiers in English.
- User-facing text in French, with typographic apostrophes (`’`) and accents.
- Run `vendor/bin/pint` and `php artisan test` before reporting work as done.
- No commit, push, or deploy without explicit request.

---

## 8. Database conventions

- Tables plural snake_case. FK `xxx_id` with `constrained()`.
- `products.category_id` → `restrictOnDelete` (a category with products cannot be deleted; the admin must show a clear message). `product_images.product_id` → `cascadeOnDelete`.
- Money: `decimal(10,2)` in MAD. `sale_price` is nullable and only counts if `< price` (`has_discount`).
- Flags: `is_active`, `is_featured`. Ordering: `sort_order` (unsigned int).
- Slugs and SKUs are unique. Routes bind by slug (`getRouteKeyName()`).
- Image paths are stored **relative to the `public` disk** (`storage/app/public`, served via `/storage`).
- "Visible" product = `products.is_active` **and** `categories.is_active` → `Product::visible()` scope. Use it for every public query.
- New schema changes = new migrations. Never edit an already-run migration. **No destructive DB command (`migrate:fresh`, `db:wipe`, dropping columns/tables) without approval.**

---

## 9. API conventions

- The only JSON endpoint today is `POST /commande`.
- Request body: `{ customer: {name, phone, city, address}, items: [{product_id, quantity, size, color}] }`. Any `price` sent by the client is ignored.
- Responses: `201 {reference, redirect}` · `422 {message, errors}` (Laravel format, French messages, keys `customer.*`, `items.N`, `items`) · `419` CSRF expired · `429` throttled.
- Phone regex (Morocco): `^(?:\+212|00212|0)[5-7]\d{8}$` after removing spaces, dots, dashes, and parentheses.
- Future admin endpoints: classic Laravel web forms (POST/PUT/DELETE + redirects + flash messages), not a JSON API, unless a real need appears.

---

## 10. UI/UX guidelines

### Identity
Modern, premium, sporty, minimalist. Bold condensed italic headlines, strong hierarchy, generous whitespace, sharp corners (only form fields are slightly rounded).

### Design tokens (`resources/css/app.css` → `@theme`)
| Token | Value | Use |
|---|---|---|
| `ink` | `#0e0e10` | text, dark surfaces, primary buttons |
| `paper` | `#f5f4f0` | page background |
| `bone` | `#ebe9e3` | light surfaces, image placeholders |
| `line` | `#dedbd3` | borders |
| `muted` | `#6c6a65` | secondary text |
| `accent` | `#e2331b` | brand signature, discounts, promotions. The logo is monochrome, so this red is a site accent (to confirm with the client) |
| `whatsapp` | `#1da851` | **reserved for WhatsApp actions** (floating button, footer contact link, admin "Écrire sur WhatsApp"). Ordering buttons use `btn-primary` |
| `success` / `warning` | `#1e7a4c` / `#b26a00` | stock status |
| `font-display` | Barlow Condensed 600–800 (+ italic) | `.display` headlines (uppercase) |
| `font-sans` | Inter 400–700 | body text |

Shared classes: `.container-brand`, `.display`, `.eyebrow`, `.btn` + `.btn-{sm,md,lg}` + `.btn-{primary,accent,light,outline,outline-light,whatsapp,ghost}`, `.field`, `.field-label`, `.field-error`, `.field-invalid`, `.link-underline`, `.text-outline`, `.scroll-rail`.
Never hard-code a hex color in a view (exception: product color swatches from the database).

### Rules
- **Mobile-first.** Test at 390px and 1440px. 16px side gutter on mobile. No horizontal page scroll.
- Hero: full-bleed photo (right 62% on desktop with a left fade to `ink`; full-width at 60% opacity on mobile). It is served via `<picture>` with WebP at two sizes plus a JPG fallback, and `fetchpriority="high"`.
- Product images: always 4:5 via `<x-product-media>`. Without photos, a tinted placeholder shows "Photo à venir".
- Discounts: red `−X%` badge + sale price in accent + struck-through original price.
- Every list needs loading/empty/error states. Every button must do something (no dead placeholders).
- Icons: inline SVG via `<x-icon name="…">` (Lucide paths). **No emoji icons.**
- Animations: subtle (`animate-fade-up`, hover transitions, marquee). `prefers-reduced-motion` is respected globally.
- No generic dashboard templates, excessive gradients, or overcrowded layouts.
- Accessibility: skip link, visible focus ring, `aria-*` on drawers/menus/radio-like buttons, labelled inputs.

### Logo
Official logo received 2026-11-11: a double check mark above the "YASSAMI®" wordmark, monochrome. The original (white on black, 953×421) is kept in `resources/brand/yassami-logo-original.png`.

`<x-logo tone="dark|light" class="h-…">` is the **only** way to render it. Set **only the height**; the width follows the 728×377 ratio, and `object-contain` prevents any distortion even inside flex containers.
- `public/images/brand/logo.png`: black (`#0e0e10`) on transparent, for light backgrounds (`tone="dark"`, the default).
- `public/images/brand/logo-light.png`: white on transparent, for dark backgrounds (`tone="light"`).
- `mark-light.png`: the double check alone. `favicon-32.png`, `apple-touch-icon.png` (180), `icon-512.png` and `public/favicon.ico` are white marks on an `ink` square. `og-image.jpg` (1200×630) is the default social share image.
- Current heights: header `h-11 lg:h-[3.25rem]`, mobile menu `h-11`, footer `h-20`, admin sidebar `h-10`, admin mobile bar `h-9`, login `h-24`/`h-16`.
- The variants are generated from the original by converting luminance to alpha (PHP GD). Regenerate them the same way if the source changes, never by hand-stretching.

---

## 11. Security rules

- No secrets in the repo. `.env` is git-ignored. `.env.example` holds keys only.
- CSRF on every POST (JS sends `X-CSRF-TOKEN` from `<meta name="csrf-token">`).
- Server-side validation of everything. Never trust cart prices from the client.
- `users.is_admin` is **not** mass-assignable (set it with `forceFill`). No default admin account or password: admins are created with `php artisan yassami:create-admin`.
- Admin pages send `noindex, nofollow`. Admin form requests also check `is_admin` in `authorize()`.
- Admin uploads: validate MIME + size, store on the `public` disk with generated names, never trust the original filename.
- Rate-limit public POST endpoints (`throttle`). Add login throttling for the admin.
- Escape all output (`{{ }}`). `{!! !!}` is allowed only for trusted static SVG paths and `json_encode(..., JSON_HEX_TAG)` JSON-LD.
- `DemoCatalogSeeder` only runs in `local`. Never seed fake products in production.

---

## 12. Status & roadmap

### Done (Phase 0–3, 2026-11-09)
Docker environment, Laravel 13 skeleton, schema (categories, products, product_images, users.is_admin), design system, layout (header, mobile menu, search, cart drawer, footer), homepage, shop (filters/search/sort/pagination), product page (gallery, variants, JSON-LD), persistent cart, WhatsApp checkout (server-validated), demo catalog, 10 feature tests.

### Done (2026-11-10)
Admin area: authentication + `yassami:create-admin`, dashboard, category CRUD, product CRUD with optimized multi-photo management, French validation messages (`lang/fr`), 14 admin feature tests (24 total).

### Next
1. ~~Admin area~~ (done).
2. ~~Official logo~~ (done 2026-11-11). Confirm the red accent with the client.
3. ~~Settings managed from the admin~~ (done 2026-11-11, `/admin/parametres`, 6 tests, 30 total). The client still has to enter the real WhatsApp number, contact details and socials there.
4. 404/500 pages in brand style, `sitemap.xml` + `robots.txt`.
5. ~~Orders saved on the platform + admin order management~~ (done 2026-11-18, replaces WhatsApp ordering; 38 tests). Possible follow-ups: email/notification to the admin on new orders, real "Meilleures ventes" from `order_items`, customer-facing order tracking.
6. ~~Production Docker setup~~ (done 2026-11-21, `DEPLOY.md`). Deployment to the VPS is run by the client on the server following `DEPLOY.md` (Claude has no server access). Pending: client to confirm which HTTPS option applies (ports 80/443 free → Caddy, else host Nginx).

### Placeholders to replace before launch
- WhatsApp contact number (optional), contact info, social URLs: enter them in `/admin/parametres`.
- Accent color `#e2331b` (the official logo is black and white, so the red is a site accent, not a logo color; confirm with the client).
- Homepage hero photo `public/images/hero/hero-training.{jpg,webp}` + `hero-training-mobile.webp`: stock photo by Anastase Maragos (https://unsplash.com/photos/9dzWZQWZMdE), Unsplash License (free commercial use, no attribution required). Replace it with a YASSAMI campaign visual wearing the brand's products. Any stock image must have no visible third-party logos, flags or brand marks.
- Real catalog imported 2026-11-18 (demo catalog deleted with the client's approval): categories Shorts, Gants de boxe, Protège-tibias; 12 products (SKU `SHO-001..003`, `GNT-001..005`, `TIB-001..004`), 26 photos. Brand names (Venum, Nike, Under Armour) are in product names at the client's request; model names are used only when printed on the product ("Elite" = "ELITE PERFORMANCE" print). **All 12 are inactive drafts with price 0 and stock 0**: the client must enter price + stock and activate them. Sizes (shorts S–XXL, gloves 10–16 oz, shin guards S–XL) and color hex values were set as reasonable defaults and must be checked by the client. Descriptions only state what is visible on the photos.
- "Meilleures ventes" = products flagged `is_featured` (no sales data yet).
