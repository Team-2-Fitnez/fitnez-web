<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { http } from '@/shared/api/http'
import LandingPublicNav from '@/features/Landing/components/LandingPublicNav.vue'
import SkeletonFaqAccordion from '@/shared/components/ui/skeleton/SkeletonFaqAccordion.vue'
import { useDeferredLoading } from '@/shared/composables/useDeferredLoading'

interface FaqItem {
  id: number
  question: string
  answer: string
  category: string
}

const faqs = ref<FaqItem[]>([])
const categories = ref<string[]>([])
const activeCategory = ref('')
const searchQuery = ref('')
const openId = ref<number | null>(null)
const { loading, run, shimmerStyle } = useDeferredLoading()

const filteredFaqs = computed(() => {
  let result = activeCategory.value
    ? faqs.value.filter(faq => faq.category === activeCategory.value)
    : faqs.value

  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return result

  const tokens = query.split(/\s+/).filter(Boolean)
  if (!tokens.length) return result

  return result
    .map(faq => {
      const question = faq.question.toLowerCase()
      const answer = faq.answer.toLowerCase()
      let score = 0

      if (question.includes(query)) score += 50
      if (answer.includes(query)) score += 10

      const matchedAll = tokens.every(token => {
        const questionMatch = question.includes(token)
        const answerMatch = answer.includes(token)
        if (questionMatch) score += 15
        if (answerMatch) score += 5
        return questionMatch || answerMatch
      })

      if (matchedAll) score += 30
      return { faq, score }
    })
    .filter(item => item.score > 0)
    .sort((a, b) => b.score - a.score)
    .map(item => item.faq)
})

function toggle(id: number) {
  openId.value = openId.value === id ? null : id
}

