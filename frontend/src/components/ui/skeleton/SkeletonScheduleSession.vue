<script setup lang="ts">
withDefaults(defineProps<{ rows?: number }>(), { rows: 4 })
</script>

<template>
  <div class="skeleton-schedule-list" style="display: grid; gap: 0.75rem">
    <div
      v-for="i in rows"
      :key="i"
      :style="{
        background: 'var(--color-paper, rgba(255, 253, 252, 0.97))',
        border: '1px solid var(--color-border, rgba(0,0,0,0.14))',
        borderRadius: '1rem',
        padding: '1rem 1.25rem',
        display: 'grid',
        gridTemplateColumns: '5rem 1fr auto',
        gap: '1rem',
        alignItems: 'center',
      }"
    >
      <div class="skeleton-block skeleton-shimmer" style="height: 2.5rem; width: 100%; border-radius: 0.5rem"></div>
      <div>
        <div class="skeleton-block skeleton-shimmer" style="height: 0.85rem; width: 70%; margin-bottom: 0.5rem"></div>
        <div class="skeleton-block skeleton-shimmer" style="height: 0.65rem; width: 45%"></div>
      </div>
      <div class="skeleton-block skeleton-shimmer" style="height: 2rem; width: 5rem; border-radius: 999px"></div>
    </div>
  </div>
</template>

<style scoped>
@import './skeleton-shimmer.css';
</style>
