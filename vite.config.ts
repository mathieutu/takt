import inertia from '@inertiajs/vite'
import ui from '@nuxt/ui/vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import { defineConfig } from 'vite'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.ts'],
      refresh: true,
    }),
    vue(),
    inertia({ ssr: false }),
    ui({
      autoImport: false,
      components: {
        dts: 'resources/js/types/components.d.ts',
      },
      router: 'inertia',
      ui: {
        colors: {
          primary: 'pink',
        },
      },
    }),
    // wayfinder()
  ],
})
