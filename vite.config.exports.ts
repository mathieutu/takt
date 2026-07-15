import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'

// Dedicated build for scripts inlined into standalone documents (PDF export template).
// Library/IIFE mode bundles all imports into a single self-contained file with no
// code-split chunks — required since this script is inlined as a raw <script> tag
// with no base URL to resolve relative chunk imports against.
export default defineConfig({
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
    },
  },
  publicDir: false,
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
