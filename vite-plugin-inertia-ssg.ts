/* eslint-disable no-console */
import type { OutputChunk } from 'rolldown'
import type { Plugin, ResolvedConfig } from 'vite'
import { createHash } from 'node:crypto'
import { execSync } from 'node:child_process'
import { readFileSync } from 'node:fs'
import { join } from 'node:path'
import { cwd } from 'node:process'

type SsgPage = {
  component: string,
  url: string,
  props?: Record<string, unknown>,
}

export const inertiaSsg = (pages: SsgPage[]): Plugin => {
  let config: ResolvedConfig
  let ssrBundle: string | undefined

  return {
    name: 'inertia-ssg',
    apply: 'build',

    configResolved(resolved) {
      config = resolved
    },

    generateBundle(options, bundle) {
      if (!config.build.ssr) return

      const isEntryChunk = (chunk: unknown): chunk is OutputChunk =>
        (chunk as OutputChunk).type === 'chunk' && (chunk as OutputChunk).isEntry

      const entry = Object.values(bundle).find(isEntryChunk)
      if (entry) {
        ssrBundle = join(options.dir ?? config.build.outDir, entry.fileName)
      }
    },

    closeBundle() {
      if (!config.build.ssr || !ssrBundle) return

      const root = cwd()
      const outDir = join(root, 'public/build/ssg')
      const manifestPath = join(root, 'public/build/manifest.json')
      const version = createHash('md5').update(readFileSync(manifestPath)).digest('hex')

      // The SSR bundle calls createServer() on import and never exits on its own.
      // We isolate the render in a child process that forces process.exit(0) when done.
      const script = [
        `import { mkdir, writeFile } from 'node:fs/promises'`,
        `import { pathToFileURL } from 'node:url'`,
        `const { default: render } = await import(pathToFileURL(${JSON.stringify(ssrBundle)}).href)`,
        `await mkdir(${JSON.stringify(outDir)}, { recursive: true })`,
        ...pages.flatMap(page => [
          `{ const { head, body } = await render(${JSON.stringify({ props: {}, ...page, version })})`,
          `await writeFile(${JSON.stringify(join(outDir, `${page.component}.json`))}, JSON.stringify({ head, body })) }`,
        ]),
        `process.exit(0)`,
      ].join('\n')

      execSync('node --input-type=module', { input: script, cwd: root, stdio: ['pipe', 'inherit', 'inherit'] })
      pages.forEach(({ component }) => console.log(`✅ SSG: public/build/ssg/${component}.json generated`))
    },
  }
}
