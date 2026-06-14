import { createInertiaApp, router } from '@inertiajs/react'
import { createRoot, hydrateRoot } from 'react-dom/client'

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
    const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true })
    return pages[`./Pages/${name}.jsx`]
  },
  setup({ el, App, props }) {
    // SSR-rendered markup is hydrated; a fresh client render otherwise.
    if (el.hasChildNodes()) {
      hydrateRoot(el, <App {...props} />)
    } else {
      createRoot(el).render(<App {...props} />)
    }
  },
})
