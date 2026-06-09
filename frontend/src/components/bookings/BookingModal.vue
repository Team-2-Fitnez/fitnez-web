<template>
  <div class="modal-card">
    <!-- Step 1: Booking Form -->
    <template v-if="step === 'form'">
      <div class="modal-header">
        <div class="booking-icon-wrapper">
          <span class="material-symbols-outlined">calendar_month</span>
        </div>
        <div>
          <p class="eyebrow" style="margin: 0; font-size: 0.7rem;">Monthly Subscription</p>
          <h2 class="title-md" style="margin: 0; font-size: 1.25rem;">{{ trainer.name }}</h2>
          <p class="text-muted" style="margin-top: 0.15rem; font-size: 0.8rem;">
            Rate: <span style="font-weight: 800;">{{ formatPrice(trainer.member_price) }}/session</span>
          </p>
        </div>
      </div>

      <form @submit.prevent="onSubmit" class="form-grid">
        <div class="form-field">
          <label class="form-label">Start Date</label>
          <input v-model="start_date" v-bind="start_dateProps" type="date" :min="today" class="form-input" :class="{ 'input-error': errors.start_date }" />
          <p v-if="errors.start_date" class="field-error">{{ errors.start_date }}</p>
        </div>

        <div class="form-field">
          <label class="form-label">Sessions Per Week</label>
          <div class="choice-grid" style="grid-template-columns: repeat(3, 1fr); gap: 0.5rem;">
            <button
              v-for="n in ([3, 5, 7] as const)"
              :key="n"
              type="button"
              :class="['choice-card', weeklySessionCount === n && 'choice-card-active']"
              @click="setSessionsPerWeek(n)"
            >
              <p style="font-weight: 900; margin: 0; font-size: 1rem;">{{ n }}×</p>
              <p class="text-muted" style="font-size: 0.7rem; margin: 0;">/ week</p>
            </button>
          </div>
        </div>

        <div class="form-field">
          <label class="form-label">Choose {{ weeklySessionCount }} day(s)</label>
          <div class="day-grid">
            <button
              v-for="day in allDays"
              :key="day.value"
              type="button"
              :class="['day-chip', selectedSessionDays.includes(day.value) && 'day-chip-active']"
              :disabled="!selectedSessionDays.includes(day.value) && selectedSessionDays.length >= weeklySessionCount"
              @click="toggleDay(day.value)"
            >
              {{ day.label }}
            </button>
          </div>
          <p v-if="errors.session_days" class="field-error">{{ errors.session_days }}</p>
        </div>

        <div class="form-field">
          <label class="form-label">Session Time</label>
          <input v-model="session_time" v-bind="session_timeProps" type="time" class="form-input" />
        </div>

        <div class="form-field">
          <label class="form-label">Additional Notes <span style="opacity: 0.4; font-weight: 400;">(optional)</span></label>
          <textarea v-model="member_notes" rows="2" class="form-input" style="resize: none;" placeholder="Training goals, health conditions, etc..." />
        </div>

        <div class="price-breakdown">
          <div class="price-row">
            <span>Price per session</span>
            <strong>{{ formatPrice(trainer.member_price) }}</strong>
          </div>
          <div class="price-row">
            <span>Base price (trainer rate)</span>
            <strong>{{ formatPrice(trainer.base_price) }}</strong>
          </div>
          <div class="price-row">
            <span>Total sessions ({{ weeklySessionCount }} × 4 weeks)</span>
            <strong>{{ totalSessions }}</strong>
          </div>
          <div class="price-row total">
            <span>Total Monthly Payment</span>
            <strong class="text-orange">{{ formatPrice(totalPrice) }}</strong>
          </div>
        </div>

        <div class="form-actions">
          <button type="button" class="button button-ghost" @click="$emit('close')">Cancel</button>
          <button type="submit" class="button button-primary" :disabled="loading">
            {{ loading ? 'Creating...' : 'Create Booking' }}
          </button>
        </div>
      </form>
    </template>

    <!-- Step 2: Payment Upload -->
    <template v-else-if="step === 'payment'">
      <div class="modal-header">
        <div class="booking-icon-wrapper" style="background: rgba(251, 191, 36, 0.12); color: #d97706;">
          <span class="material-symbols-outlined">payments</span>
        </div>
        <div>
          <p class="eyebrow" style="margin: 0; font-size: 0.7rem;">Payment Required</p>
          <h2 class="title-md" style="margin: 0; font-size: 1.25rem;">Complete Payment</h2>
        </div>
      </div>

      <div class="payment-info">
        <div class="payment-detail">
          <span>Trainer</span>
          <strong>{{ trainer.name }}</strong>
        </div>
        <div class="payment-detail">
          <span>Total to Pay</span>
          <strong class="text-orange" style="font-size: 1.25rem;">{{ formatPrice(createdBooking?.total_member_price || 0) }}</strong>
        </div>
        <div class="payment-detail">
          <span>Period</span>
          <strong>{{ createdBooking?.start_date }} – {{ createdBooking?.end_date }}</strong>
        </div>
      </div>

      <div class="payment-instructions">
        <p class="instructions-title">How to Pay</p>
        <ol class="instructions-list">
          <li>Transfer <strong>{{ formatPrice(createdBooking?.total_member_price || 0) }}</strong> to one of the following methods:</li>
          <li><strong>QRIS</strong> — Scan the QR code below</li>
          <li><strong>Bank Transfer</strong> — BCA 1234567890 a.n. PT Fitnez Sehat Indonesia</li>
        </ol>
      </div>

      <div class="upload-section">
        <label class="form-label">Upload Payment Proof</label>
        <div
          class="upload-zone"
          :class="{ 'has-file': paymentFile, dragging }"
          @dragover.prevent="dragging = true"
          @dragleave="dragging = false"
          @drop.prevent="onDrop"
        >
          <input
            ref="fileInput"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            hidden
            @change="onFileChange"
          />
          <template v-if="!paymentFile">
            <span class="material-symbols-outlined upload-icon">cloud_upload</span>
            <p>Drag & drop screenshot here, or <button type="button" class="link-btn" @click="browseFiles">browse</button></p>
            <small>JPG, PNG, or WebP. Max 4MB.</small>
          </template>
          <template v-else>
            <span class="material-symbols-outlined upload-icon success">check_circle</span>
            <p>{{ paymentFile.name }}</p>
            <small>{{ (paymentFile.size / 1024).toFixed(0) }} KB</small>
          </template>
        </div>
        <p v-if="uploadError" class="field-error">{{ uploadError }}</p>
      </div>

      <div class="form-actions">
        <button type="button" class="button button-ghost" @click="$emit('close')">Close</button>
        <button
          type="button"
          class="button button-primary"
          :disabled="!paymentFile || uploading"
          @click="uploadPayment"
        >
          {{ uploading ? 'Uploading...' : 'Submit Payment Proof' }}
        </button>
      </div>
    </template>

    <!-- Step 3: Success -->
    <template v-else-if="step === 'success'">
      <div class="modal-header" style="justify-content: center; text-align: center;">
        <div style="display: grid; gap: 1rem; place-items: center; width: 100%;">
          <div style="background: rgba(34, 197, 94, 0.12); color: #16a34a; width: 4rem; height: 4rem; border-radius: 50%; display: grid; place-items: center;">
            <span class="material-symbols-outlined" style="font-size: 2rem;">check_circle</span>
          </div>
          <div>
            <h2 class="title-md" style="margin: 0;">Payment Proof Submitted</h2>
            <p class="text-muted" style="margin-top: 0.5rem;">Admin will verify your payment within 2x24 hours. You'll be notified once confirmed.</p>
          </div>
        </div>
      </div>
      <div class="form-actions" style="justify-content: center;">
        <button type="button" class="button button-primary" @click="emitDone">Done</button>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { useBookingStore } from '../../stores/bookingStore'
