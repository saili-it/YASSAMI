/**
 * Panier YASSAMI — store Alpine persistant (localStorage).
 *
 * Les prix stockés ici servent UNIQUEMENT à l'affichage. Le serveur recalcule
 * tout (prix, stock, variantes) au moment de la commande.
 */

const STORAGE_KEY = 'yassami.cart.v1';
const MAX_QTY = 10; // doit rester égal à config('yassami.max_quantity_per_line')

/** « 1 299 DH », « 89,50 DH » — doit rester identique à App\Support\Money::format(). */
export function formatMAD(amount) {
    const value = Number(amount) || 0;
    const formatted = new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: Number.isInteger(value) ? 0 : 2,
        maximumFractionDigits: 2,
    }).format(value);
    return `${formatted} DH`;
}

function load() {
    try {
        const items = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '[]');
        return Array.isArray(items) ? items.filter((i) => i && i.productId && i.quantity > 0) : [];
    } catch {
        return [];
    }
}

const lineKey = (productId, size, color) => `${productId}|${size ?? ''}|${color ?? ''}`;

export default function cartStore() {
    return {
        items: load(),
        drawerOpen: false,
        toast: null,
        toastTimer: null,

        init() {
            // Synchronise les onglets ouverts.
            window.addEventListener('storage', (e) => {
                if (e.key === STORAGE_KEY) this.items = load();
            });
        },

        persist() {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(this.items));
            } catch {
                /* navigation privée / quota : le panier reste en mémoire */
            }
        },

        get count() {
            return this.items.reduce((n, i) => n + i.quantity, 0);
        },

        get subtotal() {
            return this.items.reduce((s, i) => s + i.price * i.quantity, 0);
        },

        /** @param {{productId:number, slug:string, name:string, image:?string, price:number, size:?string, color:?string, stock:number}} product */
        add(product, quantity = 1, { openDrawer = true } = {}) {
            const key = lineKey(product.productId, product.size, product.color);
            const existing = this.items.find((i) => i.key === key);
            const limit = Math.min(MAX_QTY, product.stock ?? MAX_QTY);

            if (existing) {
                existing.quantity = Math.min(limit, existing.quantity + quantity);
                Object.assign(existing, { price: product.price, image: product.image, stock: product.stock });
            } else {
                this.items.push({ ...product, key, quantity: Math.min(limit, quantity) });
            }

            this.persist();
            this.notify(`${product.name} ajouté au panier`);
            if (openDrawer) this.drawerOpen = true;
        },

        setQuantity(key, quantity) {
            const item = this.items.find((i) => i.key === key);
            if (!item) return;
            const limit = Math.min(MAX_QTY, item.stock ?? MAX_QTY);
            item.quantity = Math.max(1, Math.min(limit, Number(quantity) || 1));
            this.persist();
        },

        canIncrement(item) {
            return item.quantity < Math.min(MAX_QTY, item.stock ?? MAX_QTY);
        },

        remove(key) {
            this.items = this.items.filter((i) => i.key !== key);
            this.persist();
        },

        clear() {
            this.items = [];
            this.persist();
        },

        notify(message) {
            clearTimeout(this.toastTimer);
            this.toast = message;
            this.toastTimer = setTimeout(() => (this.toast = null), 2800);
        },

        format: formatMAD,
    };
}
