import type { InertiaAppSSRResponse, Page } from '@inertiajs/core'
import { createInertiaApp } from '@inertiajs/vue3'
import ui from '@nuxt/ui/vue-plugin'
import { createHead, renderSSRHead } from '@unhead/vue/server'
import { createSSRApp, h } from 'vue'
import { renderToString } from 'vue/server-renderer'

// `resolve` is required by the SSR overload but injected at build time by @inertiajs/vite,
// so we cast the return type directly to avoid TypeScript failing on the missing property.
export default async (page: Page) => {
  const head = createHead()

  // @ts-expect-error - `resolve` is required by the SSR overload but injected at build time by @inertiajs/vite
  const app: InertiaAppSSRResponse = await createInertiaApp({
    page,
    render: renderToString,
    // @ts-expect-error - `el` is null but TS wants an el here 🤷
    setup: ({ App, props, plugin }) =>
      createSSRApp({ render: () => h(App, props) })
        .use(plugin)
        .use(head)
        .use(ui),
  })

  const { headTags } = await renderSSRHead(head)
  app.head.push(headTags)

  return app
}
