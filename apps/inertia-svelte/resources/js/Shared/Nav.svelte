<script>
  import { inertia, page } from '@inertiajs/svelte'
  $: nav = $page.props.nav ?? []
</script>

<header>
  <nav aria-label="Contract pages">
    <a href="/" use:inertia data-testid="nav-home">Home</a>
    {#each nav as item (item.key)}
      <a href={item.href} use:inertia data-testid={`nav-${item.key}`}>{item.label}</a>
    {/each}
  </nav>
</header>
