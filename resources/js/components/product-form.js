/**
 * Fiche produit : galerie défilante + sélection taille / couleur / quantité.
 * `product` est injecté par la vue (données serveur, jamais saisies par l'utilisateur).
 * Le formulaire de commande directe (checkoutForm { direct: true }) est imbriqué dans ce composant.
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

    get lineTotal() {
        return product.price * this.quantity;
    },

    // ------------------------------------------------------------ Galerie
    // Bande défilante (scroll-snap) : glisser au doigt, molette/trackpad, flèches ou miniatures.

    /** Met à jour l'image active d'après la position de défilement. */
    syncImage(track) {
        const index = Math.round(track.scrollLeft / track.clientWidth);
        if (index !== this.activeImage) this.activeImage = index;
    },

    goToImage(index) {
        const track = this.$refs.track;
        if (!track) return;
        const last = track.children.length - 1;
        const target = Math.max(0, Math.min(last, index));
        // Défilement animé, sauf si l'utilisateur a demandé moins d'animations.
        const smooth = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        track.scrollTo({ left: target * track.clientWidth, behavior: smooth ? 'smooth' : 'auto' });
        this.activeImage = target;
    },

    // ------------------------------------------------------------ Variantes
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
});
