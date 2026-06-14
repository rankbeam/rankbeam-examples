import { createInertiaApp, router } from '@inertiajs/svelte'
import { hydrate, mount } from 'svelte'

function updateSchema(page) {
  document.querySelectorAll('head script[data-seo-schema]').forEach((script) => script.remove())

  const canonical = page.props.seo?.link?.find((link) => link.rel === 'canonical')?.href ?? page.url

  for (const entry of page.props.schema ?? []) {
    const script = document.createElement('script')
    script.type = 'application/ld+json'
    script.dataset.seoSchema = ''
    script.dataset.seoUrl = canonical
    script.textContent = entry.innerHTML
    document.head.appendChild(script)
  }
}

router.on('navigate', ({ detail }) => updateSchema(detail.page))

createInertiaApp({
  resolve: (name) => {
    const pages = import.meta.glob('./Pages/**/*.svelte', { eager: true })
    return pages[`./Pages/${name}.svelte`]
  },
  setup({ el, App, props }) {
    // Hydrate SSR markup; mount fresh otherwise.
    if (el.dataset.serverRendered === 'true') {
      hydrate(App, { target: el, props })
    } else {
      mount(App, { target: el, props })
    }
  },
})
