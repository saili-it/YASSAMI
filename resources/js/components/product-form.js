/**
 * Fiche produit : galerie + sélection taille / couleur / quantité.
 * `product` est injecté par la vue (données serveur, jamais saisies par l'utilisateur).
 */
export default (product) => ({
    product,
    activeImage: 0,
    size: product.sizes.length === 1 ? product.sizes[0] : null,
    color: product.colors.length === 1 ? product.colors[0].name : null,
    quantity: 1,
    error: null,

    get maxQuantity() {
        return Math.max(1, Math.min(10, product.stock));
    },

    validate() {
        if (product.stock <= 0) return 'Ce produit est en rupture de stock.';
        if (product.sizes.length && !this.size) return 'Choisissez une taille.';
        if (product.colors.length && !this.color) return 'Choisissez une couleur.';
        return null;
    },

    payload() {
        return {
            productId: product.id,
            slug: product.slug,
            name: product.name,
            image: product.image,
            price: product.price,
            stock: product.stock,
            size: this.size,
            color: this.color,
        };
    },

    addToCart() {
        this.error = this.validate();
        if (this.error) return;
        this.$store.cart.add(this.payload(), this.quantity);
    },

    orderNow() {
        this.error = this.validate();
        if (this.error) return;
        this.$store.cart.add(this.payload(), this.quantity, { openDrawer: false });
        window.location.href = product.cartUrl + '#commande';
    },
});
