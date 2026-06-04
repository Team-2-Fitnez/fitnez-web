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
    title: 'Essential / Strictly Necessary',
    description:
      'Required for login, security, session, page navigation, and basic FitNez website features. This category cannot be turned off.',
    locked: true,
  },
  {
    key: 'analytics' as CookieCategory,
    title: 'Analytics / Statistics',
    description:
      'Helps us understand frequently opened pages, errors occurred, and website performance without selling user personal data.',
    locked: false,
  },
  {
    key: 'marketing' as CookieCategory,
    title: 'Marketing / Advertising',
    description:
      'Used to measure promotional campaigns, advertising, and more relevant content recommendations.',
    locked: false,
  },
  {
    key: 'preferences' as CookieCategory,
    title: 'Preferences / Functional',
    description:
      'Saves layout options, language, filters, or other preferences to make the user experience more comfortable.',
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
            <h2 id="cookie-title">Cookie Settings</h2>
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
            <h3>This website uses cookies</h3>

            <p>
              We use cookies to run essential website features, store
              preferences, analyze performance, and support marketing activities. Cookie
              non-essential like analytics and advertising will remain blocked by default
              until you give consent.
            </p>

            <p>
              You can accept all, reject all non-essential cookies, or set
              preferences by category. Consent can be changed at any time via the
              <strong>Cookie Settings</strong> link in the website footer.
            </p>

            <a class="cookie-policy-link" href="/privacy-cookie-policy">
              View Privacy & Cookie Policy
            </a>
          </section>

          <section v-if="activeTab === 'details'" class="cookie-section">
            <h3>Configure Cookie Preferences</h3>

            <p>
              Choose which categories of cookies to allow. Essential cookies are always active because
              they are needed for security, login, and basic website functions.
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
                  {{ item.locked ? 'Always Active' : categories[item.key] ? 'Active' : 'Inactive' }}
                </button>
              </article>
            </div>

            <p class="cookie-current-choice">
              Current active choices: {{ currentConsentText }}
            </p>
          </section>

          <section v-if="activeTab === 'about'" class="cookie-section">
            <h3>About Cookies and Data Security</h3>

            <p>
              Cookies are small files stored in the browser to remember sessions,
              preferences, or specific activities. At FitNez, non-essential cookies must not be
              active before consent is given.
            </p>

            <p>
              Storage and transmission of cookie data must be done via HTTPS so that data
              is not easily read or modified by other parties during transit between the browser and
              the server.
            </p>

            <p>
              For audit purposes, the server can store anonymous consent logs containing an anonymous ID,
              consent timestamp, policy version, and consented categories.
            </p>
          </section>
        </main>

        <footer class="cookie-actions">
          <button type="button" class="cookie-button equal" @click="acceptAll">
            Accept All
          </button>

          <button type="button" class="cookie-button secondary" @click="openPreferences">
            Configure Preferences
          </button>

          <button type="button" class="cookie-button equal" @click="rejectAll">
            Reject All
          </button>

          <button
            v-if="showPreferencePanel || activeTab === 'details'"
            type="button"
            class="cookie-button save"
            @click="saveSelected"
          >
            Save Preferences
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