function highlight(text: string): string {
  const query = searchQuery.value.trim()
  if (!query) return text

  const tokens = query
    .split(/\s+/)
    .filter(Boolean)
    .sort((a, b) => b.length - a.length)
    .map(token => token.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'))

  if (!tokens.length) return text
  return text.replace(new RegExp(`(${tokens.join('|')})`, 'gi'), '<mark class="search-highlight">$1</mark>')
}

onMounted(() => {
  run(async () => {
    try {
      const [faqRes, catRes] = await Promise.all([
        http.get<FaqItem[]>('/faqs'),
        http.get<string[]>('/faqs/categories'),
      ])
      faqs.value = faqRes.data
      categories.value = catRes.data
    } catch {
      window.showFitnezToast('Failed to load FAQs.', 'error')
    }
  })
})
</script>

<template>
  <div class="bg-background text-on-background font-body-md min-h-screen flex flex-col Outfit pt-20">
    <LandingPublicNav variant="static" />

    <!-- Main Content -->
    <main class="flex-grow">
      <!-- Hero Section -->
      <section class="py-section-padding-mobile md:py-section-padding-desktop bg-surface-container-lowest relative overflow-hidden">
        <div class="absolute inset-0 bg-primary/5 pattern-dots pattern-blue-500 pattern-bg-white pattern-size-4 pattern-opacity-10 z-0"></div>
        <div class="max-w-3xl mx-auto text-center relative z-10 px-4">
          <h1 class="font-display-lg text-4xl md:text-[72px] text-primary mb-stack-md leading-tight">Help Center</h1>
          <p class="font-body-lg text-lg md:text-body-lg text-on-surface-variant mb-stack-lg">How can we help you today?</p>
          <div class="relative max-w-xl mx-auto">
            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">search</span>
            <input 
              v-model="searchQuery"
              aria-label="Search for answers" 
              class="w-full pl-12 pr-4 py-4 rounded-xl border border-outline-variant bg-surface-lowest shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all font-body-md text-body-md text-on-surface outline-none" 
              placeholder="Search for answers..." 
              type="text"
            />
          </div>
        </div>
      </section>

      <!-- Categories & FAQ Section -->
      <section class="py-section-padding-mobile md:py-section-padding-desktop bg-background">
        <div class="max-w-4xl mx-auto px-gutter">
          <!-- Category Tabs -->
          <div v-if="categories.length > 0" class="flex overflow-x-auto pb-4 md:pb-0 md:flex-wrap md:justify-center gap-stack-sm mb-stack-lg md:mb-section-padding-mobile no-scrollbar px-4 md:px-0">
            <button 
              @click="activeCategory = ''"
              :class="[
                'flex-shrink-0 px-8 py-3 rounded-full border font-label-bold text-label-bold transition-all min-h-[48px] cursor-pointer',
                activeCategory === ''
                  ? 'border-primary bg-primary text-on-primary hover:bg-surface-tint'
                  : 'border-outline-variant bg-surface-lowest text-secondary hover:border-primary hover:text-primary'
              ]"
            >
              All
            </button>
            <button 
              v-for="cat in categories"
              :key="cat"
              @click="activeCategory = cat"
              :class="[
                'flex-shrink-0 px-8 py-3 rounded-full border font-label-bold text-label-bold transition-all min-h-[48px] cursor-pointer',
                activeCategory === cat
                  ? 'border-primary bg-primary text-on-primary hover:bg-surface-tint'
                  : 'border-outline-variant bg-surface-lowest text-secondary hover:border-primary hover:text-primary'
              ]"
            >
              {{ cat }}
            </button>
          </div>

          <SkeletonFaqAccordion v-if="loading && !faqs.length" :rows="5" :style="shimmerStyle" />

          <!-- FAQ Accordions -->
          <template v-else>
            <div v-if="!filteredFaqs.length" class="text-center py-16 bg-surface-lowest rounded-xl border border-outline-variant p-8">
              <p class="font-headline-lg text-[22px] font-bold text-on-surface mb-2">Questions not found</p>
              <p class="font-body-md text-body-md text-on-surface-variant">
                {{ searchQuery ? 'Try using other keywords.' : 'No FAQs available for this category.' }}
              </p>
            </div>

            <div v-else class="space-y-4" id="faq-container">
              <!-- FAQ Item -->
              <div 
                v-for="faq in filteredFaqs"
                :key="faq.id"
                class="border border-outline-variant rounded-xl bg-surface-lowest overflow-hidden transition-all hover:shadow-sm"
              >
                <button 
                  @click="toggle(faq.id)"
                  class="w-full px-6 py-6 md:py-5 flex justify-between items-center text-left focus:outline-none faq-toggle min-h-[64px] cursor-pointer"
                >
                  <span class="font-headline-lg text-headline-lg text-on-surface !text-[20px] leading-snug" v-html="highlight(faq.question)"></span>
                  <span 
                    class="material-symbols-outlined text-primary transition-transform duration-300"
                    :class="{ 'rotate-45': openId === faq.id }"
                    data-icon="add"
                  >
                    add
                  </span>
                </button>
                <div 
                  v-show="openId === faq.id"
                  class="px-6 pb-5 text-on-surface-variant font-body-md text-body-md faq-content"
                >
                  <p v-html="highlight(faq.answer)"></p>
                </div>
              </div>
            </div>
          </template>
        </div>
      </section>

      <!-- Still have questions? -->
      <section class="py-section-padding-mobile bg-surface-container-low">
        <div class="max-w-3xl mx-auto px-4">
          <div class="bg-surface-lowest rounded-xl p-8 text-center border border-outline-variant shadow-sm relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-primary"></div>
            <span class="material-symbols-outlined text-primary text-5xl mb-4" data-icon="support_agent" style="font-variation-settings: 'FILL' 1;">support_agent</span>
            <h3 class="font-headline-lg text-2xl md:text-headline-lg text-on-surface mb-2 leading-tight">Still have questions?</h3>
            <p class="font-body-md text-base md:text-body-md text-on-surface-variant mb-6">Can't find the answer you're looking for? Please chat to our friendly team.</p>
            <RouterLink to="/login/member" class="inline-flex items-center justify-center gap-2 bg-[#f97316] hover:bg-[#ea580c] text-white font-label-bold text-label-bold py-4 px-10 rounded-full transition-colors duration-200 w-full md:w-auto min-h-[48px]">
              <span class="material-symbols-outlined text-[20px]" data-icon="chat">chat</span>Contact Admin
            </RouterLink>
          </div>
        </div>
      </section>
    </main>

    <!-- Footer (Shared Component, identical to privacy-cookie-policy) -->
    <footer class="bg-on-background dark:bg-on-background w-full mt-auto">
      <div class="flex flex-col md:flex-row justify-between items-center w-full px-gutter py-8 max-w-container-max mx-auto font-body-md text-body-md">
        <div class="font-headline-lg text-headline-lg text-surface-lowest mb-4 md:mb-0">
          Fitnez Gym
        </div>
        <div class="flex flex-wrap justify-center gap-6 text-primary-fixed dark:text-primary-fixed-dim">
          <RouterLink class="text-secondary-fixed-dim hover:text-surface-bright transition-colors duration-200 opacity-80 hover:opacity-100 transition-opacity" to="/">Home</RouterLink>
          <RouterLink class="text-secondary-fixed-dim hover:text-surface-bright transition-colors duration-200 opacity-80 hover:opacity-100 transition-opacity" to="/faq">FAQ</RouterLink>
          <RouterLink class="text-secondary-fixed-dim hover:text-surface-bright transition-colors duration-200 opacity-80 hover:opacity-100 transition-opacity" to="/privacy-cookie-policy">Privacy & Cookie Policy</RouterLink>
        </div>
        <div class="text-secondary-fixed-dim mt-4 md:mt-0 text-sm">
          © 2024 Fitnez Gym. All rights reserved.
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');

