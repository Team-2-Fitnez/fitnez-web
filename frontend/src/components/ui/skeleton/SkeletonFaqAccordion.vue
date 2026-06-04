<script setup lang="ts">
withDefaults(defineProps<{ rows?: number }>(), { rows: 5 })
</script>

<template>
  <div style="display: flex; flex-direction: column; gap: 1rem">
    <div
      v-for="i in rows"
      :key="i"
      class="skeleton-block skeleton-shimmer"
      style="height: 4rem; width: 100%; border-radius: 0.75rem; border: 1px solid rgba(0,0,0,0.08)"
    ></div>
  </div>
</template>

<style scoped>
@import './skeleton-shimmer.css';
</style>
