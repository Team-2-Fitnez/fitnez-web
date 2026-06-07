<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue'
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

const mobileOpen = ref(false)
let previousBodyOverflow = ''

function onAnchorClick(event: Event, targetId: string) {
  emit('smoothScroll', event, targetId)
}

function onMobileAnchorClick(event: Event, targetId: string) {
  onAnchorClick(event, targetId)
  closeMobile()
}

function closeMobile() {
  mobileOpen.value = false
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') closeMobile()
}

watch(mobileOpen, (open) => {
  if (typeof document === 'undefined') return
  if (open) {
    previousBodyOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    return
  }
  document.body.style.overflow = previousBodyOverflow
})

onMounted(() => {
  window.addEventListener('keydown', onKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', onKeydown)
  if (typeof document !== 'undefined') {
    document.body.style.overflow = previousBodyOverflow
  }
})
</script>

<template>
  <nav
    id="navbar"
    :class="[
      'landing-public-nav fixed top-0 w-full z-[100] transition-all duration-300',
      variant === 'static' ? 'nav-scrolled py-2' : 'bg-transparent py-4'
    ]"
  >
    <div class="flex min-w-0 justify-between items-center gap-3 px-gutter max-w-container-max mx-auto">
      <RouterLink to="/" class="flex min-w-0 items-center gap-2" @click="closeMobile">
        <span class="truncate font-headline-lg text-headline-lg font-extrabold text-on-background">Fitnez Gym</span>
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

      <div class="hidden md:flex gap-4">
        <RouterLink
          to="/login/member"
          class="text-primary font-label-bold text-label-bold hover:bg-primary-container/10 px-4 py-2 rounded-lg transition-all text-center"
        >Login</RouterLink>
        <RouterLink
          to="/register"
          class="bg-primary text-on-primary font-label-bold text-label-bold px-6 py-2.5 rounded-full hover:shadow-lg hover:scale-105 active:scale-95 transition-all text-center"
        >Join Now</RouterLink>
      </div>

      <button
        class="landing-menu-button md:hidden"
        type="button"
        aria-label="Open navigation"
        :aria-expanded="mobileOpen"
        @click="mobileOpen = true"
      >
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>

    <div v-if="mobileOpen" class="landing-mobile-drawer md:hidden" role="dialog" aria-modal="true" aria-label="Site navigation">
      <button class="landing-mobile-overlay" type="button" aria-label="Close navigation" @click="closeMobile"></button>
      <div class="landing-mobile-panel">
        <div class="landing-mobile-header">
          <RouterLink to="/" class="font-headline-lg text-headline-lg font-extrabold text-on-background" @click="closeMobile">Fitnez Gym</RouterLink>
          <button class="landing-mobile-close" type="button" aria-label="Close navigation" @click="closeMobile">x</button>
        </div>

        <div class="landing-mobile-links">
          <template v-if="variant === 'landing'">
            <a href="#features" @click="onMobileAnchorClick($event, 'features')">Features</a>
            <a href="#packages" @click="onMobileAnchorClick($event, 'packages')">Packages</a>
            <a href="#how-it-works" @click="onMobileAnchorClick($event, 'how-it-works')">How it Works</a>
          </template>
          <template v-else>
            <RouterLink to="/#features" @click="closeMobile">Features</RouterLink>
            <RouterLink to="/#packages" @click="closeMobile">Packages</RouterLink>
            <RouterLink to="/#how-it-works" @click="closeMobile">How it Works</RouterLink>
          </template>
          <RouterLink to="/faq" @click="closeMobile">FAQ</RouterLink>
        </div>

        <div class="landing-mobile-actions">
          <RouterLink to="/login/member" class="landing-mobile-login" @click="closeMobile">Login</RouterLink>
          <RouterLink to="/register" class="landing-mobile-join" @click="closeMobile">Join Now</RouterLink>
        </div>
      </div>
    </div>
  </nav>
</template>

<style scoped>
@import './landing-public.css';

.landing-menu-button {
  align-items: center;
  background: #0058be;
  border: 0;
  border-radius: 999px;
  display: inline-flex;
  flex-direction: column;
  gap: 4px;
  height: 44px;
  justify-content: center;
  padding: 0;
  width: 44px;
}

@media (min-width: 768px) {
  .landing-menu-button {
    display: none;
  }
}

.landing-menu-button span {
  background: #ffffff;
  border-radius: 999px;
  display: block;
  height: 2px;
  width: 18px;
}

.landing-mobile-drawer {
  inset: 0;
  position: fixed;
  z-index: 120;
}

.landing-mobile-overlay {
  background: rgba(11, 28, 48, 0.52);
  border: 0;
  inset: 0;
  position: absolute;
}

.landing-mobile-panel {
  background: #ffffff;
  box-shadow: -24px 0 60px rgba(11, 28, 48, 0.18);
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  height: 100vh;
  height: 100dvh;
  margin-left: auto;
  max-width: min(22rem, calc(100vw - 2rem));
  overflow-y: auto;
  padding: max(1rem, env(safe-area-inset-top)) max(1rem, env(safe-area-inset-right)) max(1rem, env(safe-area-inset-bottom)) 1rem;
  position: relative;
  width: 86vw;
}

.landing-mobile-header {
  align-items: center;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
}

.landing-mobile-close {
  background: #f1f5f9;
  border: 0;
  border-radius: 999px;
  color: #0b1c30;
  font-size: 1rem;
  font-weight: 900;
  height: 44px;
  width: 44px;
}

.landing-mobile-links {
  display: grid;
  gap: 0.5rem;
}

.landing-mobile-links a {
  border-radius: 0.875rem;
  color: #334155;
  font-weight: 800;
  padding: 0.875rem 1rem;
}

.landing-mobile-links a:hover {
  background: #eef5ff;
  color: #0058be;
}

.landing-mobile-actions {
  display: grid;
  gap: 0.75rem;
  margin-top: auto;
}

.landing-mobile-login,
.landing-mobile-join {
  align-items: center;
  border-radius: 999px;
  display: flex;
  font-weight: 900;
  justify-content: center;
  min-height: 44px;
  padding: 0.75rem 1rem;
}

.landing-mobile-login {
  border: 1px solid #cbd5e1;
  color: #0058be;
}

.landing-mobile-join {
  background: #0058be;
  color: #ffffff;
}
</style>
