/// <reference types="vite/client" />
import { createInertiaApp } from '@inertiajs/vue3'
import AppLayout from './layouts/AppLayout.vue'

createInertiaApp({
    layout: () => AppLayout,
})
