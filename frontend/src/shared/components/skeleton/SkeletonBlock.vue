<script setup lang="ts">
defineProps<{
  height?: string
}>()
</script>

<template>
  <div
    class="animate-pulse rounded-xl bg-slate-200"
    :style="{ height: height || '1rem' }"
  />
</template>
