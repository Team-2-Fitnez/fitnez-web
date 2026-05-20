<script setup lang="ts">
import { ref } from 'vue'
import CookieConsent from '@/components/CookieConsent.vue'

const cookieConsentRef = ref<InstanceType<typeof CookieConsent> | null>(null)

function openCookieSettings() {
  cookieConsentRef.value?.openCookieSettingsFromFooter()
}
</script>

<template>
  <RouterView />

  <button type="button" class="cookie-settings-button" @click="openCookieSettings">
    Pengaturan Cookie
  </button>

  <CookieConsent ref="cookieConsentRef" />
</template>

<style scoped>
.cookie-settings-button {
  position: fixed;
  right: 20px;
  bottom: 20px;
  z-index: 9998;
  padding: 12px 18px;
  border: 2px solid #141414;
  border-radius: 999px;
  background: #f6a21a;
  color: #141414;
  font-weight: 800;
  cursor: pointer;
}
</style>