import type { PublicTrainer, MonthlyBookingPayload } from '../../api/bookingsApi'
import type { TrainerBooking } from '../../types/masterData'

const props = defineProps<{ trainer: PublicTrainer }>()
const emit = defineEmits<{ close: []; booked: [] }>()

const store = useBookingStore()
const today = new Date().toISOString().split('T')[0]

const allDays = [
  { label: 'Mon', value: 'monday' },
  { label: 'Tue', value: 'tuesday' },
  { label: 'Wed', value: 'wednesday' },
  { label: 'Thu', value: 'thursday' },
  { label: 'Fri', value: 'friday' },
  { label: 'Sat', value: 'saturday' },
  { label: 'Sun', value: 'sunday' },
]

const step = ref<'form' | 'payment' | 'success'>('form')
const loading = ref(false)
const createdBooking = ref<TrainerBooking | null>(null)
const paymentFile = ref<File | null>(null)
const uploading = ref(false)
const uploadError = ref('')
const dragging = ref(false)
const fileInput = ref<HTMLInputElement>()

const schema = toTypedSchema(z.object({
  start_date: z.string().min(1, 'Please select a start date.'),
  sessions_per_week: z.number().refine((v) => [3, 5, 7].includes(v), 'Choose 3, 5, or 7 sessions.'),
  session_days: z.array(z.string()).min(1, 'Select at least one day.'),
  session_time: z.string().min(1, 'Please select a session time.'),
  member_notes: z.string().optional().default(''),
}).refine(
  (data) => data.session_days.length === data.sessions_per_week,
  { message: 'Select exactly {{sessions_per_week}} days.', path: ['session_days'] },
))

