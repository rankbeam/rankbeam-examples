<script setup>
import { Link, usePage } from '@inertiajs/vue3'
const nav = usePage().props.nav ?? []
</script>

<template>
  <header>
    <nav aria-label="Contract pages">
      <Link href="/" data-testid="nav-home">Home</Link>
      <Link
        v-for="item in nav"
        :key="item.key"
        :href="item.href"
        :data-testid="`nav-${item.key}`"
      >{{ item.label }}</Link>
    </nav>
  </header>
</template>
