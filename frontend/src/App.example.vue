<script setup lang="ts">
import { ref } from 'vue'
import CookieConsent from '@/components/CookieConsent.vue'
import SiteFooter from '@/components/SiteFooter.vue'

const cookieConsentRef = ref<InstanceType<typeof CookieConsent> | null>(null)

function openCookieSettings() {
  cookieConsentRef.value?.openCookieSettingsFromFooter()
}
</script>

<template>
  <RouterView />

  <SiteFooter @open-cookie-settings="openCookieSettings" />

  <CookieConsent ref="cookieConsentRef" />
</template>
