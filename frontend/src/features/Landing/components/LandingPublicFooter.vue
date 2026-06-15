<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <footer class="bg-on-background dark:bg-on-background w-full mt-auto">
    <div class="flex flex-col md:flex-row justify-between items-center w-full px-gutter py-8 max-w-container-max mx-auto font-body-md text-body-md">
      <div class="font-headline-lg text-headline-lg text-surface-lowest mb-4 md:mb-0">
        Fitnez Gym
      </div>
      <div class="flex flex-wrap justify-center gap-6 text-primary-fixed dark:text-primary-fixed-dim">
        <RouterLink
          class="text-secondary-fixed-dim hover:text-surface-bright transition-colors duration-200 opacity-80 hover:opacity-100 transition-opacity"
          to="/"
        >Home</RouterLink>
        <RouterLink
          class="text-secondary-fixed-dim hover:text-surface-bright transition-colors duration-200 opacity-80 hover:opacity-100 transition-opacity"
          to="/faq"
        >FAQ</RouterLink>
        <RouterLink
          class="text-secondary-fixed-dim hover:text-surface-bright transition-colors duration-200 opacity-80 hover:opacity-100 transition-opacity"
          to="/privacy-cookie-policy"
        >Privacy & Cookie Policy</RouterLink>
      </div>
      <div class="text-secondary-fixed-dim mt-4 md:mt-0 text-sm">
        © 2024 Fitnez Gym. All rights reserved.
      </div>
    </div>
  </footer>
</template>

<style scoped>
@import '@/features/Landing/components/landing-public.css';
</style>
