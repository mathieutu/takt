/// <reference types="vite/client" />
import { createInertiaApp } from '@inertiajs/vue3'
import ui from '@nuxt/ui/vue-plugin'
import AppLayout from './layouts/AppLayout.vue'
import '../css/app.css'

const appTitle = document.title
export default createInertiaApp({
  title: title => [title, appTitle].filter(Boolean).join(' - '),
  layout: () => AppLayout,
  withApp(app) {
    app.use(ui)
  },
})
