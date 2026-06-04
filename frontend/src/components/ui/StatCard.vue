<script setup lang="ts">
defineProps<{
  label: string
  value: string | number
  hint?: string
  trend?: string
  trendClass?: string // e.g. 'trend-up' or 'trend-warning'
  theme?: 'blue' | 'orange' | 'purple'
  icon?: 'users' | 'clock' | 'shield' | 'plan' | 'exercise' | 'wallet'
}>()
</script>

<template>
  <div v-if="theme" :class="['premium-stat-card', `theme-${theme}`]">
    <!-- Wave background path based on color theme -->
    <svg class="stat-card-wave" viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
      <path v-if="theme === 'blue'" d="M0,32L120,42.7C240,53,480,75,720,74.7C960,75,1200,53,1320,42.7L1440,32L1440,120L1320,120C1200,120,960,120,720,120C480,120,240,120,120,120L0,120Z" fill="url(#grad-blue)" opacity="0.18"></path>
      <path v-else-if="theme === 'orange'" d="M0,64L120,58.7C240,53,480,43,720,48C960,53,1200,75,1320,85.3L1440,96L1440,120L1320,120C1200,120,960,120,720,120C480,120,240,120,120,120L0,120Z" fill="url(#grad-orange)" opacity="0.14"></path>
      <path v-else-if="theme === 'purple'" d="M0,96L120,85.3C240,75,480,53,720,53.3C960,53,1200,75,1320,85.3L1440,96L1440,120L1320,120C1200,120,960,120,720,120C480,120,240,120,120,120L0,120Z" fill="url(#grad-purple)" opacity="0.18"></path>
      <defs>
        <linearGradient id="grad-blue" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="#365A82"></stop>
          <stop offset="100%" stop-color="#B7D3F4"></stop>
        </linearGradient>
        <linearGradient id="grad-orange" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="#DB854F"></stop>
          <stop offset="100%" stop-color="#F4E8E3"></stop>
        </linearGradient>
        <linearGradient id="grad-purple" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="#9333ea"></stop>
          <stop offset="100%" stop-color="#f3e8ff"></stop>
        </linearGradient>
      </defs>
    </svg>

    <div class="stat-header">
      <p class="stat-label-caps">{{ label }}</p>
      <div v-if="icon" class="stat-icon-wrapper">
        <svg v-if="icon === 'users'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
          <circle cx="9" cy="7" r="4"></circle>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
        <svg v-else-if="icon === 'clock'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <polyline points="12 6 12 12 16 14"></polyline>
        </svg>
        <svg v-else-if="icon === 'shield'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
        </svg>
        <svg v-else-if="icon === 'plan'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
          <polyline points="14 2 14 8 20 8"></polyline>
          <line x1="16" y1="13" x2="8" y2="13"></line>
          <line x1="16" y1="17" x2="8" y2="17"></line>
        </svg>
        <svg v-else-if="icon === 'exercise'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
        </svg>
        <svg v-else-if="icon === 'wallet'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 12V7H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14v4"></path>
          <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"></path>
          <path d="M18 12a2 2 0 0 0 0 4h4v-4z"></path>
        </svg>
      </div>
    </div>
    <div class="stat-body">
      <p class="stat-huge-value">{{ value }}</p>
      <div v-if="trend" :class="['stat-footer-trend', trendClass || 'trend-up']">
        <span v-if="trendClass === 'trend-up'">↗</span>
        <span>{{ trend }}</span>
      </div>
    </div>
  </div>

  <section v-else class="stat-card">
    <p class="stat-label">{{ label }}</p>
    <p class="stat-value">{{ value }}</p>
    <p v-if="hint" class="stat-hint">{{ hint }}</p>
  </section>
</template>

