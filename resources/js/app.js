require('./bootstrap');

// Import modules...
import { createApp, h } from 'vue';
import { App as InertiaApp, plugin as InertiaPlugin } from '@inertiajs/inertia-vue3';
import { InertiaProgress } from '@inertiajs/progress';

//  Import Element (Vue Components)
import ElementPlus from 'element-plus';
import 'element-plus/lib/theme-chalk/index.css';

const el = document.getElementById('app');

createApp({
    render: () =>
        h(InertiaApp, {
            initialPage: JSON.parse(el.dataset.page),
            resolveComponent: (name) => require(`./Pages/${name}`).default,
        }),
})
.mixin({ methods: { route } })
.use(InertiaPlugin)
.use(ElementPlus)
.mount(el);

InertiaProgress.init({ color: '#1E40AF' });
