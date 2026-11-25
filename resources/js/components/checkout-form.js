/**
 * Formulaire de commande.
 *   checkoutForm(endpoint)                    → page panier : commande tout le panier
 *   checkoutForm(endpoint, { direct: true })  → fiche produit : commande CE produit seulement,
 *                                               avec la taille / couleur / quantité choisies.
 * En mode direct, le composant est imbriqué dans « productForm » : il lit product, size,
 * color, quantity et validate() de la portée parente (portées Alpine fusionnées).
 * Le serveur valide, recalcule les prix, enregistre la commande et renvoie la page de confirmation.
 */
const CUSTOMER_KEY = 'yassami.customer.v1';

function loadCustomer() {
    try {
        return { name: '', phone: '', city: '', address: '', ...JSON.parse(localStorage.getItem(CUSTOMER_KEY) ?? '{}') };
    } catch {
        return { name: '', phone: '', city: '', address: '' };
    }
}

export default (endpoint, { direct = false } = {}) => ({
    customer: loadCustomer(),
    errors: {},
    globalErrors: [],
    submitting: false,

    fieldError(name) {
        return this.errors[`customer.${name}`]?.[0] ?? null;
    },

    /** Lignes envoyées au serveur (les prix ne sont jamais envoyés : le serveur les relit). */
    lineItems() {
        if (direct) {
            return [{ product_id: this.product.id, quantity: this.quantity, size: this.size, color: this.color }];
        }
        return this.$store.cart.items.map((i) => ({
            product_id: i.productId,
            quantity: i.quantity,
            size: i.size,
            color: i.color,
        }));
    },

    async submit() {
        if (this.submitting) return;

        this.errors = {};
        this.globalErrors = [];

        if (direct) {
            // Taille / couleur manquante : message sous les sélecteurs (productForm.error).
            this.error = this.validate();
            if (this.error) {
                document.querySelector('[data-variants]')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
        } else if (!this.$store.cart.items.length) {
            return;
        }

        this.submitting = true;

        try {
            localStorage.setItem(CUSTOMER_KEY, JSON.stringify(this.customer));
        } catch {
            /* ignore */
        }

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ customer: this.customer, items: this.lineItems() }),
            });

            const data = await response.json().catch(() => ({}));

            if (response.ok && data.redirect) {
                // Panier vidé seulement s'il vient d'être commandé (une commande directe ne touche pas au panier).
                if (!direct) this.$store.cart.clear();
                window.location.href = data.redirect;
                return;
            }

            if (response.status === 422) {
                this.errors = data.errors ?? {};
                this.globalErrors = Object.entries(this.errors)
                    .filter(([key]) => !key.startsWith('customer.'))
                    .flatMap(([, messages]) => messages);
                this.$nextTick(() => this.$el.querySelector('[data-invalid="true"]')?.focus());
            } else if (response.status === 419) {
                this.globalErrors = ['Votre session a expiré. Rechargez la page puis réessayez.'];
            } else if (response.status === 429) {
                this.globalErrors = ['Trop de tentatives. Patientez une minute puis réessayez.'];
            } else {
                this.globalErrors = [data.message ?? 'Une erreur est survenue. Réessayez dans un instant.'];
            }
        } catch {
            this.globalErrors = ['Connexion impossible. Vérifiez votre réseau puis réessayez.'];
        } finally {
            this.submitting = false;
        }
    },
});
