import Index from './pages/Index.vue';
import PromoBarPreviewFieldtype from './components/PromoBarPreviewFieldtype.vue';

Statamic.booting(() => {
    Statamic.$inertia.register('promo-bar::Index', Index);
    Statamic.$components.register('promo_bar_preview-fieldtype', PromoBarPreviewFieldtype);
});
