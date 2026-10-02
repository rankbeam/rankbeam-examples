<script setup>
import { Head } from '@inertiajs/vue3'
import Nav from '../Shared/Nav.vue'

defineProps({ seo: Object, schema: Array, content: Object })
</script>

<template>
  <!-- Renderer objects carry head-key; omit attributes absent from each object. -->
  <Head :title="seo.title">
    <meta
      v-for="m in seo.meta"
      :key="m['head-key']"
      v-bind="m"
    />
    <link
      v-for="l in seo.link"
      :key="l['head-key']"
      v-bind="l"
    />
  </Head>

  <Nav />

  <main>
    <article>
      <h1 data-testid="page-title">{{ content.title }}</h1>
      <p v-if="content.excerpt" data-testid="page-excerpt">{{ content.excerpt }}</p>
    </article>
  </main>
</template>
