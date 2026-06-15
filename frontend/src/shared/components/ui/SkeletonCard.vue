<script setup lang="ts">
/**
 * SkeletonCard.vue
 * Reusable skeleton loading placeholder for card-style content.
 */
withDefaults(defineProps<{heading?: boolean; lines?: number; actions?: boolean; wide?: boolean}>(), {heading: false, lines: 2, actions: false, wide: false})
</script>

<template>
  <div :style="{background: 'var(--color-paper, rgba(255, 253, 252, 0.97))', border: '1px solid var(--color-border, rgba(0,0,0,0.14))', borderRadius: 'var(--radius-lg, 1.75rem)', boxShadow: 'var(--shadow-card, 0 18px 55px rgba(0,0,0,0.07))', padding: 'clamp(1.25rem, 3vw, 1.75rem)'}">
    <div v-if="heading" class="flex items-center gap-3 mb-4">
      <div class="skeleton-block skeleton-shimmer rounded-full flex-shrink-0" style="width:2.5rem;height:2.5rem"></div>
      <div class="flex-1 min-w-0">
        <div class="skeleton-block skeleton-shimmer rounded" style="height:1rem;width:60%;max-width:180px"></div>
        <div class="skeleton-block skeleton-shimmer rounded mt-2" style="height:0.7rem;width:40%;max-width:120px"></div>
      </div>
      <div class="skeleton-block skeleton-shimmer rounded-full flex-shrink-0" style="width:4.5rem;height:1.5rem"></div>
    </div>
    <div class="space-y-3">
      <div v-for="i in lines" :key="'line-'+i" class="skeleton-block skeleton-shimmer rounded" :style="{height: i===lines && lines>1 ? '0.75rem' : '0.85rem', width: i===lines && lines>1 ? '55%' : '100%', maxWidth: i===lines && lines>1 ? '180px' : '100%'}"></div>
    </div>
    <div v-if="wide" class="grid gap-3 mt-4" style="grid-template-columns:repeat(auto-fit,minmax(120px,1fr))">
      <div v-for="i in 3" :key="'stat-'+i" class="rounded-xl p-3" style="background:var(--color-cream,#F4E8E3)">
        <div class="skeleton-block skeleton-shimmer rounded" style="height:0.6rem;width:50%;margin-bottom:0.5rem"></div>
        <div class="skeleton-block skeleton-shimmer rounded" style="height:0.85rem;width:70%"></div>
      </div>
    </div>
    <div v-if="actions" class="flex items-center gap-3 mt-4">
      <div class="skeleton-block skeleton-shimmer rounded-xl" style="height:2.25rem;width:7rem"></div>
      <div class="skeleton-block skeleton-shimmer rounded-xl" style="height:2.25rem;width:7rem"></div>
    </div>
  </div>
</template>

<style scoped>
@import '@/shared/components/ui/skeleton/skeleton-shimmer.css';
</style>
