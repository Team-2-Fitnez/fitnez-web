<template>
  <div class="modal-card">
    <div class="modal-header">
      <div class="booking-icon-wrapper">
        <span class="material-symbols-outlined">event_available</span>
      </div>
      <div>
        <p class="eyebrow" style="margin: 0; font-size: 0.7rem;">Session Booking</p>
        <h2 class="title-md" style="margin: 0; font-size: 1.25rem;">{{ trainer.name }}</h2>
        <p class="text-muted" style="margin-top: 0.15rem; font-size: 0.8rem;">
          Tarif: <span style="font-weight: 800; color: var(--color-blue-dark);">{{ formatPrice(trainer.price) }}/jam</span>
        </p>
      </div>
    </div>

    <form @submit.prevent="onSubmit" class="form-grid">
      <div class="form-field">
        <label class="form-label">Tanggal Sesi</label>
        <input v-model="booking_date" v-bind="booking_dateProps" type="date" :min="today" class="form-input" :class="{ 'input-error': errors.booking_date }" />
        <p v-if="errors.booking_date" class="field-error">{{ errors.booking_date }}</p>
      </div>

      <div class="time-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
        <div class="form-field">
          <label class="form-label">Jam Mulai</label>
          <input v-model="start_time" v-bind="start_timeProps" type="time" class="form-input" :class="{ 'input-error': errors.start_time }" />
          <p v-if="errors.start_time" class="field-error">{{ errors.start_time }}</p>
        </div>
        <div class="form-field">
          <label class="form-label">Jam Selesai</label>
          <input v-model="end_time" v-bind="end_timeProps" type="time" class="form-input" :class="{ 'input-error': errors.end_time }" />
          <p v-if="errors.end_time" class="field-error">{{ errors.end_time }}</p>
        </div>
      </div>

      <div class="form-field">
        <label class="form-label">Tipe Sesi</label>
        <div class="choice-grid session-type-grid" style="grid-template-columns: 1fr 1fr; gap: 0.75rem;">
          <button
            v-for="type in (['online', 'offline'] as const)"
            :key="type"
            type="button"
            :class="['choice-card', session_type === type && 'choice-card-active']"
            @click="session_type = type"
          >
            <p style="font-weight: 900; text-transform: capitalize; margin: 0; font-size: 0.9rem;">{{ type }}</p>
            <p class="text-muted" style="font-size: 0.75rem; margin: 0.15rem 0 0;">
              {{ type === 'online' ? 'Zoom / Meet' : 'Face to Face' }}
            </p>
          </button>
        </div>
      </div>

      <div v-if="session_type === 'offline'" class="form-field animate-slide-down">
        <label class="form-label">Lokasi Pertemuan</label>
        <input v-model="location" v-bind="locationProps" type="text" class="form-input" placeholder="Nama gym atau alamat detail..." />
      </div>

      <div class="form-field">
        <label class="form-label">Catatan Tambahan <span style="opacity: 0.4; font-weight: 400;">(opsional)</span></label>
        <textarea v-model="member_notes" v-bind="member_notesProps" rows="2" class="form-input" style="resize: none;" placeholder="Target latihan, kondisi kesehatan, dll..." />
      </div>

      <div v-if="estimatedPrice > 0" class="price-panel">
        <div>
          <p class="stat-label" style="font-size: 0.75rem; margin: 0; color: var(--color-muted);">Estimasi Biaya</p>
          <p class="text-muted" style="font-size: 0.75rem; margin: 0.15rem 0 0;">{{ durationHours }} jam × {{ formatPrice(trainer.price) }}</p>
        </div>
        <p style="font-weight: 900; font-size: 1.25rem; color: var(--color-orange); margin: 0;">{{ formatPrice(estimatedPrice) }}</p>
      </div>

      <div class="form-actions">
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
  booking_date: z.string().min(1, 'Pilih tanggal sesi.'),
  start_time: z.string().min(1, 'Pilih jam mulai.'),
  end_time: z.string().min(1, 'Pilih jam selesai.'),
  session_type: z.enum(['online', 'offline']),
  location: z.string().optional().default(''),
  member_notes: z.string().optional().default(''),
}).refine(data => {
  if (data.end_time <= data.start_time) return false
  return true
}, { message: 'Jam selesai harus setelah jam mulai.', path: ['end_time'] }))

