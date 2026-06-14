<script setup lang="ts">
import { computed } from 'vue'
import { useGlobalRequestLoading } from '@/shared/stores/globalRequestLoading'

const loading = useGlobalRequestLoading()

const statusText = computed(() => {
  if (loading.profile === 'steady') return 'Preparing Fitnez data steadily'
  if (loading.profile === 'fast') return 'Arranging Fitnez data'
  return 'Retrieving Fitnez data'
})

const overlayStyle = computed(() => ({
  '--loader-shimmer-ms': `${loading.shimmerMs}ms`,
}))
</script>

<template>
  <Transition name="loader-fade">
    <div v-if="loading.visible" class="global-loader" :style="overlayStyle" aria-live="polite" aria-busy="true">
      <div class="global-loader__backdrop"></div>
      <div class="global-loader__panel">
        <div class="global-loader__bar shimmer-block global-loader__brand"></div>
        <div class="global-loader__row">
          <div class="global-loader__hero">
            <div class="shimmer-block global-loader__eyebrow"></div>
            <div class="shimmer-block global-loader__headline"></div>
            <div class="shimmer-block global-loader__headline global-loader__headline--short"></div>
            <div class="shimmer-block global-loader__line"></div>
            <div class="shimmer-block global-loader__line global-loader__line--short"></div>
          </div>
          <div class="global-loader__cards">
            <div class="global-loader__card">
              <div class="shimmer-block global-loader__pill"></div>
              <div class="shimmer-block global-loader__line"></div>
              <div class="shimmer-block global-loader__line global-loader__line--short"></div>
            </div>
            <div class="global-loader__card">
              <div class="shimmer-block global-loader__pill"></div>
              <div class="shimmer-block global-loader__line"></div>
              <div class="shimmer-block global-loader__line global-loader__line--short"></div>
            </div>
            <div class="global-loader__card">
              <div class="shimmer-block global-loader__pill"></div>
              <div class="shimmer-block global-loader__line"></div>
              <div class="shimmer-block global-loader__line global-loader__line--short"></div>
            </div>
          </div>
        </div>
        <div class="global-loader__footer">
          <span class="global-loader__dot"></span>
          <p>{{ statusText }}</p>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.global-loader {
  inset: 0;
  pointer-events: none;
  position: fixed;
  z-index: 4000;
}

.global-loader__backdrop {
  backdrop-filter: blur(12px);
  background:
    radial-gradient(circle at top left, rgba(183, 211, 244, 0.28), transparent 28rem),
    radial-gradient(circle at top right, rgba(219, 133, 79, 0.18), transparent 22rem),
    rgba(255, 252, 248, 0.72);
  inset: 0;
  position: absolute;
}

.global-loader__panel {
  display: grid;
  gap: 1.2rem;
  inset: 0;
  margin: auto;
  max-width: 1120px;
  padding: clamp(1rem, 4vw, 2rem);
  position: absolute;
  width: 100%;
}

.global-loader__bar,
.global-loader__hero,
.global-loader__card {
  background: rgba(255, 255, 255, 0.72);
  border: 1px solid rgba(7, 23, 47, 0.08);
  box-shadow: 0 18px 50px rgba(7, 23, 47, 0.08);
}

.global-loader__bar {
  border-radius: 999px;
  height: 3.4rem;
  width: min(100%, 24rem);
}

.global-loader__row {
  display: grid;
  gap: 1rem;
  grid-template-columns: minmax(0, 1.1fr) minmax(320px, 0.9fr);
}

.global-loader__hero,
.global-loader__card {
  border-radius: 2rem;
  padding: 1.4rem;
}

.global-loader__cards {
  display: grid;
  gap: 1rem;
}

.global-loader__eyebrow,
.global-loader__pill,
.global-loader__line,
.global-loader__headline,
.global-loader__brand {
  border-radius: 999px;
}

.global-loader__eyebrow {
  height: 0.8rem;
  margin-bottom: 1rem;
  width: 8rem;
}

.global-loader__headline {
  height: 1.25rem;
  margin-bottom: 0.75rem;
  width: 100%;
}

.global-loader__headline--short {
  width: 68%;
}

.global-loader__line {
  height: 0.9rem;
  margin-top: 0.7rem;
  width: 100%;
}

.global-loader__line--short {
  width: 56%;
}

.global-loader__pill {
  height: 0.75rem;
  margin-bottom: 1rem;
  width: 5rem;
}

.global-loader__footer {
  align-items: center;
  color: #152238;
  display: inline-flex;
  font-size: 0.8rem;
  font-weight: 900;
  gap: 0.65rem;
}

.global-loader__footer p {
  margin: 0;
}

.global-loader__dot {
  animation: loader-pulse 1.1s ease-in-out infinite;
  background: #f97316;
  border-radius: 999px;
  height: 0.7rem;
  width: 0.7rem;
}

.shimmer-block {
  background:
    linear-gradient(110deg, rgba(226, 232, 240, 0.9) 8%, rgba(255, 255, 255, 0.98) 18%, rgba(226, 232, 240, 0.9) 33%);
  background-size: 220% 100%;
  animation: shimmer var(--loader-shimmer-ms) linear infinite;
}

.loader-fade-enter-active,
.loader-fade-leave-active {
  transition: opacity 0.22s ease;
}

.loader-fade-enter-from,
.loader-fade-leave-to {
  opacity: 0;
}

@keyframes shimmer {
  to {
    background-position-x: -220%;
  }
}

@keyframes loader-pulse {
  0%, 100% {
    opacity: 0.5;
    transform: scale(0.95);
  }

  50% {
    opacity: 1;
    transform: scale(1.08);
  }
}

@media (max-width: 900px) {
  .global-loader__row {
    grid-template-columns: 1fr;
  }
}
</style>
