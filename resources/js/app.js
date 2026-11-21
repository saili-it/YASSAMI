import Alpine from 'alpinejs';
import cartStore from './cart';
import productForm from './components/product-form';
import checkoutForm from './components/checkout-form';
import adminProductForm from './components/admin-product-form';

window.Alpine = Alpine;

Alpine.store('cart', cartStore());
Alpine.data('productForm', productForm);
Alpine.data('checkoutForm', checkoutForm);
Alpine.data('adminProductForm', adminProductForm);

Alpine.start();
