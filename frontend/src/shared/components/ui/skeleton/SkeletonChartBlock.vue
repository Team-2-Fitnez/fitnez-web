<script setup lang="ts">
</script>

<template>
  <div
    class="skeleton-chart"
    :style="{
      background: 'var(--color-paper, rgba(255, 253, 252, 0.97))',
      border: '1px solid var(--color-border, rgba(0,0,0,0.14))',
      borderRadius: 'var(--radius-lg, 1.75rem)',
      padding: '1.5rem',
      minHeight: '280px',
    }"
  >
    <div class="skeleton-block skeleton-shimmer" style="height: 0.75rem; width: 35%; margin-bottom: 1.25rem"></div>
    <div style="display: flex; align-items: flex-end; gap: 0.75rem; height: 200px">
      <div
        v-for="i in 7"
        :key="i"
        class="skeleton-block skeleton-shimmer"
        :style="{ flex: '1', borderRadius: '0.5rem 0.5rem 0 0', height: `${30 + (i % 4) * 18}%` }"
      ></div>
    </div>
  </div>
</template>

<style scoped>
@import '@/shared/components/ui/skeleton/skeleton-shimmer.css';
</style>
