<script setup lang="ts">
withDefaults(defineProps<{ listRows?: number }>(), { listRows: 8 })
</script>

<template>
  <div style="display: grid; grid-template-columns: minmax(280px, 360px) 1fr; gap: 1.25rem; min-height: 480px">
    <div style="display: flex; flex-direction: column; gap: 0.5rem">
      <div v-for="i in listRows" :key="i" style="display: flex; gap: 0.75rem; align-items: center; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid rgba(0,0,0,0.08)">
        <div class="skeleton-block skeleton-shimmer" style="width: 2.25rem; height: 2.25rem; border-radius: 999px"></div>
        <div style="flex: 1">
          <div class="skeleton-block skeleton-shimmer" style="height: 0.75rem; width: 70%; margin-bottom: 0.35rem"></div>
          <div class="skeleton-block skeleton-shimmer" style="height: 0.55rem; width: 45%"></div>
        </div>
      </div>
    </div>
    <div
      :style="{
        border: '1px solid var(--color-border, rgba(0,0,0,0.14))',
        borderRadius: 'var(--radius-lg, 1.75rem)',
        padding: '1.5rem',
      }"
    >
      <div class="skeleton-block skeleton-shimmer" style="width: 4rem; height: 4rem; border-radius: 999px; margin-bottom: 1rem"></div>
      <div class="skeleton-block skeleton-shimmer" style="height: 1.25rem; width: 50%; margin-bottom: 0.75rem"></div>
      <div v-for="i in 4" :key="'d'+i" class="skeleton-block skeleton-shimmer" style="height: 0.7rem; width: 100%; margin-bottom: 0.5rem"></div>
    </div>
  </div>
</template>

<style scoped>
@import './skeleton-shimmer.css';
</style>
