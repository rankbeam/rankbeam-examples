<script setup>
import { Head } from '@inertiajs/vue3'
import Nav from '../Shared/Nav.vue'

defineProps({ title: String })
</script>

<template>
  <Head :title="title" />
  <Nav />
  <main>
    <h1 data-testid="page-title">{{ title }}</h1>
    <p>Navigate to a contract page above — Inertia handles the visit client-side.</p>
  </main>
</template>
