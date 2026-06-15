<script setup lang="ts">
withDefaults(defineProps<{ count?: number }>(), { count: 4 })
</script>

<template>
  <div class="skeleton-stat-grid" :style="{ display: 'grid', gap: '1rem', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))' }">
    <div
      v-for="i in count"
      :key="i"
      class="skeleton-stat-card"
      :style="{
        background: 'var(--color-paper, rgba(255, 253, 252, 0.97))',
        border: '1px solid var(--color-border, rgba(0,0,0,0.14))',
        borderRadius: 'var(--radius-lg, 1.75rem)',
        padding: '1.25rem 1.5rem',
      }"
    >
      <div class="skeleton-block skeleton-shimmer" style="height: 0.65rem; width: 55%; margin-bottom: 0.75rem"></div>
      <div class="skeleton-block skeleton-shimmer" style="height: 1.75rem; width: 40%"></div>
      <div class="skeleton-block skeleton-shimmer" style="height: 0.6rem; width: 70%; margin-top: 0.5rem"></div>
    </div>
  </div>
</template>

<style scoped>
@import '@/shared/components/ui/skeleton/skeleton-shimmer.css';
</style>
