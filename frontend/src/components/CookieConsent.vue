<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import {
  defaultConsentCategories,
  getStoredConsent,
  saveConsent,
  type CookieCategory,
} from '@/utils/cookieConsent'

type TabName = 'consent' | 'details' | 'about'

const isVisible = ref(false)
const activeTab = ref<TabName>('consent')
const showPreferencePanel = ref(false)

const categories = ref<Record<CookieCategory, boolean>>({
  ...defaultConsentCategories,
})

const categoryItems = [
  {
    key: 'essential' as CookieCategory,
    title: 'Esensial / Sangat Diperlukan',
    description:
      'Dibutuhkan untuk login, keamanan, session, navigasi halaman, dan fitur dasar website FitNez. Kategori ini tidak bisa dimatikan.',
    locked: true,
  },
  {
    key: 'analytics' as CookieCategory,
    title: 'Analitik / Statistik',
    description:
      'Membantu kami memahami halaman yang sering dibuka, error yang terjadi, dan performa website tanpa menjual data pribadi pengguna.',
    locked: false,
  },
  {
    key: 'marketing' as CookieCategory,
    title: 'Pemasaran / Iklan',
    description:
      'Dipakai untuk mengukur kampanye promosi, iklan, dan rekomendasi konten yang lebih relevan.',
    locked: false,
  },
  {
    key: 'preferences' as CookieCategory,
    title: 'Preferensi / Fungsional',
    description:
      'Menyimpan pilihan tampilan, bahasa, filter, atau preferensi lain agar pengalaman pengguna lebih nyaman.',
    locked: false,
  },
]

const currentConsentText = computed(() => {
  const enabled = Object.entries(categories.value)
    .filter(([, value]) => value)
    .map(([key]) => key)
    .join(', ')

  return enabled || 'essential'
})

onMounted(() => {
  const stored = getStoredConsent()

  if (!stored) {
    isVisible.value = true
    return
  }

  categories.value = {
    essential: true,
    analytics: Boolean(stored.categories.analytics),
    marketing: Boolean(stored.categories.marketing),
    preferences: Boolean(stored.categories.preferences),
  }
})

function openPreferences() {
  activeTab.value = 'details'
  showPreferencePanel.value = true
}

function acceptAll() {
  saveConsent({
    essential: true,
    analytics: true,
    marketing: true,
    preferences: true,
  })
  isVisible.value = false
}

function rejectAll() {
  saveConsent({
    essential: true,
    analytics: false,
    marketing: false,
    preferences: false,
  })
  isVisible.value = false
}

function saveSelected() {
  saveConsent(categories.value)
  isVisible.value = false
}

function toggleCategory(category: CookieCategory) {
  if (category === 'essential') return

  categories.value = {
    ...categories.value,
    [category]: !categories.value[category],
  }
}

function openCookieSettingsFromFooter() {
  const stored = getStoredConsent()

  if (stored) {
    categories.value = {
      essential: true,
      analytics: Boolean(stored.categories.analytics),
      marketing: Boolean(stored.categories.marketing),
      preferences: Boolean(stored.categories.preferences),
    }
  }

  activeTab.value = 'details'
  showPreferencePanel.value = true
  isVisible.value = true
}

defineExpose({
  openCookieSettingsFromFooter,
})
</script>