.Outfit {
  font-family: 'Outfit', sans-serif !important;
}

/* Typography styles matching custom properties in HTML mockup */
.font-headline-xl {
  font-family: 'Outfit', sans-serif !important;
  font-size: 48px !important;
  line-height: 1.2 !important;
  letter-spacing: -0.02em !important;
  font-weight: 700 !important;
}
.font-headline-lg {
  font-family: 'Outfit', sans-serif !important;
  font-size: 32px !important;
  line-height: 1.3 !important;
  font-weight: 700 !important;
}
.font-body-lg {
  font-family: 'Outfit', sans-serif !important;
  font-size: 20px !important;
  line-height: 1.6 !important;
  font-weight: 400 !important;
}
.font-body-md {
  font-family: 'Outfit', sans-serif !important;
  font-size: 16px !important;
  line-height: 1.6 !important;
  font-weight: 400 !important;
}
.font-label-bold {
  font-family: 'Outfit', sans-serif !important;
  font-size: 14px !important;
  line-height: 1.0 !important;
  letter-spacing: 0.05em !important;
  font-weight: 700 !important;
}

/* Custom color utility classes mapped from tailwind config block */
.bg-surface-bright { background-color: #f8f9ff !important; }
.text-on-surface { color: #0b1c30 !important; }
.text-on-surface-variant { color: #424754 !important; }
.bg-surface-variant { background-color: #d3e4fe !important; }
.bg-surface-container-lowest, .bg-surface-lowest { background-color: #ffffff !important; }
.text-brand-orange { color: #f97316 !important; }
.bg-brand-orange { background-color: #f97316 !important; }
.border-outline-variant { border-color: #c2c6d6 !important; }
.border-outline { border-color: #727785 !important; }
.border-primary { border-color: #005ac2 !important; }
.bg-primary { background-color: #0058be !important; }
.text-primary { color: #0058be !important; }
.bg-on-secondary-fixed { background-color: #131b2e !important; }
.bg-on-background { background-color: #0b1c30 !important; }
.text-surface-lowest { color: #ffffff !important; }
.text-secondary-fixed-dim { color: #bec6e0 !important; }
.text-error { color: #ba1a1a !important; }
.border-error { border-color: #ba1a1a !important; }
.bg-error-container { background-color: #ffdad6 !important; }
.text-on-error-container { color: #93000a !important; }

/* Custom layout/spacing styles matching custom properties in HTML mockup */
.bg-background { background-color: #f8f9ff !important; }
.text-on-background { color: #0b1c30 !important; }
.bg-surface-container-low { background-color: #eff4ff !important; }
.bg-surface-lowest { background-color: #ffffff !important; }
.py-stack-sm { padding-top: 8px !important; padding-bottom: 8px !important; }
.py-stack-lg { padding-top: 32px !important; padding-bottom: 32px !important; }
.gap-stack-md { gap: 16px !important; }
.gap-stack-lg { gap: 32px !important; }
.mb-stack-md { margin-bottom: 16px !important; }
.mb-stack-lg { margin-bottom: 32px !important; }

.px-gutter { padding-left: 24px !important; padding-right: 24px !important; }
.max-w-container-max { max-width: 1280px !important; }
.py-section-padding-mobile { padding-top: 64px !important; padding-bottom: 64px !important; }
.py-section-padding-desktop { padding-top: 120px !important; padding-bottom: 120px !important; }

.scale-102 {
  transform: scale(1.02);
}
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.rotate-45 {
  transform: rotate(45deg);
}

:deep(.search-highlight) {
  background: #fde047 !important;
  color: #1e3a8a !important;
  padding: 0.1rem 0.25rem !important;
  border-radius: 4px !important;
  font-weight: 700 !important;
}

/* Dots pattern helper */
.pattern-dots {
  background-image: radial-gradient(currentColor 1px, transparent 1px) !important;
  background-size: 16px 16px !important;
}

/* 16px margin spacing between dynamic FAQ items */
#faq-container > * + * {
  margin-top: 16px !important;
}

.nav-scrolled {
  background-color: rgba(248, 249, 255, 0.9) !important;
  backdrop-filter: blur(12px) !important;
  border-bottom: 1px solid rgba(194, 198, 214, 0.3) !important;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
}
</style>
