import { createApp, h } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'

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
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
    return pages[`./Pages/${name}.vue`]
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el)
  },
})
