<script setup lang="ts">
/**
 * SkeletonList.vue
 * List of skeleton items for contact lists, notification feeds.
 * Props: rows (5), avatar (true), lines (2)
 * Each row: optional avatar circle, text lines, right-side meta pill.
 */
withDefaults(defineProps<{rows?: number; avatar?: boolean; lines?: number}>(), {rows: 5, avatar: true, lines: 2})
</script>

<template>
  <div class="animate-pulse" :style="{background: 'var(--color-paper, rgba(255, 253, 252, 0.97))', border: '1px solid var(--color-border, rgba(0,0,0,0.14))', borderRadius: 'var(--radius-lg, 1.75rem)', boxShadow: 'var(--shadow-card, 0 18px 55px rgba(0,0,0,0.07))', overflow: 'hidden'}">
    <div v-for="i in rows" :key="'r'+i" class="flex items-center gap-3 px-4 py-3" :style="{borderBottom: i<rows ? '1px solid var(--color-border, rgba(0,0,0,0.14))' : 'none'}">
      <!-- Avatar circle (2.25rem, matches chat contact list size) -->
      <div v-if="avatar" class="rounded-full bg-gray-200 dark:bg-gray-700 flex-shrink-0" style="width:2.25rem;height:2.25rem"></div>
      <!-- Text lines -->
      <div class="flex-1 min-w-0 space-y-2">
        <div v-for="j in lines" :key="'l'+j" class="bg-gray-200 dark:bg-gray-700 rounded" :style="{height: j===lines ? '0.65rem' : '0.85rem', width: j===lines ? '45%' : '100%', maxWidth: j===lines ? '140px' : '100%'}"></div>
      </div>
      <!-- Right-side meta pill (time/badge placeholder) -->
      <div class="bg-gray-200 dark:bg-gray-700 rounded flex-shrink-0" style="width:3rem;height:0.65rem"></div>
    </div>
  </div>
</template>
