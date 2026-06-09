<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '../../components/layout/sidebarItems'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import SkeletonCard from '../../components/ui/SkeletonCard.vue'
import { useBookingStore } from '../../stores/bookingStore'
import { useAutoRefresh } from '../../composables/useAutoRefresh'

const store = useBookingStore()
const route = useRoute()

type BookingFilter = 'all' | 'pending' | 'pending_payment' | 'confirmed' | 'completed' | 'cancelled'

const filters: BookingFilter[] = ['all', 'pending', 'pending_payment', 'confirmed', 'completed', 'cancelled']
const filter = ref<BookingFilter>('all')
const selectedDate = ref('')

const now = new Date()
const currentMonth = ref(now.getMonth())
const currentYear = ref(now.getFullYear())

const filterLabels: Record<BookingFilter, string> = {
  all: 'All',
  pending: 'Need Payment',
  pending_payment: 'Pending Review',
  confirmed: 'Active',
  completed: 'Completed',
  cancelled: 'Cancelled',
}

const currentMonthYear = computed(() => {
  return new Date(currentYear.value, currentMonth.value, 1).toLocaleDateString('en-US', {
    month: 'long',
    year: 'numeric',
  })
})

const filteredBookings = computed(() => {
  return store.bookings.filter((booking) => {
    const statusMatch = filter.value === 'all' || booking.status === filter.value
    const dateMatch = !selectedDate.value || (booking.start_date || '').startsWith(selectedDate.value)
    return statusMatch && dateMatch
  })
})

const pendingCount = computed(() => store.bookings.filter(b => b.status === 'pending').length)
const pendingPaymentCount = computed(() => store.bookings.filter(b => b.status === 'pending_payment').length)
const confirmedCount = computed(() => store.bookings.filter(b => b.status === 'confirmed').length)
const completedCount = computed(() => store.bookings.filter(b => b.status === 'completed').length)

const pendingUploadBookingId = ref<number | null>(null)
const uploadFile = ref<File | null>(null)
const uploading = ref(false)
const uploadError = ref('')

async function submitPaymentProof() {
  if (!uploadFile.value || !pendingUploadBookingId.value) return
  uploading.value = true
  uploadError.value = ''
  try {
    await store.uploadPaymentProof(pendingUploadBookingId.value, uploadFile.value)
    pendingUploadBookingId.value = null
    uploadFile.value = null
    window.showFitnezToast('Payment proof submitted. Admin will verify within 2x24 hours.', 'success')
  } catch (e: any) {
    uploadError.value = e?.message || 'Upload failed.'
  } finally {
    uploading.value = false
  }
}

const groupedBookings = computed(() => {
  const groups = new Map<string, typeof store.bookings>()
  filteredBookings.value.forEach((booking) => {
    const key = (booking.start_date || '').substring(0, 10) || 'No date'
    groups.set(key, [...(groups.get(key) || []), booking])
  })

  return Array.from(groups.entries())
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([date, bookings]) => ({
      date,
      label: date === new Date().toISOString().substring(0, 10) ? 'Today' : formatDate(date),
      bookings,
    }))
})

