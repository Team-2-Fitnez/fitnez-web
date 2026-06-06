<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref } from 'vue'
import {
  defaultConsentCategories,
  getStoredConsent,
  saveConsent,
  type CookieCategory,
} from '@/utils/cookieConsent'

const isVisible = ref(false)
const showPreferencePanel = ref(false)

const categories = ref<Record<CookieCategory, boolean>>({
  ...defaultConsentCategories,
})

const categoryItems = [
  {
    key: 'essential' as CookieCategory,
    title: 'Essential',
    description: 'Required for login, security, session, page navigation, and basic website features. This category cannot be turned off.',
    locked: true,
  },
  {
    key: 'analytics' as CookieCategory,
    title: 'Analytics',
    description: 'Helps us understand frequently opened pages, errors occurred, and website performance.',
    locked: false,
  },
  {
    key: 'marketing' as CookieCategory,
    title: 'Marketing',
    description: 'Used to measure promotional campaigns and deliver more relevant content recommendations.',
    locked: false,
  },
  {
    key: 'preferences' as CookieCategory,
    title: 'Functional',
    description: 'Saves layout options, filters, or other preferences to make the user experience more comfortable.',
    locked: false,
  },
]

onMounted(() => {
  const stored = getStoredConsent()

  if (!stored) {
    isVisible.value = true
  } else {
    categories.value = {
      essential: true,
      analytics: Boolean(stored.categories.analytics),
      marketing: Boolean(stored.categories.marketing),
      preferences: Boolean(stored.categories.preferences),
    }
  }

  // Register global listener to open cookie settings from anywhere
  window.addEventListener('open-cookie-settings', openCookieSettingsFromGlobal)
})

onBeforeUnmount(() => {
  window.removeEventListener('open-cookie-settings', openCookieSettingsFromGlobal)
})

