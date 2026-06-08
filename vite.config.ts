import { execSync } from 'node:child_process'
import { join } from 'node:path'
import { cwd } from 'node:process'
import inertia from '@inertiajs/vite'
import ui from '@nuxt/ui/vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import { defineConfig, type Plugin } from 'vite'

const ssgPlugin = (): Plugin => {
  let isSsr = false

  return {
    name: 'inertia-ssg',
    apply: 'build',
    configResolved(config) {
      isSsr = !!config.build.ssr
    },
    async closeBundle() {
      if (!isSsr) return

      const root = cwd()

      // bootstrap/ssr/app.js appelle createServer() à l'import et ne s'arrête jamais.
      // On isole le rendu dans un sous-process qui force process.exit(0) une fois terminé.
      const ssrBundle = join(root, 'bootstrap/ssr/app.js')
      const outFile = join(root, 'public/build/ssg/LandingPage.json')
      const script = [
        `import { renderToString } from 'vue/server-renderer'`,
        `import { mkdirSync, writeFileSync } from 'node:fs'`,
        `import { pathToFileURL } from 'node:url'`,
        `const { default: render } = await import(pathToFileURL(${JSON.stringify(ssrBundle)}).href)`,
        `const { head, body } = await render({ component: 'LandingPage', props: {}, url: '/', version: null }, renderToString)`,
        `mkdirSync(${JSON.stringify(join(root, 'public/build/ssg'))}, { recursive: true })`,
        `writeFileSync(${JSON.stringify(outFile)}, JSON.stringify({ head, body }))`,
        `process.exit(0)`,
      ].join('\n')

      execSync('node --input-type=module', { input: script, cwd: root })
      console.log('✅ SSG: public/build/ssg/LandingPage.json generated')
    },
  }
}

export default defineConfig({
  ssr: {
    noExternal: ['@nuxt/ui'],
  },
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.ts'],
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
    ssgPlugin(),
    // wayfinder()
  ],
})
