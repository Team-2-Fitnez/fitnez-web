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
        <input v-model="form.booking_date" type="date" :min="today" class="form-input" required />
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
        <div class="form-field">
          <label class="form-label">Jam Mulai</label>
          <input v-model="form.start_time" type="time" class="form-input" required />
        </div>
        <div class="form-field">
          <label class="form-label">Jam Selesai</label>
          <input v-model="form.end_time" type="time" class="form-input" required />
        </div>
      </div>

      <div class="form-field">
        <label class="form-label">Tipe Sesi</label>
        <div class="choice-grid" style="grid-template-columns: 1fr 1fr;">
          <button
            v-for="type in ['online', 'offline']"
            :key="type"
            type="button"
            :class="['choice-card', form.session_type === type && 'choice-card-active']"
            @click="form.session_type = type"
          >
            <p style="font-weight: 900; text-transform: capitalize;">{{ type }}</p>
            <p class="text-muted" style="font-size: 0.75rem;">
              {{ type === 'online' ? 'Zoom / Meet' : 'Tatap Muka' }}
            </p>
          </button>
        </div>
      </div>

      <div v-if="form.session_type === 'offline'" class="form-field">
        <label class="form-label">Lokasi</label>
        <input v-model="form.location" type="text" class="form-input" placeholder="Nama gym atau alamat..." />
      </div>

      <div class="form-field">
        <label class="form-label">Catatan <span style="opacity: 0.4; font-weight: 400;">(opsional)</span></label>
        <textarea v-model="form.member_notes" rows="2" class="form-input" style="resize: none;" placeholder="Tujuan latihan, kondisi kesehatan, dll..." />
      </div>

      <div v-if="estimatedPrice > 0" class="panel" style="background: var(--color-cream); display: flex; justify-content: space-between; align-items: center;">
        <div>
          <p class="stat-label" style="font-size: 0.75rem;">Estimasi Biaya</p>
          <p class="text-muted" style="font-size: 0.75rem;">{{ durationHours }} jam × {{ formatPrice(trainer.price) }}</p>
        </div>
        <p style="font-weight: 900; font-size: 1.25rem; color: var(--color-orange);">{{ formatPrice(estimatedPrice) }}</p>
      </div>

      <p v-if="error" class="alert alert-error">{{ error }}</p>

      <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
        <button type="button" class="button button-ghost" @click="$emit('close')">Batal</button>
        <button type="submit" class="button button-primary" :disabled="submitting">
          {{ submitting ? 'Memproses...' : 'Konfirmasi Booking' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useBookingStore } from '../../stores/bookingStore'
import type { PublicTrainer } from '../../api/bookingsApi'

const props = defineProps<{ trainer: PublicTrainer }>()
const emit = defineEmits<{ close: []; booked: [] }>()

const store = useBookingStore()
const submitting = ref(false)
const error = ref('')
const today = new Date().toISOString().split('T')[0]

const form = ref({
  booking_date: '',
  start_time: '09:00',
  end_time: '10:00',
  session_type: 'online',
  location: '',
  member_notes: '',
})

const durationHours = computed(() => {
  if (!form.value.start_time || !form.value.end_time) return 0
  const [sh, sm] = form.value.start_time.split(':').map(Number)
  const [eh, em] = form.value.end_time.split(':').map(Number)
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

async function onSubmit() {
  error.value = ''

  if (!form.value.booking_date) {
    error.value = 'Tanggal wajib diisi.'
    return
  }
  if (form.value.end_time <= form.value.start_time) {
    error.value = 'Waktu selesai harus setelah waktu mulai.'
    return
  }

  submitting.value = true
  try {
    await store.createBooking({
      trainer_id: props.trainer.id,
      booking_date: form.value.booking_date,
      start_time: form.value.start_time,
      end_time: form.value.end_time,
      session_type: form.value.session_type,
      location: form.value.location || 'Gym Utama',
      member_notes: form.value.member_notes,
      total_price: estimatedPrice.value,
    })
    
    alert('✅ Booking berhasil dibuat!\n\nPermintaan Anda telah dikirim ke trainer. Mohon menunggu konfirmasi dari trainer.\n\nAnda dapat melihat status booking di halaman Schedule.')
    emit('booked')
  } catch (e: any) {
    error.value = e?.message || 'Gagal membuat booking.'
  } finally {
    submitting.value = false
  }
}
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
</style>
