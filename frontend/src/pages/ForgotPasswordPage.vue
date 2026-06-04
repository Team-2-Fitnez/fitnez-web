<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { authService } from '../services/authService'
import FitnezButton from '../components/ui/FitnezButton.vue'
import FitnezCard from '../components/ui/FitnezCard.vue'
import FitnezInput from '../components/ui/FitnezInput.vue'

const router = useRouter()
const email = ref('')
const otp = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const otpSent = ref(false)
const loading = ref(false)
const error = ref('')
const message = ref('')

async function requestOtp() {
  error.value = ''
  message.value = ''
  loading.value = true

  try {
    await authService.forgotPassword(email.value)
    otpSent.value = true
    message.value = 'OTP sent. In local development, check the Laravel log if the mail driver uses log.'
  } catch (e: any) {
    error.value = e?.message || 'Failed to request password reset OTP.'
  } finally {
    loading.value = false
  }
}

async function resetPassword() {
  error.value = ''
  message.value = ''
  loading.value = true

  try {
    await authService.resetPassword(email.value, otp.value, password.value, passwordConfirmation.value)
    message.value = 'Password reset successful. Redirecting to login.'
    setTimeout(() => router.push('/login/member'), 800)
  } catch (e: any) {
    error.value = e?.message || 'Failed to reset password.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="page-center">
    <div style="width: min(100%, 460px);">
      <FitnezCard>
        <form v-if="!otpSent" style="display: grid; gap: 1.25rem;" @submit.prevent="requestOtp">
          <div style="text-align: center;">
            <p class="eyebrow">Password Recovery</p>
            <h1 class="title-lg">Forgot password?</h1>
            <p class="text-muted" style="margin-top: 1rem;">
              Enter your email. Fitnez will send an OTP only for resetting your password.
            </p>
          </div>

          <FitnezInput v-model="email" label="Email" type="email" />
          <p v-if="error" class="alert alert-error">{{ error }}</p>
          <p v-if="message" class="alert alert-info">{{ message }}</p>

          <FitnezButton type="submit" :disabled="loading" style="width: 100%;">
            {{ loading ? 'Please wait...' : 'Send Reset OTP' }}
          </FitnezButton>

          <RouterLink to="/login/member" style="font-size: 0.875rem; font-weight: 800; color: var(--color-blue-dark); text-decoration: underline;">
            Back to login
          </RouterLink>
        </form>

        <form v-else style="display: grid; gap: 1.25rem;" @submit.prevent="resetPassword">
          <div style="text-align: center;">
            <p class="eyebrow">Reset Password</p>
            <h1 class="title-lg">Enter OTP and new password.</h1>
          </div>

          <FitnezInput v-model="email" label="Email" type="email" disabled />
          <FitnezInput v-model="otp" label="OTP Code" placeholder="6-digit code" />
          <FitnezInput v-model="password" label="New Password" type="password" />
          <FitnezInput v-model="passwordConfirmation" label="Confirm New Password" type="password" />

          <p v-if="error" class="alert alert-error">{{ error }}</p>
          <p v-if="message" class="alert alert-info">{{ message }}</p>

          <FitnezButton type="submit" :disabled="loading" style="width: 100%;">
            {{ loading ? 'Please wait...' : 'Reset Password' }}
          </FitnezButton>
        </form>
      </FitnezCard>
    </div>
  </main>
</template>
