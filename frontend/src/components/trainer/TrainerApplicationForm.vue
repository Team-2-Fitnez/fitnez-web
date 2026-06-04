<template>
  <div style="background: white; border-radius: 12px; padding: 1.5rem;">
    <div style="margin-bottom: 1.5rem;">
      <h3 style="font-weight: 600; color: #1a1a1a; margin-bottom: 0.25rem;">Apply as Trainer</h3>
      <p style="font-size: 0.875rem; color: #666;">Complete form data for admin review</p>
    </div>

    <form @submit.prevent="onSubmit" style="display: grid; gap: 1rem;">
      <div class="form-field">
        <label class="form-label">Specialization</label>
        <input
          v-model="form.specialization"
          type="text"
          placeholder="Strength Training, Yoga, HIIT..."
          class="form-input"
          required
        />
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
        <div class="form-field">
          <label class="form-label">Experience (years)</label>
          <input
            v-model.number="form.experience_years"
            type="number"
            min="0"
            max="50"
            class="form-input"
            required
          />
        </div>
        <div class="form-field">
          <label class="form-label">Hourly Rate (IDR)</label>
          <input
            v-model.number="form.hourly_rate"
            type="number"
            min="10000"
            step="10000"
            placeholder="100000"
            class="form-input"
            required
          />
        </div>
      </div>

      <div class="form-field">
        <label class="form-label">Short Bio</label>
        <textarea
          v-model="form.biography"
          rows="3"
          placeholder="Tell us about your experience and training methods..."
          class="form-input"
          style="resize: none;"
        />
      </div>

      <p v-if="error" class="alert alert-error">{{ error }}</p>
      <p v-if="success" class="alert alert-success">Application successfully sent. Admin will review your application.</p>

      <button type="submit" class="button button-primary" :disabled="submitting">
        {{ submitting ? 'Sending...' : 'Submit Application' }}
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { trainerApplicationApi } from '../../api/trainerApplicationApi'

const submitting = ref(false)
const error = ref('')
const success = ref(false)

const form = ref({
  specialization: '',
  experience_years: 0,
  hourly_rate: 100000,
  biography: '',
})

async function onSubmit() {
  error.value = ''
  success.value = false

  if (!form.value.specialization || form.value.experience_years < 0 || form.value.hourly_rate < 10000) {
    error.value = 'Please complete all fields correctly.'
    return
  }

  submitting.value = true
  try {
    await trainerApplicationApi.apply(form.value)
    success.value = true
    form.value = { specialization: '', experience_years: 0, hourly_rate: 100000, biography: '' }
  } catch (e: any) {
    error.value = e?.message || 'Failed to submit application.'
  } finally {
    submitting.value = false
  }
}
</script>
