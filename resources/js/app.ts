/// <reference types="vite/client" />
import { createInertiaApp } from '@inertiajs/vue3'
import ui from '@nuxt/ui/vue-plugin'
import AppLayout from './layouts/AppLayout.vue'
import '../css/app.css'

createInertiaApp({
  layout: () => AppLayout,
  withApp: app => app.use(ui),
})
