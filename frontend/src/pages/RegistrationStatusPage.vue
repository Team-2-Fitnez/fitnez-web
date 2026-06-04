<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { manualRegistrationApi } from '../api/manualRegistrationApi'
import type { ProspectiveRegistration } from '../types/membership'
import FitnezButton from '../components/ui/FitnezButton.vue'
import FitnezCard from '../components/ui/FitnezCard.vue'
import FitnezInput from '../components/ui/FitnezInput.vue'
import StatusBadge from '../components/ui/StatusBadge.vue'

const registrationCode = ref(localStorage.getItem('fitnez_last_registration_code') || '')
const email = ref(localStorage.getItem('fitnez_last_registration_email') || '')
const registration = ref<ProspectiveRegistration | null>(null)
const loading = ref(false)
const error = ref('')

async function checkStatus() {
  error.value = ''

  if (!registrationCode.value.trim() || !email.value.trim()) {
    error.value = 'Registration code and email are required.'
    return
  }

  loading.value = true

  try {
    const response = await manualRegistrationApi.status(registrationCode.value.trim(), email.value.trim())
    registration.value = response.data
    localStorage.setItem('fitnez_last_registration_code', registrationCode.value.trim())
    localStorage.setItem('fitnez_last_registration_email', email.value.trim())
  } catch (e: any) {
    if (e?.status === 422) {
      const messages = e?.payload?.errors ? Object.values(e.payload.errors).flat().join('; ') : null
      error.value = messages || 'Invalid registration code or email.'
    } else {
      error.value = e?.message || 'Registration status not found.'
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (registrationCode.value.trim() && email.value.trim()) checkStatus()
})
</script>

<template>
  <main class="page-center">
    <FitnezCard style="width: min(100%, 900px);">
      <div style="display: grid; gap: 1.25rem;">
        <div>
          <p class="eyebrow">Registration Status</p>
          <h1 class="title-lg">Track your application.</h1>
          <p class="text-muted">Enter your registration code and email.</p>
        </div>

        <div class="choice-grid">
          <FitnezInput v-model="registrationCode" label="Registration Code" />
          <FitnezInput v-model="email" label="Email" type="email" />
        </div>

        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
          <FitnezButton :disabled="loading" @click="checkStatus">
            {{ loading ? 'Checking...' : 'Check Status' }}
          </FitnezButton>
          <RouterLink to="/register" class="button button-ghost">Back to Register</RouterLink>
        </div>

        <p v-if="error" class="alert alert-error">{{ error }}</p>

        <section v-if="registration" class="panel">
          <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem; align-items: start;">
            <div>
              <p class="stat-label" style="word-break: break-word;">{{ registration.registration_code }}</p>
              <h2 class="title-md">{{ registration.full_name }}</h2>
              <p class="text-muted">{{ registration.email }}</p>
            </div>
            <StatusBadge :status="registration.status" />
          </div>

          <div class="feature-grid" style="margin-top: 1rem;">
            <div class="panel" style="background: var(--color-cream);">
              <p class="stat-label">Amount</p>
              <p class="title-md">Rp {{ Number(registration.amount).toLocaleString('en-US') }}</p>
            </div>
            <div class="panel" style="background: var(--color-cream);">
              <p class="stat-label">Status</p>
              <p class="title-md" style="text-transform: capitalize;">{{ registration.status.replaceAll('_', ' ') }}</p>
            </div>
            <div class="panel" style="background: var(--color-cream);">
              <p class="stat-label">Next Step</p>
              <p class="title-md">
                <span v-if="registration.status === 'approved'">Login now</span>
                <span v-else-if="registration.status === 'awaiting_admin_review'">Wait for admin</span>
                <span v-else-if="registration.status === 'awaiting_payment'">Upload proof</span>
                <span v-else>Contact admin</span>
              </p>
            </div>
          </div>

          <p v-if="registration.rejection_reason" class="alert alert-error" style="margin-top: 1rem;">
            {{ registration.rejection_reason }}
          </p>

          <RouterLink
            v-if="registration.status === 'approved'"
            to="/login/member"
            class="button button-primary"
            style="margin-top: 1rem;"
          >
            Login Now
          </RouterLink>
        </section>
      </div>
    </FitnezCard>
  </main>
</template>