<template>
  <Teleport to="body">
    <section
      v-if="isVisible"
      class="cookie-overlay"
      aria-labelledby="cookie-title"
      role="dialog"
      aria-modal="true"
    >
      <div class="cookie-modal">
        <header class="cookie-header">
          <div>
            <p class="cookie-brand">FitNez Privacy Center</p>
            <h2 id="cookie-title">Pengaturan Cookie</h2>
          </div>

          <nav class="cookie-tabs" aria-label="Cookie information tabs">
            <button
              type="button"
              :class="{ active: activeTab === 'consent' }"
              @click="activeTab = 'consent'"
            >
              Consent
            </button>
            <button
              type="button"
              :class="{ active: activeTab === 'details' }"
              @click="activeTab = 'details'"
            >
              Details
            </button>
            <button
              type="button"
              :class="{ active: activeTab === 'about' }"
              @click="activeTab = 'about'"
            >
              About
            </button>
          </nav>
        </header>

        <main class="cookie-body">
          <section v-if="activeTab === 'consent'" class="cookie-section">
            <h3>Website ini menggunakan cookies</h3>

            <p>
              Kami menggunakan cookie untuk menjalankan fitur esensial website, menyimpan
              preferensi, menganalisis performa, dan mendukung aktivitas pemasaran. Cookie
              non-esensial seperti analitik dan iklan akan tetap diblokir secara default
              sampai kamu memberi persetujuan.
            </p>

            <p>
              Kamu dapat menerima semua, menolak semua cookie non-esensial, atau mengatur
              preferensi berdasarkan kategori. Persetujuan dapat diubah kapan saja melalui
              tautan <strong>Pengaturan Cookie</strong> di footer website.
            </p>

            <a class="cookie-policy-link" href="/privacy-cookie-policy">
              Lihat Kebijakan Privasi & Cookie
            </a>
          </section>

          <section v-if="activeTab === 'details'" class="cookie-section">
            <h3>Atur Preferensi Cookie</h3>

            <p>
              Pilih kategori cookie yang boleh digunakan. Cookie esensial selalu aktif karena
              dibutuhkan untuk keamanan, login, dan fungsi dasar website.
            </p>

            <div class="cookie-category-list">
              <article
                v-for="item in categoryItems"
                :key="item.key"
                class="cookie-category-card"
              >
                <div>
                  <h4>{{ item.title }}</h4>
                  <p>{{ item.description }}</p>
                </div>

                <button
                  type="button"
                  class="cookie-switch"
                  :class="{ enabled: categories[item.key], locked: item.locked }"
                  :aria-pressed="categories[item.key]"
                  :disabled="item.locked"
                  @click="toggleCategory(item.key)"
                >
                  <span />
                  {{ item.locked ? 'Selalu Aktif' : categories[item.key] ? 'Aktif' : 'Nonaktif' }}
                </button>
              </article>
            </div>

            <p class="cookie-current-choice">
              Pilihan aktif saat ini: {{ currentConsentText }}
            </p>
          </section>

          <section v-if="activeTab === 'about'" class="cookie-section">
            <h3>Tentang Cookie dan Keamanan Data</h3>

            <p>
              Cookie adalah file kecil yang disimpan di browser untuk mengingat sesi,
              preferensi, atau aktivitas tertentu. Di FitNez, cookie non-esensial tidak boleh
              aktif sebelum persetujuan diberikan.
            </p>

            <p>
              Penyimpanan dan pengiriman data cookie wajib dilakukan melalui HTTPS agar data
              tidak mudah dibaca atau dimodifikasi pihak lain saat berpindah antara browser dan
              server.
            </p>

            <p>
              Untuk audit, server dapat menyimpan log persetujuan anonim berisi ID anonim,
              waktu persetujuan, versi kebijakan, dan kategori yang disetujui.
            </p>
          </section>
        </main>

        <footer class="cookie-actions">
          <button type="button" class="cookie-button equal" @click="acceptAll">
            Terima Semua
          </button>

          <button type="button" class="cookie-button secondary" @click="openPreferences">
            Atur Preferensi
          </button>

          <button type="button" class="cookie-button equal" @click="rejectAll">
            Tolak Semua
          </button>

          <button
            v-if="showPreferencePanel || activeTab === 'details'"
            type="button"
            class="cookie-button save"
            @click="saveSelected"
          >
            Simpan Pilihan
          </button>
        </footer>
      </div>
    </section>
  </Teleport>
</template>

<style scoped>
.cookie-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: grid;
  place-items: center;
  padding: 24px;
  background: rgba(18, 18, 18, 0.42);
  backdrop-filter: blur(6px);
}

.cookie-modal {
  width: min(720px, 100%);
  max-height: min(92vh, 820px);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  color: #141414;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(255, 246, 230, 0.97)),
    #fff4df;
  border: 1px solid rgba(22, 22, 22, 0.08);
  border-radius: 24px;
  box-shadow: 0 26px 80px rgba(0, 0, 0, 0.24);
}

.cookie-header {
  display: grid;
  grid-template-columns: 1fr;
  gap: 18px;
  padding: 24px 28px 0;
}

