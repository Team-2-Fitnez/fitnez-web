<script setup lang="ts">
defineProps<{ label: string; value: string | number; hint?: string }>()
</script>

<template>
  <section class="stat-card">
    <p class="stat-label">{{ label }}</p>
    <p class="stat-value">{{ value }}</p>
    <p v-if="hint" class="stat-hint">{{ hint }}</p>
  </section>
</template>
