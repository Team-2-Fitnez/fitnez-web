<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { http } from '../api/http'

interface FaqItem {
  id: number
  question: string
  answer: string
  category: string
}

const faqs = ref<FaqItem[]>([])
const categories = ref<string[]>([])
const activeCategory = ref<string>('')
const searchQuery = ref('')
const loading = ref(true)
const openId = ref<number | null>(null)

function toggle(id: number) {
  openId.value = openId.value === id ? null : id
}

const filteredFaqs = computed(() => {
  let result = faqs.value
  if (activeCategory.value) {
    result = result.filter(f => f.category === activeCategory.value)
  }
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(f =>
      f.question.toLowerCase().includes(q) ||
      f.answer.toLowerCase().includes(q)
    )
  }
  return result
})

function highlight(text: string): string {
  if (!searchQuery.value.trim()) return text
  const q = searchQuery.value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  const regex = new RegExp(`(${q})`, 'gi')
  return text.replace(regex, '<mark class="search-highlight">$1</mark>')
}

onMounted(async () => {
  try {
    const [faqRes, catRes] = await Promise.all([
      http.get<FaqItem[]>('/faqs'),
      http.get<string[]>('/faqs/categories'),
    ])
    faqs.value = faqRes.data
    categories.value = catRes.data
  } catch {
    window.showFitnezToast('Gagal memuat FAQ.', 'error')
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <main class="page">
    <div class="faq-hero">
      <div class="page-container" style="text-align: center; padding: 4rem 1rem 3rem;">
        <p class="eyebrow">Help Center</p>
        <h1 class="title-xl" style="margin: 0.5rem 0 0.75rem;">Frequently Asked Questions</h1>
        <p class="text-muted" style="max-width: 36rem; margin: 0 auto 2rem; font-size: 1.05rem;">
          Temukan jawaban seputar pendaftaran, pembayaran, jadwal latihan, dan fitur Fitnez lainnya.
        </p>

        <div class="faq-search-wrapper">
          <svg class="faq-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
          </svg>
          <input
            v-model="searchQuery"
            type="search"
            class="faq-search-input"
            placeholder="Cari pertanyaan atau kata kunci..."
          />
        </div>
      </div>
    </div>

    <div class="page-container" style="max-width: 820px; margin-top: -1rem; padding-bottom: 4rem;">
      <div v-if="loading" style="display: grid; gap: 0.75rem;">
        <div v-for="n in 5" :key="n" class="skeleton-block" style="height: 64px; border-radius: 12px;"></div>
      </div>

      <template v-else>
        <div v-if="categories.length > 1" class="faq-categories" role="tablist" aria-label="Kategori FAQ">
          <button
            :class="['faq-cat-btn', { active: activeCategory === '' }]"
            role="tab"
            :aria-selected="activeCategory === ''"
            @click="activeCategory = ''"
          >Semua</button>
          <button
            v-for="cat in categories"
            :key="cat"
            :class="['faq-cat-btn', { active: activeCategory === cat }]"
            role="tab"
            :aria-selected="activeCategory === cat"
            @click="activeCategory = cat"
          >{{ cat }}</button>
        </div>

        <div v-if="!filteredFaqs.length && !loading" class="faq-empty">
          <p style="font-weight: 700; font-size: 1.1rem;">Pertanyaan tidak ditemukan</p>
          <p class="text-muted" style="margin-top: 0.25rem;">
            {{ searchQuery ? 'Coba gunakan kata kunci lain.' : 'Belum ada FAQ untuk kategori ini.' }}
          </p>
        </div>

        <div v-else class="faq-list" role="list">
          <div
            v-for="faq in filteredFaqs"
            :key="faq.id"
            class="faq-item"
            role="listitem"
          >
            <button
              :id="'faq-btn-' + faq.id"
              class="faq-question"
              :aria-expanded="openId === faq.id"
              :aria-controls="'faq-answer-' + faq.id"
              @click="toggle(faq.id)"
            >
              <span class="faq-question-text" v-html="highlight(faq.question)"></span>
              <span class="faq-chevron" :class="{ rotated: openId === faq.id }">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
              </span>
            </button>
            <div
              :id="'faq-answer-' + faq.id"
              class="faq-answer"
              role="region"
              :aria-labelledby="'faq-btn-' + faq.id"
              :style="{ maxHeight: openId === faq.id ? '400px' : '0', opacity: openId === faq.id ? 1 : 0 }"
            >
              <div class="faq-answer-inner">
                <p v-html="highlight(faq.answer)"></p>
              </div>
            </div>
          </div>
        </div>
      </template>

      <aside class="faq-cta">
        <div class="faq-cta-card">
          <h3 class="title-md">Tidak menemukan jawaban?</h3>
          <p class="text-muted" style="margin: 0.25rem 0 1rem;">
            Hubungi admin melalui chat atau kunjungi halaman profile untuk informasi lebih lanjut.
          </p>
          <RouterLink to="/login/member" class="button button-primary">Hubungi Admin via Chat</RouterLink>
        </div>
      </aside>
    </div>
  </main>
</template>

<style scoped>
.faq-hero {
  background: linear-gradient(135deg, var(--color-cream) 0%, #fff5e6 100%);
  border-bottom: 1px solid var(--color-border);
}

.faq-search-wrapper {
  position: relative;
  max-width: 480px;
  margin: 0 auto;
}

.faq-search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #999;
  pointer-events: none;
}

.faq-search-input {
  width: 100%;
  padding: 0.85rem 1rem 0.85rem 2.75rem;
  border: 2px solid var(--color-border);
  border-radius: 12px;
  font: inherit;
  font-size: 1rem;
  background: white;
  transition: border-color 0.2s, box-shadow 0.2s;
  box-sizing: border-box;
}

.faq-search-input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12);
}

.faq-categories {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 1.5rem;
}

.faq-cat-btn {
  padding: 0.5rem 1.25rem;
  border: 1.5px solid var(--color-border);
  border-radius: 999px;
  background: white;
  font: inherit;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  color: var(--color-muted);
}

.faq-cat-btn:hover {
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.faq-cat-btn.active {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: white;
}

.faq-list {
  display: grid;
  gap: 0.5rem;
}

.faq-item {
  border: 1px solid var(--color-border);
  border-radius: 12px;
  background: white;
  overflow: hidden;
  transition: box-shadow 0.2s;
}

.faq-item:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.faq-question {
  width: 100%;
  padding: 1rem 1.25rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  cursor: pointer;
  background: none;
  border: none;
  font: inherit;
  text-align: left;
  color: inherit;
}

.faq-question-text {
  font-weight: 700;
  font-size: 0.95rem;
  line-height: 1.5;
  flex: 1;
}

.faq-chevron {
  flex-shrink: 0;
  transition: transform 0.25s ease;
  color: var(--color-muted);
}

.faq-chevron.rotated {
  transform: rotate(180deg);
}

.faq-answer {
  max-height: 0;
  opacity: 0;
  overflow: hidden;
  transition: max-height 0.3s ease, opacity 0.25s ease;
}

.faq-answer-inner {
  padding: 0 1.25rem 1.25rem;
  border-top: 1px solid var(--color-border);
  padding-top: 1rem;
  margin: 0 0.25rem;
}

.faq-answer-inner p {
  line-height: 1.7;
  color: var(--color-muted);
}

.faq-empty {
  text-align: center;
  padding: 3rem 1rem;
  background: var(--color-cream);
  border-radius: 12px;
}

.faq-cta {
  margin-top: 2.5rem;
}

.faq-cta-card {
  background: var(--color-cream);
  border-radius: 16px;
  padding: 2rem;
  text-align: center;
  border: 1px solid var(--color-border);
}

:deep(.search-highlight) {
  background: #fef08a;
  color: inherit;
  padding: 0.1rem 0.2rem;
  border-radius: 3px;
}
</style>
