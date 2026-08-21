import inertia from '@inertiajs/vite'
import ui from '@nuxt/ui/vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import { defineConfig } from 'vite'
import { inertiaSsg } from './vite-plugin-inertia-ssg.ts'

export default defineConfig({
  ssr: {
    noExternal: ['@nuxt/ui'],
  },
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.ts'],
      ssr: 'resources/js/ssr.ts',
      refresh: true,
    }),
    vue(),
    inertia(),
    ui({
      autoImport: false,
      components: {
        dts: 'resources/js/types/components.d.ts',
      },
      router: 'inertia',
      icon: {
        clientBundle: {
          scan: true,
        },
      },
      ui: {
        colors: {
          primary: 'pink',
        },
        button: {
          compoundVariants: [
            {
              color: 'neutral',
              variant: 'ghost',
              class: 'hover:bg-accented active:bg-accented focus-visible:bg-accented',
            },
          ],
        },
      },
    }),
    inertiaSsg([
      { component: 'LandingPage', url: '/' },
    ]),
    // wayfinder()
  ],
})
