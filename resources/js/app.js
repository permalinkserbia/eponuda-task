import './bootstrap';
import { createApp } from 'vue';
import TelevisionsPage from './pages/TelevisionsPage.vue';
import TvSprejemnikiPage from './pages/TvSprejemnikiPage.vue';

const path = window.location.pathname;

if (path === '/televisions' || path === '/tv-sprejemniki') {
    const app = createApp({});

    if (path === '/televisions') {
        app.component('TelevisionsPage', TelevisionsPage);
    } else if (path === '/tv-sprejemniki') {
        app.component('TvSprejemnikiPage', TvSprejemnikiPage);
    }

    app.mount('#app');
}
