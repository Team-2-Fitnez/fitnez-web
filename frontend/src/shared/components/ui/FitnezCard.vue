<script setup lang="ts">
withDefaults(defineProps<{ padded?: boolean }>(), { padded: true })
</script>

<template>
  <section :class="padded ? 'card' : 'card card-no-padding'">
    <slot />
  </section>
</template>
