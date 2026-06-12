<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '../stores/authStore'
import FitnezButton from '../components/ui/FitnezButton.vue'
import FitnezCard from '../components/ui/FitnezCard.vue'
import FitnezInput from '../components/ui/FitnezInput.vue'

const auth = useAuthStore()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  loading.value = true

  try {
    await auth.memberLogin(email.value, password.value)
    auth.initialized = true

    if (auth.user?.role === 'admin') {
      window.location.href = '/entries/admin.html'
    } else if (auth.user?.role === 'trainer' || auth.user?.can_access_trainer_workspace) {
      window.location.href = '/entries/trainer.html'
    } else {
      window.location.href = '/entries/member.html'
    }
  } catch (e: any) {
    error.value = e?.message || 'Login failed. Please check your email and password.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="page-center">
    <div style="width: min(100%, 460px);">
      <FitnezCard>
        <form style="display: grid; gap: 1.25rem;" @submit.prevent="submit">
          <div style="text-align: center;">
            <p class="eyebrow">Fitnez Login</p>
            <h1 class="title-lg">Welcome back.</h1>
            <p class="text-muted" style="margin-top: 1rem;">
              Sign in with your registered email and password. Use Forgot password if you need an OTP reset code.
            </p>
          </div>

          <FitnezInput v-model="email" label="Email" type="email" />
          <FitnezInput v-model="password" label="Password" type="password" />

          <p v-if="error" class="alert alert-error">{{ error }}</p>

          <FitnezButton type="submit" :disabled="loading" style="width: 100%;">
            {{ loading ? 'Please wait...' : 'Login' }}
          </FitnezButton>

          <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 0.75rem; font-size: 0.875rem; font-weight: 800;">
            <RouterLink to="/register" style="color: var(--color-blue-dark); text-decoration: underline;">Register</RouterLink>
            <RouterLink to="/forgot-password" style="color: var(--color-blue-dark); text-decoration: underline;">Forgot password?</RouterLink>
            <RouterLink to="/registration-status" style="color: var(--color-blue-dark); text-decoration: underline;">Check status</RouterLink>
          </div>
        </form>
      </FitnezCard>
    </div>
  </main>
</template>