function openCookieSettingsFromGlobal() {
  const stored = getStoredConsent()
  if (stored) {
    categories.value = {
      essential: true,
      analytics: Boolean(stored.categories.analytics),
      marketing: Boolean(stored.categories.marketing),
      preferences: Boolean(stored.categories.preferences),
    }
  }
  showPreferencePanel.value = true
  isVisible.value = true
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


function saveSelected() {
  saveConsent(categories.value)
  isVisible.value = false
}

function closePopup() {
  const stored = getStoredConsent()
  if (!stored) {
    saveConsent({
      essential: true,
      analytics: false,
      marketing: false,
      preferences: false,
    })
  }
  isVisible.value = false
}

function openPreferences() {
  showPreferencePanel.value = true
}

defineExpose({
  openCookieSettingsFromFooter: openCookieSettingsFromGlobal
})
</script>

<template>
  <Teleport to="body">
    <div v-if="isVisible" class="cookie-popup-container">
      <div class="cookie-card">
        <!-- Header -->
        <div class="cookie-header">
          <h3 class="cookie-title">Cookies settings</h3>
          <button type="button" class="cookie-close-btn" @click="closePopup" aria-label="Close">
            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div class="cookie-body">
          <p v-if="!showPreferencePanel" class="cookie-text">
            We use cookies and similar technologies to help personalise content, tailor and measure ads, and provide a better experience. By clicking accept, you agree to this, as outlined in our Cookie Policy.
          </p>

          <div v-else class="cookie-pref-panel">
            <div v-for="item in categoryItems" :key="item.key" class="cookie-pref-row">
              <div class="cookie-pref-info">
                <div class="cookie-pref-title-row">
                  <span class="cookie-pref-title">{{ item.title }}</span>
                  <span v-if="item.locked" class="cookie-tag-always">Always Active</span>
                </div>
                <p class="cookie-pref-desc">{{ item.description }}</p>
              </div>
              <div v-if="!item.locked" class="cookie-pref-toggle">
                <label class="ios-switch">
                  <input type="checkbox" v-model="categories[item.key]" />
                  <span class="ios-slider"></span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="cookie-footer">
          <template v-if="!showPreferencePanel">
            <button type="button" class="cookie-btn cookie-btn-accept" @click="acceptAll">
              Accept
            </button>
            <button type="button" class="cookie-btn cookie-btn-preferences" @click="openPreferences">
              Preferences
            </button>
          </template>
          <template v-else>
            <button type="button" class="cookie-btn cookie-btn-save" @click="saveSelected">
              Save Preferences
            </button>
            <button type="button" class="cookie-btn cookie-btn-back" @click="showPreferencePanel = false">
              Back
            </button>
          </template>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.cookie-popup-container {
  position: fixed;
  bottom: 24px;
  right: 24px;
  width: 420px;
  max-width: calc(100vw - 48px);
  z-index: 9999;
  font-family: 'Outfit', sans-serif;
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.cookie-card {
  background: #ffffff;
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
  border: 1px solid rgba(0, 0, 0, 0.08);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.cookie-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 24px 12px;
}

.cookie-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.cookie-close-btn {
  background: none;
  border: none;
  color: #111827;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0.8;
  transition: opacity 0.2s;
}

.cookie-close-btn:hover {
  opacity: 1;
}

.cookie-body {
  padding: 0 24px 20px;
  max-height: 280px;
  overflow-y: auto;
}

.cookie-text {
  font-size: 0.875rem;
  line-height: 1.5;
  color: #374151;
  margin: 0;
}

.cookie-pref-panel {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.cookie-pref-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid #f3f4f6;
}

.cookie-pref-row:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.cookie-pref-info {
  flex: 1;
}

.cookie-pref-title-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.cookie-pref-title {
  font-weight: 700;
  font-size: 0.95rem;
  color: #111827;
}

.cookie-tag-always {
  font-size: 0.7rem;
  font-weight: 700;
  color: #10b981;
  background-color: #ecfdf5;
  padding: 2px 6px;
  border-radius: 4px;
}

.cookie-pref-desc {
  font-size: 0.8rem;
  line-height: 1.4;
  color: #6b7280;
  margin: 0;
}

/* iOS Switch CSS */
.ios-switch {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
  flex-shrink: 0;
}

.ios-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.ios-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #e5e7eb;
  transition: .3s;
  border-radius: 24px;
}

.ios-slider:before {
  position: absolute;
  content: "";
  height: 20px;
  width: 20px;
  left: 2px;
  bottom: 2px;
  background-color: white;
  transition: .3s;
  border-radius: 50%;
  box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}

input:checked + .ios-slider {
  background-color: #1e1b4b;
}

input:checked + .ios-slider:before {
  transform: translateX(20px);
}

.cookie-footer {
  display: flex;
  gap: 12px;
  padding: 0 24px 24px;
}

.cookie-btn {
  border: none;
  font-size: 0.95rem;
  font-weight: 700;
  border-radius: 12px;
  padding: 12px 20px;
  cursor: pointer;
  transition: all 0.2s;
  text-align: center;
}

.cookie-btn-accept {
  background-color: #1e1b4b;
  color: #ffffff;
  flex: 1;
}

.cookie-btn-accept:hover {
  background-color: #111030;
}

.cookie-btn-preferences {
  background-color: #f3f4f6;
  color: #1e1b4b;
  flex: 1;
}

.cookie-btn-preferences:hover {
  background-color: #e5e7eb;
}

.cookie-btn-save {
  background-color: #1e1b4b;
  color: #ffffff;
  flex: 2;
}

.cookie-btn-save:hover {
  background-color: #111030;
}

.cookie-btn-back {
  background-color: #f3f4f6;
  color: #1e1b4b;
  flex: 1;
}

.cookie-btn-back:hover {
  background-color: #e5e7eb;
}

@media (max-width: 640px) {
  .cookie-popup-container {
    bottom: max(0.75rem, env(safe-area-inset-bottom));
    left: max(0.75rem, env(safe-area-inset-left));
    max-width: none;
    right: max(0.75rem, env(safe-area-inset-right));
    width: auto;
  }

  .cookie-card {
    border-radius: 16px;
    max-height: calc(100dvh - 1.5rem);
  }

  .cookie-header {
    align-items: flex-start;
    padding: 18px 18px 10px;
  }

  .cookie-title {
    font-size: 1.2rem;
  }

  .cookie-body {
    overflow-y: auto;
    padding: 0 18px 18px;
  }

  .cookie-pref-row,
  .cookie-pref-title-row,
  .cookie-footer {
    align-items: stretch;
    flex-direction: column;
  }

  .cookie-pref-toggle {
    align-self: flex-start;
  }

  .cookie-footer {
    padding: 0 18px 18px;
  }

  .cookie-btn {
    min-height: 44px;
    width: 100%;
  }
}
</style>