const calendarDays = computed(() => {
  const year = currentYear.value
  const month = currentMonth.value
  const firstDayIndex = new Date(year, month, 1).getDay()
  const totalDays = new Date(year, month + 1, 0).getDate()
  const today = new Date()
  const days: Array<{ day: number | null; dateStr: string; isToday: boolean }> = []

  for (let i = 0; i < firstDayIndex; i++) {
    days.push({ day: null, dateStr: '', isToday: false })
  }

  for (let day = 1; day <= totalDays; day++) {
    const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`
    days.push({
      day,
      dateStr,
      isToday: day === today.getDate() && month === today.getMonth() && year === today.getFullYear(),
    })
  }

  return days
})

// Session dates expand
const expandedSessions = ref(new Set<number>())
const sessionDatesMap = ref<Map<number, string[]>>(new Map())
const sessionTimesMap = ref<Map<number, string>>(new Map())
const sessionLoadingMap = ref<Map<number, boolean>>(new Map())

async function toggleSessions(bookingId: number) {
  if (expandedSessions.value.has(bookingId)) {
    expandedSessions.value.delete(bookingId)
    return
  }
  if (!sessionDatesMap.value.has(bookingId)) {
    sessionLoadingMap.value.set(bookingId, true)
    try {
      const res = await store.fetchSessionDates(bookingId)
      sessionDatesMap.value.set(bookingId, res.dates)
      sessionTimesMap.value.set(bookingId, res.session_time)
    } finally {
      sessionLoadingMap.value.set(bookingId, false)
    }
  }
  expandedSessions.value.add(bookingId)
}

function hasBookingOnDate(dateStr: string): boolean {
  if (!dateStr) return false
  for (const booking of store.bookings) {
    const dates = sessionDatesMap.value.get(booking.id)
    if (dates && dates.includes(dateStr)) return true
  }
  return false
}

function prevMonth() {
  if (currentMonth.value === 0) {
    currentMonth.value = 11
    currentYear.value -= 1
    return
  }
  currentMonth.value -= 1
}

function nextMonth() {
  if (currentMonth.value === 11) {
    currentMonth.value = 0
    currentYear.value += 1
    return
  }
  currentMonth.value += 1
}

function selectDate(dateStr: string) {
  if (!dateStr) return
  selectedDate.value = selectedDate.value === dateStr ? '' : dateStr
}

function getDayStatuses(dateStr: string) {
  if (!dateStr) return []
  return [...new Set(
    store.bookings
      .filter(booking => (booking.start_date || '').startsWith(dateStr))
      .map(booking => booking.status || 'pending_payment')
  )].slice(0, 3)
}

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

function formatPrice(n: number | string | null | undefined) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(Number(n || 0))
}

function statusLabel(value?: string | null) {
  if (value === 'pending') return 'Need Payment'
  if (value === 'pending_payment') return 'Pending Review'
  if (value === 'confirmed') return 'Active'
  if (value === 'completed') return 'Completed'
  if (value === 'cancelled') return 'Cancelled'
  return value || 'Pending'
}

async function cancelBooking(id: number) {
  try {
    await store.updateStatus(id, 'cancelled')
    window.showFitnezToast('Booking successfully cancelled.', 'success')
  } catch {
    window.showFitnezToast('Failed to cancel booking.', 'error')
  }
}

onMounted(() => {
  store.loadBookings()
  if (route.query.booking === 'success') {
    window.showFitnezToast('Booking created! Please make payment to activate.', 'success')
  }
})
useAutoRefresh(() => store.loadBookings(), 8000)
</script>

<template>
  <WorkspaceLayout
    role="member"
    sidebar-title="Member"
    title="Workout Schedule"
    subtitle="View and manage your monthly training subscriptions."
    :sidebar-items="memberSidebarItems"
  >
    <section class="member-schedule-layout">
      <div v-if="pendingCount > 0" class="schedule-alert">
        <span class="material-symbols-outlined">payments</span>
        <div>
          <p>{{ pendingCount }} booking(s) need payment proof</p>
          <small>Upload your payment receipt to proceed.</small>
        </div>
      </div>
      <div v-else-if="pendingPaymentCount > 0" class="schedule-alert" style="background: #eff6ff; border-color: #93c5fd;">
        <span class="material-symbols-outlined">hourglass_top</span>
        <div>
          <p>{{ pendingPaymentCount }} booking(s) waiting for admin confirmation</p>
          <small>Admin will verify your payment within 2x24 hours.</small>
        </div>
      </div>

      <div class="schedule-grid">
        <aside class="schedule-left">
          <article class="schedule-card calendar-card">
            <div class="calendar-header">
              <div>
                <p class="eyebrow">Calendar</p>
                <h3>{{ currentMonthYear }}</h3>
              </div>
              <div class="calendar-nav">
                <button type="button" aria-label="Previous month" @click="prevMonth">
                  <span class="material-symbols-outlined">chevron_left</span>
                </button>
                <button type="button" aria-label="Next month" @click="nextMonth">
                  <span class="material-symbols-outlined">chevron_right</span>
                </button>
              </div>
            </div>

            <div class="weekday-grid">
              <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
            </div>

            <div class="calendar-grid">
              <button
                v-for="(day, index) in calendarDays"
                :key="`${day.dateStr}-${index}`"
                type="button"
                :disabled="!day.day"
                :class="[
                  'day-cell',
                  day.isToday && 'today',
                  selectedDate === day.dateStr && 'selected',
                  getDayStatuses(day.dateStr).length && 'has-booking',
                ]"
                @click="selectDate(day.dateStr)"
              >
                <span v-if="day.day">{{ day.day }}</span>
                <i v-if="day.day" class="dot-row">
                  <b
                    v-for="status in getDayStatuses(day.dateStr)"
                    :key="status"
                    :class="`dot-${status}`"
                  ></b>
                </i>
              </button>
            </div>

            <button v-if="selectedDate" type="button" class="clear-date" @click="selectedDate = ''">
              Show all dates
            </button>
          </article>

          <article class="schedule-card summary-card">
            <p class="eyebrow">Schedule Summary</p>
            <div class="summary-grid">
              <div>
                <strong>{{ store.bookings.length }}</strong>
                <span>Total</span>
              </div>
              <div>
                <strong>{{ pendingCount }}</strong>
                <span>Need Pay</span>
              </div>
              <div>
                <strong>{{ pendingPaymentCount }}</strong>
                <span>Review</span>
              </div>
              <div>
                <strong>{{ confirmedCount }}</strong>
                <span>Active</span>
              </div>
              <div>
                <strong>{{ completedCount }}</strong>
                <span>Done</span>
              </div>
            </div>
          </article>
        </aside>

        <section class="schedule-right schedule-card">
          <div class="list-head">
            <div>
              <p class="eyebrow">Booking List</p>
              <h3>{{ selectedDate ? formatDate(selectedDate) : 'All Bookings' }}</h3>
            </div>
            <router-link to="/member/hire-trainer" class="button button-primary button-small">
              Find Trainer
            </router-link>
          </div>

          <div class="status-tabs">
            <button
              v-for="item in filters"
              :key="item"
              type="button"
              :class="['filter-btn', filter === item && 'active']"
              @click="filter = item"
            >
              {{ filterLabels[item] }}
            </button>
          </div>

          <div v-if="store.loading" class="loading-list">
            <SkeletonCard v-for="n in 3" :key="n" heading :lines="2" wide actions />
          </div>

          <div v-else-if="filteredBookings.length === 0" class="empty-schedule-card">
            <span class="material-symbols-outlined">calendar_today</span>
            <p>
              {{ filter === 'all' ? 'No bookings yet.' : `No bookings with status "${filterLabels[filter]}".` }}
            </p>
            <router-link to="/member/hire-trainer" class="button button-primary">
              Hire a Trainer Now
            </router-link>
          </div>

          <div v-else class="session-groups">
            <div v-for="group in groupedBookings" :key="group.date" class="session-group">
              <div class="date-divider">
                <span></span>
                <div>
                  <strong>{{ group.label }}</strong>
                  <small>{{ group.date }}</small>
                </div>
              </div>

              <article v-for="booking in group.bookings" :key="booking.id" class="session-card">
                <div class="session-body">
                  <div class="session-top">
                    <div>
                      <span class="status-chip">{{ statusLabel(booking.status) }}</span>
                      <h4>{{ booking.trainer?.full_name ?? 'Trainer' }}</h4>
                      <p>{{ booking.sessions_per_week }}×/week · {{ booking.session_time }} · {{ booking.session_days?.join(', ') || '-' }}</p>
                    </div>
                    <StatusBadge :status="booking.status || 'pending_payment'" />
                  </div>

                  <div class="meta-grid">
                    <div>
                      <span>Monthly Cost</span>
                      <strong>{{ formatPrice(booking.total_member_price) }}</strong>
                    </div>
                    <div>
                      <span>Sessions</span>
                      <strong>{{ booking.total_sessions }} total ({{ booking.sessions_per_week }}/week)</strong>
                    </div>
                    <div>
                      <span>Period</span>
                      <strong>{{ booking.start_date }} – {{ booking.end_date }}</strong>
                    </div>
                  </div>

                  <p v-if="booking.member_notes" class="session-note">{{ booking.member_notes }}</p>
                  <p v-if="booking.status === 'pending'" class="status-note pending">Upload payment proof to proceed.</p>
                  <p v-else-if="booking.status === 'pending_payment'" class="status-note pending">Awaiting admin payment verification.</p>
                  <p v-else-if="booking.status === 'confirmed'" class="status-note confirmed">Booking active. Coordinate with your trainer via Chat.</p>

                  <button
                    v-if="booking.status === 'confirmed'"
                    class="button button-ghost button-small"
                    style="margin-top: 0.5rem;"
                    @click="toggleSessions(booking.id)"
                  >
                    {{ expandedSessions.has(booking.id) ? 'Hide' : 'Show' }} Session Dates
                  </button>

                  <div v-if="expandedSessions.has(booking.id)" class="session-dates-list">
                    <div v-if="sessionLoadingMap.get(booking.id)" class="session-date-item loading">
                      Loading sessions...
                    </div>
                    <div
                      v-else
                      v-for="date in (sessionDatesMap.get(booking.id) || [])"
                      :key="date"
                      class="session-date-item"
                    >
                      <span class="material-symbols-outlined">fitness_center</span>
                      <span>{{ formatDate(date) }}</span>
                      <span class="session-time-badge">{{ sessionTimesMap.get(booking.id) || booking.session_time }}</span>
                    </div>
                  </div>

                  <div v-if="booking.status !== 'completed' && booking.status !== 'cancelled'" class="session-actions" style="flex-wrap: wrap;">
                    <button
                      v-if="booking.status === 'pending'"
                      class="button button-primary button-small"
                      type="button"
                      @click="pendingUploadBookingId = booking.id"
                    >
                      Upload Payment Proof
                    </button>
                    <router-link v-if="booking.status === 'confirmed'" :to="`/member/chat?contact=${booking.trainer_id}`" class="button button-primary button-small">
                      Chat Trainer
                    </router-link>
                    <button class="button button-danger-ghost button-small" type="button" @click="cancelBooking(booking.id)">
                      Cancel
                    </button>
                  </div>
                </div>
              </article>
            </div>
          </div>
        </section>
      </div>
    </section>

    <!-- Upload Payment Proof Modal -->
    <Teleport to="body">
      <div v-if="pendingUploadBookingId" class="modal-backdrop" @click.self="pendingUploadBookingId = null">
        <div class="modal-card">
          <div class="modal-header">
            <div class="modal-icon">
              <span class="material-symbols-outlined">cloud_upload</span>
            </div>
            <div>
              <p class="eyebrow" style="margin: 0; font-size: 0.7rem;">Payment Proof</p>
              <h2 class="title-md" style="margin: 0; font-size: 1.15rem;">Upload Transfer Receipt</h2>
            </div>
          </div>

          <div class="upload-section">
            <label class="form-label">Screenshot / Photo of Payment</label>
            <div class="upload-zone" :class="{ 'has-file': uploadFile }" @click="($refs.uf as HTMLInputElement)?.click()">
              <input ref="uf" type="file" accept="image/jpeg,image/png,image/webp" hidden @change="(e: any) => { const f = e.target?.files?.[0]; if (f) { uploadFile = f; uploadError = '' } }" />
              <template v-if="!uploadFile">
                <span class="material-symbols-outlined upload-icon">cloud_upload</span>
                <p>Tap to select screenshot</p>
                <small>JPG, PNG, or WebP. Max 4MB.</small>
              </template>
              <template v-else>
                <span class="material-symbols-outlined upload-icon success">check_circle</span>
                <p>{{ uploadFile.name }}</p>
                <small>{{ (uploadFile.size / 1024).toFixed(0) }} KB</small>
              </template>
            </div>
            <p v-if="uploadError" class="field-error">{{ uploadError }}</p>
          </div>

          <div class="form-actions">
            <button type="button" class="button button-ghost" @click="pendingUploadBookingId = null; uploadFile = null; uploadError = ''">
              Cancel
            </button>
            <button type="button" class="button button-primary" :disabled="!uploadFile || uploading" @click="submitPaymentProof">
              {{ uploading ? 'Uploading...' : 'Submit Proof' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </WorkspaceLayout>
</template>

<style scoped>
.member-schedule-layout {
  display: grid;
  gap: 1.25rem;
}

.schedule-alert {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 1rem;
  align-items: center;
}

.schedule-alert p {
  font-weight: 800;
  font-size: 0.9rem;
  margin: 0;
}

.schedule-alert small {
  font-size: 0.75rem;
  color: #92400e;
}

.schedule-grid {
  display: grid;
  grid-template-columns: 320px 1fr;
  gap: 1.25rem;
  align-items: start;
}

.schedule-left {
  display: grid;
  gap: 1rem;
}

.schedule-card {
  background: white;
  border-radius: 1.25rem;
  padding: 1.25rem;
  border: 1px solid rgba(0,0,0,0.06);
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.calendar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.calendar-header h3 {
  font-size: 1rem;
  font-weight: 900;
  margin: 0.15rem 0 0;
}

.weekday-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  text-align: center;
  font-size: 0.7rem;
  font-weight: 700;
  color: #64748b;
  margin-bottom: 0.5rem;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 2px;
}

.day-cell {
  aspect-ratio: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 0.5rem;
  background: transparent;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
  transition: 0.15s ease;
  position: relative;
}

.day-cell:hover:not(:disabled) {
  background: #f1f5f9;
}

.day-cell.today {
  background: var(--color-blue);
  color: white;
}

.day-cell.selected {
  outline: 2px solid var(--color-blue);
  outline-offset: -2px;
}

.day-cell:disabled {
  opacity: 0.2;
}

.dot-row {
  display: flex;
  gap: 2px;
  margin-top: 2px;
}

.dot-row b {
  width: 5px;
  height: 5px;
  border-radius: 50%;
}

.dot-pending_payment { background: #f59e0b; }
.dot-confirmed { background: #22c55e; }
.dot-completed { background: #64748b; }
.dot-cancelled { background: #ef4444; }

.clear-date {
  width: 100%;
  margin-top: 0.5rem;
  border: none;
  background: transparent;
  color: var(--color-blue);
  font-weight: 700;
  font-size: 0.75rem;
  cursor: pointer;
}

.summary-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
  margin-top: 0.75rem;
}

.summary-grid div {
  text-align: center;
  padding: 0.5rem;
  background: #f8fafc;
  border-radius: 0.75rem;
}

.summary-grid strong {
  display: block;
  font-size: 1.25rem;
  font-weight: 900;
}

.summary-grid span {
  font-size: 0.7rem;
  color: #64748b;
  font-weight: 600;
}

.list-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.status-tabs {
  display: flex;
  gap: 0.25rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.filter-btn {
  padding: 0.35rem 0.75rem;
  border-radius: 0.5rem;
  border: none;
  background: #f1f5f9;
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
  transition: 0.15s ease;
}

.filter-btn.active {
  background: var(--color-blue);
  color: white;
}

.loading-list {
  display: grid;
  gap: 0.75rem;
}

.empty-schedule-card {
  text-align: center;
  padding: 3rem;
}

.session-groups {
  display: grid;
  gap: 1rem;
}

.session-group {
  display: grid;
  gap: 0.5rem;
}

.date-divider {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.date-divider span {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
  background: var(--color-blue);
  flex-shrink: 0;
}

.date-divider strong {
  font-size: 0.85rem;
}

.date-divider small {
  font-size: 0.7rem;
  color: #64748b;
  display: block;
}

.session-card {
  background: #f8fafc;
  border-radius: 1rem;
  padding: 1rem;
  border: 1px solid rgba(0,0,0,0.06);
}

.session-body {
  display: grid;
  gap: 0.75rem;
}

.session-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.status-chip {
  display: inline-block;
  padding: 0.15rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.65rem;
  font-weight: 800;
  text-transform: uppercase;
  background: #e2e8f0;
  color: #475569;
}

.session-top h4 {
  margin: 0.25rem 0 0;
  font-size: 0.95rem;
  font-weight: 900;
}

.session-top p {
  margin: 0.15rem 0 0;
  font-size: 0.75rem;
  color: #64748b;
}

.meta-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
}

.meta-grid div {
  padding: 0.5rem;
  background: white;
  border-radius: 0.5rem;
}

.meta-grid span {
  display: block;
  font-size: 0.65rem;
  color: #64748b;
  font-weight: 600;
}

.meta-grid strong {
  font-size: 0.85rem;
  font-weight: 900;
}

.session-note {
  font-size: 0.8rem;
  font-style: italic;
  color: #475569;
  padding: 0.5rem;
  background: white;
  border-radius: 0.5rem;
}

.status-note {
  font-size: 0.75rem;
  font-weight: 600;
}

.status-note.pending { color: #d97706; }
.status-note.confirmed { color: #16a34a; }
.status-note.rejected { color: #dc2626; }

.session-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
  margin-top: 0.25rem;
}

.session-dates-list {
  display: grid;
  gap: 0.35rem;
  padding: 0.5rem;
  background: white;
  border-radius: 0.75rem;
  max-height: 200px;
  overflow-y: auto;
}

.session-date-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.35rem 0.5rem;
  font-size: 0.8rem;
  font-weight: 600;
  border-radius: 0.5rem;
  background: #f1f5f9;
}

.session-date-item .material-symbols-outlined {
  font-size: 1rem;
  color: var(--color-blue);
}

.session-time-badge {
  margin-left: auto;
  background: var(--color-blue);
  color: white;
  padding: 0.1rem 0.4rem;
  border-radius: 0.25rem;
  font-size: 0.7rem;
  font-weight: 700;
}

.session-date-item.loading {
  justify-content: center;
  color: #64748b;
  font-style: italic;
}

/* Upload Modal */
.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 50;
  display: grid;
  place-items: center;
  background: rgba(11, 28, 48, 0.45);
  backdrop-filter: blur(8px);
  padding: 1rem;
  animation: fadeIn 0.25s ease-out;
}

.modal-card {
  width: min(100%, 440px);
  display: grid;
  gap: 1.25rem;
  border-radius: 1.5rem;
  background: rgba(255, 255, 255, 0.98);
  border: 1px solid rgba(15, 23, 42, 0.08);
  padding: 1.75rem;
  box-shadow: 0 24px 60px rgba(11, 28, 48, 0.18);
}

.modal-header {
  display: flex;
  gap: 1rem;
  align-items: center;
  border-bottom: 1px solid rgba(0,0,0,0.06);
  padding-bottom: 1rem;
}

.modal-icon {
  background: rgba(54, 90, 130, 0.1);
  color: var(--color-blue);
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 0.75rem;
  display: grid;
  place-items: center;
  flex-shrink: 0;
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

.upload-zone:hover {
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

.form-label {
  display: block;
  font-weight: 800;
  margin-bottom: 0.4rem;
  font-size: 0.85rem;
  color: var(--color-blue-dark);
}

.field-error {
  color: #ef4444;
  font-size: 0.75rem;
  margin-top: 0.25rem;
  font-weight: 600;
}

.form-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.button {
  align-items: center;
  border-radius: 0.75rem;
  display: inline-flex;
  font-size: 0.8rem;
  font-weight: 900;
  justify-content: center;
  min-height: 2.25rem;
  padding: 0.4rem 1rem;
  transition: 160ms ease;
  border: none;
  cursor: pointer;
  text-decoration: none;
}

.button-primary {
  background: var(--color-blue);
  color: white;
}

.button-primary:hover {
  background: var(--color-blue-dark);
}

.button-ghost {
  background: transparent;
  border: 1px solid rgba(0, 0, 0, 0.15);
  color: var(--color-muted);
}

.button-ghost:hover {
  background: #f1f5f9;
}

.button-danger-ghost {
  background: transparent;
  border: 1px solid #fca5a5;
  color: #dc2626;
}

.button-danger-ghost:hover {
  background: #fef2f2;
}

.button-small {
  font-size: 0.7rem;
  min-height: 2rem;
  padding: 0.3rem 0.75rem;
}

@media (max-width: 900px) {
  .schedule-grid {
    grid-template-columns: 1fr;
  }

  .meta-grid {
    grid-template-columns: 1fr;
  }
}
</style>
