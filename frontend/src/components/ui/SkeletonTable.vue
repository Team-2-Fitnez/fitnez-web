<script setup lang="ts">
/**
 * SkeletonTable.vue
 * Table-like skeleton with header and body rows, for schedules, bookings.
 * Props: rows (4), columns (4)
 * Header row uses cream background (matching .data-table th).
 * Body rows have light border-bottom (matching .data-table td).
 */
withDefaults(defineProps<{rows?: number; columns?: number}>(), {rows: 4, columns: 4})
</script>

<template>
  <div class="animate-pulse" :style="{background: 'var(--color-paper, rgba(255, 253, 252, 0.97))', border: '1px solid var(--color-border, rgba(0,0,0,0.14))', borderRadius: 'var(--radius-lg, 1.75rem)', boxShadow: 'var(--shadow-card, 0 18px 55px rgba(0,0,0,0.07))', overflow: 'hidden'}">
    <!-- Header row (cream background, like .data-table th) -->
    <div class="flex items-center gap-4 px-4 py-3" :style="{background: 'var(--color-cream, #F4E8E3)'}">
      <div v-for="col in columns" :key="'h'+col" class="bg-gray-200 dark:bg-gray-700 rounded flex-1" :style="{height: '0.75rem', maxWidth: col===1 ? '40%' : col===columns ? '20%' : '30%'}"></div>
    </div>
    <!-- Body rows (border-bottom separator) -->
    <div v-for="row in rows" :key="'r'+row" class="flex items-center gap-4 px-4 py-3" :style="{borderBottom: row<rows ? '1px solid var(--color-border, rgba(0,0,0,0.10))' : 'none'}">
      <div v-for="col in columns" :key="'c'+row+'-'+col" class="bg-gray-200 dark:bg-gray-700 rounded flex-1" :style="{height: col===1 ? '0.85rem' : '0.75rem', maxWidth: col===1 ? '50%' : col===columns ? '15%' : '25%'}"></div>
    </div>
  </div>
</template>