const { handleSubmit, errors, isSubmitting, setFieldError, defineField } = useForm({
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

const [booking_date, booking_dateProps] = defineField('booking_date')
const [start_time, start_timeProps] = defineField('start_time')
const [end_time, end_timeProps] = defineField('end_time')
const [session_type] = defineField('session_type')
const [location, locationProps] = defineField('location')
const [member_notes, member_notesProps] = defineField('member_notes')

const durationHours = computed(() => {
  if (!start_time.value || !end_time.value) return 0
  const [sh, sm] = start_time.value.split(':').map(Number)
  const [eh, em] = end_time.value.split(':').map(Number)
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
      location: formValues.session_type === 'offline' ? (formValues.location || 'Gym Utama') : 'Online Sesi',
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
  gap: 1.5rem;
  border-radius: 1.5rem;
  background: rgba(255, 255, 255, 0.98);
  border: 1px solid rgba(15, 23, 42, 0.08);
  padding: 2rem;
  box-shadow: 0 24px 60px rgba(11, 28, 48, 0.18);
  max-height: 90vh;
  overflow-y: auto;
  animation: scaleUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal-header {
  display: flex;
  gap: 1rem;
  align-items: center;
  border-bottom: 1px solid rgba(0,0,0,0.06);
  padding-bottom: 1rem;
}

.booking-icon-wrapper {
  background: rgba(54, 90, 130, 0.1);
  color: var(--color-blue);
  width: 3rem;
  height: 3rem;
  border-radius: 0.75rem;
  display: grid;
  place-items: center;
}

.form-grid {
  display: grid;
  gap: 1.25rem;
}

.form-field {
  display: flex;
  flex-direction: column;
}

.form-label {
  display: block;
  font-weight: 800;
  margin-bottom: 0.4rem;
  font-size: 0.85rem;
  color: var(--color-blue-dark);
}

.form-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid rgba(0, 0, 0, 0.15);
  border-radius: 0.75rem;
  font-size: 0.9rem;
  font-weight: 600;
  background-color: #f8fafc;
  color: var(--color-black);
  transition: all 0.2s ease;
}

.form-input:focus {
  border-color: var(--color-blue);
  background-color: white;
  box-shadow: 0 0 0 4px rgba(183, 211, 244, 0.5);
  outline: none;
}

.input-error {
  border-color: #ef4444 !important;
}

.field-error {
  color: #ef4444;
  font-size: 0.75rem;
  margin-top: 0.25rem;
  font-weight: 600;
}

.choice-grid {
  display: grid;
}

.choice-card {
  background: #f8fafc;
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 0.75rem;
  cursor: pointer;
  padding: 0.75rem 1rem;
  transition: all 0.2s ease;
  text-align: left;
  border: 2px solid transparent;
}

.choice-card:hover {
  background: #f1f5f9;
}

.choice-card-active {
  background: rgba(54, 90, 130, 0.08);
  border-color: var(--color-blue);
}

.price-panel {
  background: #f8fafc;
  border: 1px dashed rgba(54, 90, 130, 0.3);
  border-radius: 0.75rem;
  padding: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.form-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  margin-top: 0.5rem;
}

.button {
  align-items: center;
  border-radius: 0.75rem;
  display: inline-flex;
  font-size: 0.875rem;
  font-weight: 900;
  justify-content: center;
  min-height: 2.5rem;
  padding: 0.6rem 1.25rem;
  transition: 160ms ease;
  border: none;
  cursor: pointer;
}

.button-ghost {
  background: white;
  border: 1px solid rgba(0, 0, 0, 0.15);
  color: var(--color-muted);
}

.button-ghost:hover {
  background: #f1f5f9;
}

.button-primary {
  background: var(--color-blue);
  color: white;
}

.button-primary:hover {
  background: var(--color-blue-dark);
}

.button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@keyframes scaleUp {
  from { transform: scale(0.95); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

.animate-slide-down {
  animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
  from { transform: translateY(-10px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

@media (max-width: 640px) {
  .modal-card {
    border-radius: 1rem;
    gap: 1rem;
    max-height: calc(100dvh - 1.5rem);
    padding: 1rem;
    width: 100%;
  }

  .modal-header,
  .price-panel,
  .form-actions {
    align-items: stretch;
    flex-direction: column;
  }

  .time-grid,
  .session-type-grid {
    grid-template-columns: 1fr !important;
  }

  .form-actions .button {
    width: 100%;
  }
}
</style>