const { handleSubmit, errors, setFieldError, defineField, setFieldValue } = useForm({
  validationSchema: schema,
  initialValues: {
    start_date: '',
    sessions_per_week: 3,
    session_days: [] as string[],
    session_time: '09:00',
    member_notes: '',
  },
})

const [start_date, start_dateProps] = defineField('start_date')
const [sessions_per_week] = defineField('sessions_per_week')
const [session_days] = defineField('session_days')
const [session_time, session_timeProps] = defineField('session_time')
const [member_notes] = defineField('member_notes')

const weeklySessionCount = computed(() => sessions_per_week.value ?? 3)
const selectedSessionDays = computed<string[]>(() => session_days.value ?? [])
const totalSessions = computed(() => weeklySessionCount.value * 4)
const totalPrice = computed(() => (props.trainer.member_price || 0) * totalSessions.value)

function setSessionsPerWeek(value: 3 | 5 | 7) {
  setFieldValue('sessions_per_week', value)
  setFieldValue('session_days', selectedSessionDays.value.slice(0, value))
}

function toggleDay(day: string) {
  const current: string[] = session_days.value || []
  if (current.includes(day)) {
    setFieldValue('session_days', current.filter((d) => d !== day))
  } else if (current.length < (sessions_per_week.value || 3)) {
    setFieldValue('session_days', [...current, day])
  }
}

function formatPrice(n: number | string | null | undefined) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(Number(n || 0))
}

function browseFiles() {
  fileInput.value?.click()
}

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement
  if (target.files?.length) {
    paymentFile.value = target.files[0]
    uploadError.value = ''
  }
}

function onDrop(e: DragEvent) {
  dragging.value = false
  if (e.dataTransfer?.files.length) {
    paymentFile.value = e.dataTransfer.files[0]
    uploadError.value = ''
  }
}

async function uploadPayment() {
  if (!paymentFile.value || !createdBooking.value) return
  uploading.value = true
  uploadError.value = ''
  try {
    await store.uploadPaymentProof(createdBooking.value.id, paymentFile.value)
    step.value = 'success'
  } catch (e: any) {
    uploadError.value = e?.message || 'Upload failed. Please try again.'
  } finally {
    uploading.value = false
  }
}

function emitDone() {
  emit('booked')
}

