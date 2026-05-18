import './bootstrap'
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import AppLayout from './layouts/AppLayout.vue'

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./views/*.vue', { eager: true })
        const page = pages[`./views/${name}.vue`]
        if (!page) throw new Error(`Page component "${name}" not found.`)
        page.default.layout = page.default.layout ?? AppLayout
        return page
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el)
    },
})
