<script setup lang="ts">
import { RouterLink } from 'vue-router'

withDefaults(
  defineProps<{
    variant?: 'landing' | 'static'
  }>(),
  { variant: 'landing' },
)

const emit = defineEmits<{
  smoothScroll: [event: Event, targetId: string]
}>()

function onAnchorClick(event: Event, targetId: string) {
  emit('smoothScroll', event, targetId)
}
</script>

<template>
  <nav
    id="navbar"
    class="landing-public-nav fixed top-0 w-full z-[100] transition-all duration-300 bg-transparent py-4"
  >
    <div class="flex justify-between items-center px-gutter max-w-container-max mx-auto">
      <RouterLink to="/" class="flex items-center gap-2">
        <span class="font-headline-lg text-headline-lg font-extrabold text-on-background">Fitnez Gym</span>
      </RouterLink>

      <div class="hidden md:flex gap-stack-lg items-center">
        <template v-if="variant === 'landing'">
          <a
            class="nav-link text-on-surface-variant hover:text-primary transition-colors font-label-bold text-label-bold"
            href="#features"
            @click="onAnchorClick($event, 'features')"
          >Features</a>
          <a
            class="nav-link text-on-surface-variant hover:text-primary transition-colors font-label-bold text-label-bold"
            href="#packages"
            @click="onAnchorClick($event, 'packages')"
          >Packages</a>
          <a
            class="nav-link text-on-surface-variant hover:text-primary transition-colors font-label-bold text-label-bold"
            href="#how-it-works"
            @click="onAnchorClick($event, 'how-it-works')"
          >How it Works</a>
        </template>
        <template v-else>
          <RouterLink
            class="nav-link text-on-surface-variant hover:text-primary transition-colors font-label-bold text-label-bold"
            to="/#features"
          >Features</RouterLink>
          <RouterLink
            class="nav-link text-on-surface-variant hover:text-primary transition-colors font-label-bold text-label-bold"
            to="/#packages"
          >Packages</RouterLink>
          <RouterLink
            class="nav-link text-on-surface-variant hover:text-primary transition-colors font-label-bold text-label-bold"
            to="/#how-it-works"
          >How it Works</RouterLink>
        </template>
        <RouterLink
          class="nav-link text-on-surface-variant hover:text-primary transition-colors font-label-bold text-label-bold"
          to="/faq"
        >FAQ</RouterLink>
      </div>

      <div class="flex gap-4">
        <RouterLink
          to="/login/member"
          class="hidden sm:block text-primary font-label-bold text-label-bold hover:bg-primary-container/10 px-4 py-2 rounded-lg transition-all text-center"
        >Login</RouterLink>
        <RouterLink
          to="/register"
          class="bg-primary text-on-primary font-label-bold text-label-bold px-6 py-2.5 rounded-full hover:shadow-lg hover:scale-105 active:scale-95 transition-all text-center"
        >Join Now</RouterLink>
      </div>
    </div>
  </nav>
</template>

<style scoped>
@import './landing-public.css';
</style>