const onSubmit = handleSubmit(async (formValues) => {
  loading.value = true
  try {
    const res = await store.createBooking({
      trainer_id: props.trainer.id,
      start_date: formValues.start_date,
      sessions_per_week: formValues.sessions_per_week,
      session_days: formValues.session_days,
      session_time: formValues.session_time,
      member_notes: formValues.member_notes,
    } as MonthlyBookingPayload) as any
    createdBooking.value = res?.data || null
    step.value = 'payment'
  } catch (e: any) {
    setFieldError('start_date', e?.message || 'Failed to create booking.')
  } finally {
    loading.value = false
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
  flex-shrink: 0;
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
  box-sizing: border-box;
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
  border: 2px solid transparent;
  border-radius: 0.75rem;
  cursor: pointer;
  padding: 0.75rem 1rem;
  transition: all 0.2s ease;
  text-align: center;
}

.choice-card:hover {
  background: #f1f5f9;
}

.choice-card-active {
  background: rgba(54, 90, 130, 0.08);
  border-color: var(--color-blue);
}

.day-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.day-chip {
  padding: 0.4rem 0.7rem;
  border-radius: 0.6rem;
  border: 2px solid transparent;
  background: #f1f5f9;
  font-weight: 700;
  font-size: 0.75rem;
  cursor: pointer;
  transition: all 0.15s ease;
}

.day-chip-active {
  background: var(--color-blue);
  color: white;
}

.day-chip:disabled:not(.day-chip-active) {
  opacity: 0.4;
  cursor: not-allowed;
}

.price-breakdown {
  background: #f8fafc;
  border-radius: 0.75rem;
  padding: 1rem;
  display: grid;
  gap: 0.5rem;
}

.price-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
}

.price-row.total {
  border-top: 1px dashed #ccc;
  padding-top: 0.5rem;
  font-size: 1rem;
}

.text-orange {
  color: var(--color-orange);
}

.form-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  margin-top: 0.5rem;
}

.payment-info {
  background: #f8fafc;
  border-radius: 0.75rem;
  padding: 1rem;
  display: grid;
  gap: 0.5rem;
}

.payment-detail {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  align-items: center;
}

.payment-instructions {
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 0.75rem;
  padding: 1rem;
}

.instructions-title {
  font-weight: 800;
  font-size: 0.85rem;
  margin-bottom: 0.5rem;
}

.instructions-list {
  margin: 0;
  padding-left: 1.25rem;
  font-size: 0.8rem;
  display: grid;
  gap: 0.35rem;
  color: #475569;
}

.upload-section {
  display: flex;
  flex-direction: column;
}

.upload-zone {
  border: 2px dashed #cbd5e1;
  border-radius: 0.75rem;
  padding: 1.5rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s ease;
  background: #f8fafc;
  display: grid;
  gap: 0.35rem;
  place-items: center;
}

.upload-zone:hover,
.upload-zone.dragging {
  border-color: var(--color-blue);
  background: #f0f4ff;
}

.upload-zone.has-file {
  border-color: #22c55e;
  background: #f0fdf4;
}

.upload-icon {
  font-size: 2rem !important;
  color: #94a3b8;
}

.upload-icon.success {
  color: #22c55e;
}

.upload-zone p {
  font-size: 0.85rem;
  font-weight: 600;
  margin: 0;
}

.upload-zone small {
  font-size: 0.7rem;
  color: #64748b;
}

.link-btn {
  background: none;
  border: none;
  color: var(--color-blue);
  font-weight: 700;
  cursor: pointer;
  text-decoration: underline;
  padding: 0;
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

@media (max-width: 640px) {
  .modal-card {
    border-radius: 1rem;
    gap: 1rem;
    max-height: calc(100dvh - 1.5rem);
    padding: 1rem;
    width: 100%;
  }

  .modal-header,
  .price-breakdown,
  .form-actions,
  .payment-info,
  .payment-instructions {
    align-items: stretch;
    flex-direction: column;
  }

  .choice-grid {
    grid-template-columns: 1fr !important;
  }

  .form-actions .button {
    width: 100%;
  }
}
</style>
