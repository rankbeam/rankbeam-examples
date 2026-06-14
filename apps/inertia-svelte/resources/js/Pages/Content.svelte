<script>
  import Nav from '../Shared/Nav.svelte'
  export let seo
  export let schema
  export let content
</script>

<!-- Keep SEO tags in the page; JSON-LD is owned by the root view and router. -->
<svelte:head>
  <title>{seo.title}</title>
  {#each seo.meta as m (m['head-key'])}
    {#if m.name}
      <meta name={m.name} content={m.content} />
    {:else}
      <meta property={m.property} content={m.content} />
    {/if}
  {/each}
  {#each seo.link as l (l['head-key'])}
    <link rel={l.rel} hreflang={l.hreflang} href={l.href} />
  {/each}
</svelte:head>

<Nav />

<main>
  <article>
    <h1 data-testid="page-title">{content.title}</h1>
    {#if content.excerpt}
      <p data-testid="page-excerpt">{content.excerpt}</p>
    {/if}
  </article>
</main>
