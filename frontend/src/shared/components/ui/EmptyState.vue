<script setup lang="ts">
defineProps<{ title: string; description?: string }>()
</script>

<template>
  <div class="rounded-[1.5rem] border border-dashed border-black/15 bg-white/60 p-8 text-center">
    <p class="text-lg font-black">{{ title }}</p>
    <p v-if="description" class="mt-2 text-sm font-semibold text-black/45">{{ description }}</p>
  </div>
</template>
