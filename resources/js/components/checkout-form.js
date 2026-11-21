/**
 * Formulaire de commande (page panier).
 * Envoie le panier au serveur, qui valide, recalcule les prix, enregistre la commande
 * et renvoie l'adresse de la page de confirmation.
 */
const CUSTOMER_KEY = 'yassami.customer.v1';

function loadCustomer() {
    try {
        return { name: '', phone: '', city: '', address: '', ...JSON.parse(localStorage.getItem(CUSTOMER_KEY) ?? '{}') };
    } catch {
        return { name: '', phone: '', city: '', address: '' };
    }
}

export default (endpoint) => ({
    customer: loadCustomer(),
    errors: {},
    globalErrors: [],
    submitting: false,

    fieldError(name) {
        return this.errors[`customer.${name}`]?.[0] ?? null;
    },

    async submit() {
        const cart = this.$store.cart;
        if (!cart.items.length || this.submitting) return;

        this.submitting = true;
        this.errors = {};
        this.globalErrors = [];

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
                body: JSON.stringify({
                    customer: this.customer,
                    items: cart.items.map((i) => ({
                        product_id: i.productId,
                        quantity: i.quantity,
                        size: i.size,
                        color: i.color,
                    })),
                }),
            });

            const data = await response.json().catch(() => ({}));

            if (response.ok && data.redirect) {
                // Commande enregistrée : on vide le panier puis on affiche la confirmation.
                cart.clear();
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