.cookie-brand {
  margin: 0 0 4px;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #7b4b06;
}

.cookie-header h2 {
  margin: 0;
  font-size: clamp(1.4rem, 3vw, 2rem);
  line-height: 1.2;
}

.cookie-tabs {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  border-bottom: 1px solid rgba(20, 20, 20, 0.13);
}

.cookie-tabs button {
  padding: 14px 10px;
  border: 0;
  border-bottom: 3px solid transparent;
  background: transparent;
  color: #141414;
  font: inherit;
  font-weight: 800;
  cursor: pointer;
}

.cookie-tabs button.active {
  border-color: #f6a21a;
  color: #8c5300;
}

.cookie-body {
  overflow-y: auto;
  padding: 26px 28px;
}

.cookie-section h3 {
  margin: 0 0 14px;
  font-size: clamp(1.2rem, 2.2vw, 1.55rem);
}

.cookie-section p {
  margin: 0 0 16px;
  line-height: 1.75;
  color: #252525;
}

.cookie-policy-link {
  display: inline-flex;
  margin-top: 4px;
  color: #111;
  font-weight: 800;
  text-decoration: underline;
  text-decoration-color: #f6a21a;
  text-decoration-thickness: 3px;
  text-underline-offset: 5px;
}

.cookie-category-list {
  display: grid;
  gap: 14px;
  margin-top: 18px;
}

.cookie-category-card {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 18px;
  align-items: center;
  padding: 16px;
  background: rgba(255, 255, 255, 0.68);
  border: 1px solid rgba(20, 20, 20, 0.1);
  border-radius: 18px;
}

.cookie-category-card h4 {
  margin: 0 0 6px;
  font-size: 1rem;
}

.cookie-category-card p {
  margin: 0;
  font-size: 0.93rem;
  line-height: 1.55;
}

.cookie-switch {
  min-width: 124px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 12px;
  border: 2px solid #141414;
  border-radius: 999px;
  background: #fff;
  color: #141414;
  font-weight: 800;
  cursor: pointer;
}

.cookie-switch span {
  width: 14px;
  height: 14px;
  border-radius: 999px;
  background: #a0a0a0;
}

.cookie-switch.enabled {
  background: #f6a21a;
}

.cookie-switch.enabled span {
  background: #141414;
}

.cookie-switch.locked {
  cursor: not-allowed;
  opacity: 0.82;
}

.cookie-current-choice {
  margin-top: 16px !important;
  font-size: 0.9rem;
  font-weight: 700;
}

.cookie-actions {
  display: grid;
  gap: 12px;
  padding: 20px 28px 28px;
  background: rgba(255, 255, 255, 0.5);
  border-top: 1px solid rgba(20, 20, 20, 0.1);
}

.cookie-button {
  width: 100%;
  min-height: 54px;
  border: 2px solid #141414;
  border-radius: 14px;
  background: #f6a21a;
  color: #141414;
  font: inherit;
  font-weight: 900;
  cursor: pointer;
  transition:
    transform 0.15s ease,
    box-shadow 0.15s ease,
    background 0.15s ease;
}

.cookie-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 22px rgba(120, 72, 0, 0.18);
  background: #ffb238;
}

.cookie-button.equal {
  background: #f6a21a;
}

.cookie-button.secondary {
  background: #f6a21a;
}

.cookie-button.save {
  background: #141414;
  color: #f6a21a;
}

@media (min-width: 700px) {
  .cookie-header {
    grid-template-columns: 1fr;
  }

  .cookie-actions {
    grid-template-columns: repeat(3, 1fr);
  }

  .cookie-button.save {
    grid-column: 1 / -1;
  }
}

@media (max-width: 520px) {
  .cookie-overlay {
    padding: 10px;
    align-items: end;
  }

  .cookie-modal {
    border-radius: 22px 22px 0 0;
    max-height: 94vh;
  }

  .cookie-header,
  .cookie-body,
  .cookie-actions {
    padding-left: 18px;
    padding-right: 18px;
  }

  .cookie-category-card {
    grid-template-columns: 1fr;
  }

  .cookie-switch {
    width: 100%;
  }
}
</style>
