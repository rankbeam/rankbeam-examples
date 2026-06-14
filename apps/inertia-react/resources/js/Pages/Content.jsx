import { Head } from '@inertiajs/react'
import Nav from '../Shared/Nav'

// Bind head-key (not only React's key) so Inertia replaces same-key meta/link tags.
export default function Content({ seo, schema, content }) {
  return (
    <>
      <Head title={seo.title}>
        {seo.meta.map((m) => (
          <meta
            key={m['head-key']}
            head-key={m['head-key']}
            name={m.name}
            property={m.property}
            content={m.content}
          />
        ))}
        {seo.link.map((l) => (
          <link
            key={l['head-key']}
            head-key={l['head-key']}
            rel={l.rel}
            hrefLang={l.hreflang}
            href={l.href}
          />
        ))}
      </Head>

      <Nav />

      <main>
        <article>
          <h1 data-testid="page-title">{content.title}</h1>
          {content.excerpt && <p data-testid="page-excerpt">{content.excerpt}</p>}
        </article>
      </main>
    </>
  )
}
