<script setup lang="ts">
</script>

<template>
  <div style="display: grid; grid-template-columns: minmax(240px, 320px) 1fr; gap: 1rem; min-height: 420px">
    <div
      style="border: 1px solid var(--color-border, rgba(0,0,0,0.14)); border-radius: var(--radius-lg, 1.75rem); padding: 0.75rem; overflow: hidden;"
    >
      <div v-for="i in 6" :key="i" style="display: flex; gap: 0.75rem; align-items: center; padding: 0.65rem 0.5rem">
        <div class="skeleton-block skeleton-shimmer" style="width: 2.5rem; height: 2.5rem; border-radius: 999px; flex-shrink: 0"></div>
        <div style="flex: 1">
          <div class="skeleton-block skeleton-shimmer" style="height: 0.75rem; width: 65%; margin-bottom: 0.35rem"></div>
          <div class="skeleton-block skeleton-shimmer" style="height: 0.55rem; width: 40%"></div>
        </div>
      </div>
    </div>
    <div
      style="border: 1px solid var(--color-border, rgba(0,0,0,0.14)); border-radius: var(--radius-lg, 1.75rem); padding: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem;"
    >
      <div class="skeleton-block skeleton-shimmer" style="height: 0.85rem; width: 30%"></div>
      <div v-for="i in 5" :key="i" class="skeleton-block skeleton-shimmer" :style="`height: 2.5rem; width: ${i % 2 ? '55%' : '70%'}; align-self: ${i % 2 ? 'flex-start' : 'flex-end'}; border-radius: 1rem;`"></div>
    </div>
  </div>
</template>

<style scoped>
@import './skeleton-shimmer.css';
</style>
