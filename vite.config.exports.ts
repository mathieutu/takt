import { fileURLToPath, URL } from 'node:url'
import laravel from 'laravel-vite-plugin'
import { defineConfig } from 'vite'

// Dedicated build for resources/js/exports/billing-pdf.ts, the script inlined into the
// PDF export template (resources/views/exports/billing.blade.php) — a plain HTML document
// sent to a headless-Chromium rendering service with no base URL and no Vue app running.
// Needs its own config (not the main vite.config.ts `input` array) in `build.lib`/`iife`
// mode: inlining the main bundle's module output as a raw <script> breaks, since it
// imports shared chunks that don't exist once extracted from the manifest.
export default defineConfig({
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
    },
  },
  plugins: [
    // Reused (rather than a plain outDir) so the built script is tracked in a real Vite
    // manifest, readable from the Blade template via the standard `Vite::content()`
    // helper instead of a hardcoded file path.
    laravel({
      input: ['resources/js/exports/billing-pdf.ts'],
      buildDirectory: 'build-exports',
      refresh: false,
    }),
  ],
  build: {
    outDir: 'public/build-exports',
    emptyOutDir: true,
    lib: {
      entry: fileURLToPath(new URL('./resources/js/exports/billing-pdf.ts', import.meta.url)),
      name: 'BillingPdf',
      formats: ['iife'],
      fileName: () => 'billing-pdf.js',
    },
  },
})
