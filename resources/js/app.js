import './bootstrap';
import { createApp } from 'vue';
import TelevisionsPage from './pages/TelevisionsPage.vue';
import TvReceiverPage from './pages/TvReceiverPage.vue';


function initVue() {
    const path = window.location.pathname;
    const appElement = document.getElementById('app');

    if (!appElement) {
        return;
    }

    if (path === '/televisions' || path === '/tv-sprejemniki') {
        try {
            if (path === '/televisions') {
                const vueApp = createApp(TelevisionsPage);
                vueApp.mount('#app');
            } else if (path === '/tv-sprejemniki') {
                const vueApp = createApp(TvReceiverPage);
                vueApp.mount('#app');
            }
        } catch (error) {
            appElement.innerHTML = '<div style="padding: 20px; color: red;">Error: ' + error.message + '</div>';
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initVue);
} else {
    setTimeout(initVue, 0);
}
