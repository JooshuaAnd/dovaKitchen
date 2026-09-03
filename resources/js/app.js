import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

// Route helper for frontend
const routes = {
    'home': '/',
    'login': '/login',
    'login.post': '/login',
    'register': '/register',
    'register.post': '/register',
    'logout': '/logout',
    'dietary-setup': '/dietary-setup',
    'dietary-profile.update': '/dietary-profile',
    'catalog': '/catalog',
    'birthday-reward.claim': '/birthday-reward/claim',
    'checkout.store': '/checkout',
    'history.index': '/history',
    'profile.edit': '/profile',
    'profile.update': '/profile',
    'profile.password': '/profile/password',
    
    // Admin Routes
    'admin.dashboard': '/admin/menus',
    'admin.menus.index': '/admin/menus',
    'admin.menus.create': '/admin/menus/create',
    'admin.menus.store': '/admin/menus',
    'admin.weekly-management.index': '/admin/weekly-management',
    'admin.weekly-management.update': '/admin/weekly-management',
    'admin.users.index': '/admin/users',
    'admin.users.destroy': '/admin/users',
    'admin.orders.index': '/admin/orders',
    'admin.kitchen-report.index': '/admin/kitchen-report',
};

window.route = (name, params) => {
    if (!name) {
        const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';
        const found = Object.keys(routes).find(key => routes[key] === currentPath);
        return {
            current: found || ''
        };
    }
    
    if (name === 'orders.show') return `/orders/${params}`;
    if (name === 'payment-proof.store') return `/orders/${params}/payment-proof`;
    if (name === 'admin.menus.edit') return `/admin/menus/${params}/edit`;
    if (name === 'admin.menus.update') return `/admin/menus/${params}`;
    if (name === 'admin.menus.destroy') return `/admin/menus/${params}`;
    if (name === 'admin.users.destroy') return `/admin/users/${params}`;
    if (name === 'admin.orders.verify') return `/admin/orders/${params}/verify`;
    
    let path = routes[name] || (typeof name === 'string' && name.startsWith('/') ? name : '/');
    
    if (params && typeof params === 'object') {
        const qs = new URLSearchParams(params).toString();
        if (qs) {
            path += `?${qs}`;
        }
    }
    
    return path;
};

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mixin({ methods: { route: window.route } })
            .mount(el);
    },
});
