/// <reference types="vite/client" />
import { createInertiaApp, router } from '@inertiajs/vue3'
import ui from '@nuxt/ui/vue-plugin'
import AppLayout from './layouts/AppLayout.vue'
import '../css/app.css'

const appTitle = typeof document !== 'undefined' ? document.title : 'Takt'

// Prevents stale prefetched props from a previous user session (e.g. after logout/login as another user).
router.on('navigate', () => router.flushAll())

createInertiaApp({
  title: title => (title && title !== appTitle) ? `${title} - ${appTitle}` : appTitle,
  layout: () => AppLayout,
  withApp(app) {
    app.use(ui)
  },
})
