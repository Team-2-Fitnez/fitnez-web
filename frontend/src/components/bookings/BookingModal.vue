<template>
  <div class="modal-card">
    <div>
      <p class="eyebrow">Booking Sesi</p>
      <h2 class="title-md">{{ trainer.name }}</h2>
      <p class="text-muted" style="margin-top: 0.25rem;">
        {{ formatPrice(trainer.price) }}/jam
      </p>
    </div>

    <form @submit.prevent="onSubmit" style="display: grid; gap: 1rem;">
      <div class="form-field">
        <label class="form-label">Tanggal Sesi</label>
        <input v-model="values.booking_date" type="date" :min="today" class="form-input" :class="{ 'input-error': errors.booking_date }" />
        <p v-if="errors.booking_date" class="field-error">{{ errors.booking_date }}</p>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
        <div class="form-field">
          <label class="form-label">Jam Mulai</label>
          <input v-model="values.start_time" type="time" class="form-input" :class="{ 'input-error': errors.start_time }" />
          <p v-if="errors.start_time" class="field-error">{{ errors.start_time }}</p>
        </div>
        <div class="form-field">
          <label class="form-label">Jam Selesai</label>
          <input v-model="values.end_time" type="time" class="form-input" :class="{ 'input-error': errors.end_time }" />
          <p v-if="errors.end_time" class="field-error">{{ errors.end_time }}</p>
        </div>
      </div>

      <div class="form-field">
        <label class="form-label">Tipe Sesi</label>
        <div class="choice-grid" style="grid-template-columns: 1fr 1fr;">
          <button
            v-for="type in ['online', 'offline']"
            :key="type"
            type="button"
            :class="['choice-card', values.session_type === type && 'choice-card-active']"
            @click="values.session_type = type"
          >
            <p style="font-weight: 900; text-transform: capitalize;">{{ type }}</p>
            <p class="text-muted" style="font-size: 0.75rem;">
              {{ type === 'online' ? 'Zoom / Meet' : 'Tatap Muka' }}
            </p>
          </button>
        </div>
      </div>

      <div v-if="values.session_type === 'offline'" class="form-field">
        <label class="form-label">Lokasi</label>
        <input v-model="values.location" type="text" class="form-input" placeholder="Nama gym atau alamat..." />
      </div>

      <div class="form-field">
        <label class="form-label">Catatan <span style="opacity: 0.4; font-weight: 400;">(opsional)</span></label>
        <textarea v-model="values.member_notes" rows="2" class="form-input" style="resize: none;" placeholder="Tujuan latihan, kondisi kesehatan, dll..." />
      </div>

      <div v-if="estimatedPrice > 0" class="panel" style="background: var(--color-cream); display: flex; justify-content: space-between; align-items: center;">
        <div>
          <p class="stat-label" style="font-size: 0.75rem;">Estimasi Biaya</p>
          <p class="text-muted" style="font-size: 0.75rem;">{{ durationHours }} jam × {{ formatPrice(trainer.price) }}</p>
        </div>
        <p style="font-weight: 900; font-size: 1.25rem; color: var(--color-orange);">{{ formatPrice(estimatedPrice) }}</p>
      </div>

      <p v-if="errors.booking_date" class="alert alert-error">{{ errors.booking_date }}</p>

      <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
        <button type="button" class="button button-ghost" @click="$emit('close')">Batal</button>
        <button type="submit" class="button button-primary" :disabled="isSubmitting">
          {{ isSubmitting ? 'Memproses...' : 'Konfirmasi Booking' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { useBookingStore } from '../../stores/bookingStore'
import type { PublicTrainer } from '../../api/bookingsApi'

const props = defineProps<{ trainer: PublicTrainer }>()
const emit = defineEmits<{ close: []; booked: [] }>()

const store = useBookingStore()
const today = new Date().toISOString().split('T')[0]

const schema = toTypedSchema(z.object({
  booking_date: z.string().min(1, 'Tanggal wajib diisi.'),
  start_time: z.string().min(1, 'Jam mulai wajib diisi.'),
  end_time: z.string().min(1, 'Jam selesai wajib diisi.'),
  session_type: z.enum(['online', 'offline']),
  location: z.string().optional().default(''),
  member_notes: z.string().optional().default(''),
}).refine(data => {
  if (data.end_time <= data.start_time) return false
  return true
}, { message: 'Waktu selesai harus setelah waktu mulai.', path: ['end_time'] }))

const { handleSubmit, errors, values, isSubmitting, setFieldError } = useForm({
  validationSchema: schema,
  initialValues: {
    booking_date: '',
    start_time: '09:00',
    end_time: '10:00',
    session_type: 'online' as const,
    location: '',
    member_notes: '',
  },
})

const durationHours = computed(() => {
  if (!values.start_time || !values.end_time) return 0
  const [sh, sm] = values.start_time.split(':').map(Number)
  const [eh, em] = values.end_time.split(':').map(Number)
  return Math.max(0, (eh * 60 + em - sh * 60 - sm) / 60)
})

const estimatedPrice = computed(() => Math.round(props.trainer.price * durationHours.value))

function formatPrice(n: number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(n)
}

const onSubmit = handleSubmit(async (formValues) => {
  try {
    await store.createBooking({
      trainer_id: props.trainer.id,
      booking_date: formValues.booking_date,
      start_time: formValues.start_time,
      end_time: formValues.end_time,
      session_type: formValues.session_type,
      location: formValues.location || 'Gym Utama',
      member_notes: formValues.member_notes,
      total_price: estimatedPrice.value,
    })
    emit('booked')
  } catch (e: any) {
    setFieldError('booking_date', e?.message || 'Gagal membuat booking.')
  }
})
</script>

<style scoped>
.modal-card {
  width: min(100%, 480px);
  display: grid;
  gap: 1.25rem;
  border-radius: 1.5rem;
  background: white;
  padding: 1.5rem;
  box-shadow: 0 24px 80px rgba(0, 0, 0, 0.22);
  max-height: 90vh;
  overflow-y: auto;
}
.input-error { border-color: #e53e3e !important; }
.field-error { color: #e53e3e; font-size: 0.75rem; margin-top: 0.25rem; }
</style>
