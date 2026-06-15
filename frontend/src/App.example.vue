<script setup lang="ts">
import { ref } from 'vue'
import CookieConsent from '@/shared/components/privacy/CookieConsent.vue'
import SiteFooter from '@/shared/components/layout/SiteFooter.vue'

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
