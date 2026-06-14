<script setup>
import { Head } from '@inertiajs/vue3'
import Nav from '../Shared/Nav.vue'

defineProps({ seo: Object, schema: Array, content: Object })
</script>

<template>
  <!-- Bind :head-key (NOT :key) so Inertia replaces same-key meta/link tags. -->
  <Head :title="seo.title">
    <meta
      v-for="m in seo.meta"
      :key="m['head-key']"
      :head-key="m['head-key']"
      :name="m.name"
      :property="m.property"
      :content="m.content"
    />
    <link
      v-for="l in seo.link"
      :key="l['head-key']"
      :head-key="l['head-key']"
      :rel="l.rel"
      :hreflang="l.hreflang"
      :href="l.href"
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
