import './bootstrap'
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import AppLayout from './layouts/AppLayout.vue'

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./views/*.vue', { eager: true })
        const page = pages[`./views/${name}.vue`]
        if (!page) throw new Error(`Page component "${name}" not found.`)
        if (page.default.layout === undefined) page.default.layout = AppLayout
        return page
    },
    setup({ el, App, props, plugin }) {
        console.group('[Inertia] props')
        console.log(JSON.parse(JSON.stringify(props)))
        console.groupEnd()
        console.log('[Inertia] HTML initial', el.outerHTML)
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el)
    },
})
