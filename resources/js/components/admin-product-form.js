/**
 * Formulaire produit (administration) : tailles, couleurs et photos.
 * Les données initiales viennent du serveur (old() en cas d'erreur de validation).
 */
export default ({ sizes, colors, images, maxImages }) => ({
    sizes: [...sizes],
    customSize: '',
    colors: colors.length ? colors.map((c) => ({ ...c })) : [],
    images: images.map((img) => ({ ...img, removed: false })), // existantes
    uploads: [], // nouvelles : { file, url }
    maxImages,
    dragging: false,
    imageError: null,

    // ---------------------------------------------------------- Tailles
    toggleSize(size) {
        this.sizes = this.sizes.includes(size) ? this.sizes.filter((s) => s !== size) : [...this.sizes, size];
    },
    addCustomSize() {
        const value = this.customSize.trim().toUpperCase();
        if (value && !this.sizes.includes(value)) this.sizes.push(value);
        this.customSize = '';
    },

    // --------------------------------------------------------- Couleurs
    addColor(preset = null) {
        this.colors.push(preset ? { ...preset } : { name: '', hex: '#111111' });
    },
    removeColor(index) {
        this.colors.splice(index, 1);
    },

    // ----------------------------------------------------------- Photos
    get keptImages() {
        return this.images.filter((i) => !i.removed);
    },
    get totalImages() {
        return this.keptImages.length + this.uploads.length;
    },
    move(index, delta) {
        const target = index + delta;
        if (target < 0 || target >= this.images.length) return;
        const list = [...this.images];
        [list[index], list[target]] = [list[target], list[index]];
        this.images = list;
    },
    addFiles(fileList) {
        this.imageError = null;
        for (const file of fileList) {
            if (!file.type.startsWith('image/')) continue;
            if (this.totalImages >= this.maxImages) {
                this.imageError = `Maximum ${this.maxImages} photos par produit.`;
                break;
            }
            if (file.size > 8 * 1024 * 1024) {
                this.imageError = `« ${file.name} » dépasse 8 Mo.`;
                continue;
            }
            this.uploads.push({ file, url: URL.createObjectURL(file) });
        }
        this.syncInput();
    },
    removeUpload(index) {
        URL.revokeObjectURL(this.uploads[index].url);
        this.uploads.splice(index, 1);
        this.syncInput();
    },
    /** Recopie la liste `uploads` dans le vrai <input type="file"> envoyé au serveur. */
    syncInput() {
        const transfer = new DataTransfer();
        this.uploads.forEach((u) => transfer.items.add(u.file));
        this.$refs.files.files = transfer.files;
    },
});
