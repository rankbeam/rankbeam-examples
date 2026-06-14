<script>
  import Nav from '../Shared/Nav.svelte'
  export let title
</script>

<svelte:head>
  <title>{title}</title>
</svelte:head>

<Nav />

<main>
  <h1 data-testid="page-title">{title}</h1>
  <p>Navigate to a contract page above — Inertia handles the visit client-side.</p>
</main>
