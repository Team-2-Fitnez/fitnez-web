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

type BookingFilter = 'all' | 'pending' | 'confirmed' | 'completed' | 'cancelled'

const filters: BookingFilter[] = ['all', 'pending', 'confirmed', 'completed', 'cancelled']
const filter = ref<BookingFilter>('all')
const selectedDate = ref('')

const now = new Date()
const currentMonth = ref(now.getMonth())
const currentYear = ref(now.getFullYear())

const filterLabels: Record<BookingFilter, string> = {
  all: 'All',
  pending: 'Pending',
  confirmed: 'Confirmed',
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
    const dateMatch = !selectedDate.value || (booking.booking_date || '').startsWith(selectedDate.value)
    return statusMatch && dateMatch
  })
})

const pendingCount = computed(() => store.bookings.filter(b => b.status === 'pending').length)
const confirmedCount = computed(() => store.bookings.filter(b => b.status === 'confirmed').length)
const completedCount = computed(() => store.bookings.filter(b => b.status === 'completed').length)

const groupedBookings = computed(() => {
  const groups = new Map<string, typeof store.bookings>()
  filteredBookings.value.forEach((booking) => {
    const key = (booking.booking_date || '').substring(0, 10) || 'No date'
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
      .filter(booking => (booking.booking_date || '').startsWith(dateStr))
      .map(booking => booking.status || 'pending')
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

function shortTime(value?: string | null) {
  return value ? value.substring(0, 5) : '--:--'
}

function statusLabel(value?: string | null) {
  if (value === 'pending') return 'Pending'
  if (value === 'confirmed') return 'Confirmed'
  if (value === 'completed') return 'Completed'
  if (value === 'cancelled') return 'Cancelled'
  if (value === 'rejected') return 'Rejected'
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
    window.showFitnezToast('Booking successful! Check schedule in the Schedule menu.', 'success')
  }
})
useAutoRefresh(() => store.loadBookings(), 8000)
</script>

<template>
  <WorkspaceLayout
    role="member"
    sidebar-title="Member"
    title="Workout Schedule"
    subtitle="View and manage your workout session schedule with your trainer."
    :sidebar-items="memberSidebarItems"
  >
    <section class="member-schedule-layout">
      <div v-if="pendingCount > 0" class="schedule-alert">
        <span class="material-symbols-outlined">pending_actions</span>
        <div>
          <p>{{ pendingCount }} booking(s) waiting for confirmation</p>
          <small>Trainer will confirm your schedule soon.</small>
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
                <span>Pending</span>
              </div>
              <div>
                <strong>{{ confirmedCount }}</strong>
                <span>Active</span>
              </div>
              <div>
                <strong>{{ completedCount }}</strong>
                <span>Completed</span>
              </div>
            </div>
          </article>
        </aside>

        <section class="schedule-right schedule-card">
          <div class="list-head">
            <div>
              <p class="eyebrow">Session List</p>
              <h3>{{ selectedDate ? formatDate(selectedDate) : 'All Schedules' }}</h3>
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
              {{ filter === 'all' ? 'No workout schedule yet.' : `No schedules with status "${filterLabels[filter]}".` }}
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
                <div class="time-tile">
                  <strong>{{ shortTime(booking.start_time) }}</strong>
                  <span>{{ shortTime(booking.end_time) }}</span>
                </div>

                <div class="session-body">
                  <div class="session-top">
                    <div>
                      <span class="status-chip">{{ statusLabel(booking.status) }}</span>
                      <h4>{{ booking.trainer?.full_name ?? 'Trainer' }}</h4>
                      <p>{{ booking.session_type }} - {{ booking.location || 'Fitnez Gym' }}</p>
                    </div>
                    <StatusBadge :status="booking.status || 'pending'" />
                  </div>

                  <div class="meta-grid">
                    <div>
                      <span>Cost</span>
                      <strong>{{ formatPrice(booking.total_price) }}</strong>
                    </div>
                    <div>
                      <span>Type</span>
                      <strong>{{ booking.session_type || '-' }}</strong>
                    </div>
                    <div>
                      <span>Date</span>
                      <strong>{{ formatDate(booking.booking_date) }}</strong>
                    </div>
                  </div>

                  <p v-if="booking.member_notes" class="session-note">{{ booking.member_notes }}</p>
                  <p v-if="booking.status === 'pending'" class="status-note pending">Waiting for confirmation from trainer.</p>
                  <p v-else-if="booking.status === 'confirmed'" class="status-note confirmed">Schedule confirmed. Please coordinate via Chat.</p>
                  <p v-else-if="booking.status === 'rejected'" class="status-note rejected">Booking rejected by trainer. Please choose another time.</p>

                  <div v-if="booking.status === 'confirmed' || booking.status === 'pending'" class="session-actions">
                    <router-link v-if="booking.status === 'confirmed'" :to="`/member/chat?contact=${booking.trainer_id}`" class="button button-primary button-small">
                      Chat Trainer
                    </router-link>
                    <button class="button button-danger-ghost button-small" type="button" @click="cancelBooking(booking.id)">
                      Cancel Session
                    </button>
                  </div>
                </div>
              </article>
            </div>
          </div>
        </section>
      </div>
    </section>
  </WorkspaceLayout>
</template>

<style scoped>
.member-schedule-layout {
  display: grid;
  gap: 1.25rem;
}

.schedule-alert,
.schedule-card {
  background: #ffffff;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 1.25rem;
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
}

.schedule-alert {
  align-items: center;
  display: flex;
  gap: 1rem;
  padding: 1rem 1.15rem;
}

.schedule-alert > span {
  align-items: center;
  background: #fffbeb;
  border-radius: 999px;
  color: #b45309;
  display: inline-flex;
  height: 2.6rem;
  justify-content: center;
  width: 2.6rem;
}

.schedule-alert p {
  color: #0f172a;
  font-weight: 950;
  margin: 0;
}

.schedule-alert small {
  color: #64748b;
  font-weight: 800;
}

.schedule-grid {
  align-items: start;
  display: grid;
  gap: 1.25rem;
  grid-template-columns: minmax(300px, 0.85fr) minmax(0, 1.45fr);
}

.schedule-left {
  display: grid;
  gap: 1.25rem;
}

.schedule-card {
  min-width: 0;
  padding: clamp(1rem, 2vw, 1.4rem);
}

.eyebrow {
  color: #2563eb;
  font-size: 0.72rem;
  font-weight: 950;
  letter-spacing: 0.14em;
  margin: 0;
  text-transform: uppercase;
}

.calendar-header,
.list-head,
.session-top {
  align-items: flex-start;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
}

.calendar-header h3,
.list-head h3 {
  color: #0f172a;
  font-size: 1.2rem;
  font-weight: 950;
  margin-top: 0.25rem;
  text-transform: capitalize;
}

.calendar-nav {
  display: flex;
  gap: 0.45rem;
}

.calendar-nav button {
  align-items: center;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  color: #475569;
  cursor: pointer;
  display: inline-flex;
  height: 2.35rem;
  justify-content: center;
  width: 2.35rem;
}

.weekday-grid,
.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
}

.weekday-grid {
  color: #94a3b8;
  font-size: 0.7rem;
  font-weight: 950;
  gap: 0.35rem;
  margin: 1.25rem 0 0.5rem;
  text-align: center;
}

.calendar-grid {
  gap: 0.35rem;
}

.day-cell {
  aspect-ratio: 1 / 1;
  background: #f8fafc;
  border: 1px solid transparent;
  border-radius: 0.8rem;
  color: #475569;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  font-size: 0.82rem;
  font-weight: 950;
  justify-content: space-between;
  min-width: 0;
  padding: 0.45rem;
}

.day-cell:disabled {
  cursor: default;
  opacity: 0;
}

.day-cell.today {
  border-color: #2563eb;
  color: #1d4ed8;
}

.day-cell.selected {
  background: #2563eb;
  color: #ffffff;
}

.day-cell.has-booking {
  box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.18);
}

.dot-row {
  display: flex;
  gap: 0.15rem;
  justify-content: center;
  min-height: 0.35rem;
}

.dot-row b {
  border-radius: 999px;
  display: block;
  height: 0.32rem;
  width: 0.32rem;
}

.dot-pending { background: #f59e0b; }
.dot-confirmed { background: #10b981; }
.dot-completed { background: #2563eb; }
.dot-cancelled,
.dot-rejected { background: #ef4444; }

.clear-date {
  background: #eff6ff;
  border: 0;
  border-radius: 999px;
  color: #1d4ed8;
  cursor: pointer;
  font-size: 0.78rem;
  font-weight: 950;
  margin-top: 1rem;
  padding: 0.6rem 0.85rem;
  width: 100%;
}

.summary-grid {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  margin-top: 1rem;
}

.summary-grid div {
  background: #f8fafc;
  border: 1px solid rgba(15, 23, 42, 0.06);
  border-radius: 1rem;
  padding: 0.9rem;
}

.summary-grid strong {
  color: #0f172a;
  display: block;
  font-size: 1.45rem;
  font-weight: 950;
}

.summary-grid span {
  color: #64748b;
  font-size: 0.75rem;
  font-weight: 900;
}

.schedule-right {
  display: flex;
  flex-direction: column;
  gap: 1.15rem;
}

.status-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.55rem;
}

.filter-btn {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 999px;
  color: #475569;
  cursor: pointer;
  font-size: 0.82rem;
  font-weight: 950;
  padding: 0.58rem 0.95rem;
}

.filter-btn.active {
  background: #2563eb;
  border-color: #2563eb;
  color: #ffffff;
}

.loading-list,
.session-groups,
.session-group {
  display: grid;
  gap: 1rem;
}

.empty-schedule-card {
  align-items: center;
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  border-radius: 1rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: 3rem 1rem;
  text-align: center;
}

.empty-schedule-card > span {
  color: #2563eb;
  font-size: 3rem;
}

.empty-schedule-card p {
  color: #64748b;
  font-weight: 900;
}

.date-divider {
  align-items: center;
  display: flex;
  gap: 0.8rem;
}

.date-divider > span {
  background: #2563eb;
  border: 5px solid #eff6ff;
  border-radius: 999px;
  height: 1.25rem;
  width: 1.25rem;
}

.date-divider strong {
  color: #0f172a;
  display: block;
  font-weight: 950;
}

.date-divider small {
  color: #64748b;
  display: block;
  font-size: 0.72rem;
  font-weight: 800;
}

.session-card {
  background: #ffffff;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 1rem;
  display: grid;
  gap: 1rem;
  grid-template-columns: 5rem minmax(0, 1fr);
  padding: 1rem;
}

.time-tile {
  align-items: center;
  background: #eff6ff;
  border-radius: 1rem;
  color: #1d4ed8;
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-height: 5rem;
}

.time-tile strong {
  font-size: 1.1rem;
  font-weight: 950;
}

.time-tile span {
  color: #64748b;
  font-size: 0.75rem;
  font-weight: 900;
}

.session-body {
  min-width: 0;
}

.status-chip {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 999px;
  color: #475569;
  display: inline-flex;
  font-size: 0.7rem;
  font-weight: 950;
  margin-bottom: 0.45rem;
  padding: 0.25rem 0.55rem;
}

.session-top h4 {
  color: #0f172a;
  font-size: 1.08rem;
  font-weight: 950;
  margin: 0;
}

.session-top p {
  color: #64748b;
  font-size: 0.85rem;
  font-weight: 800;
  margin: 0.2rem 0 0;
  text-transform: capitalize;
}

.meta-grid {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  margin-top: 1rem;
}

.meta-grid div {
  background: #f8fafc;
  border: 1px solid rgba(15, 23, 42, 0.06);
  border-radius: 0.85rem;
  min-width: 0;
  padding: 0.75rem;
}

.meta-grid span {
  color: #64748b;
  display: block;
  font-size: 0.7rem;
  font-weight: 950;
  text-transform: uppercase;
}

.meta-grid strong {
  color: #0f172a;
  display: block;
  font-size: 0.84rem;
  font-weight: 950;
  margin-top: 0.25rem;
  overflow-wrap: anywhere;
}

.session-note,
.status-note {
  border-radius: 0.85rem;
  font-size: 0.85rem;
  font-weight: 800;
  margin: 1rem 0 0;
  padding: 0.75rem;
}

.session-note {
  background: #f8fafc;
  color: #475569;
}

.status-note.pending { background: #fffbeb; color: #92400e; }
.status-note.confirmed { background: #f0fdf4; color: #166534; }
.status-note.rejected { background: #fef2f2; color: #991b1b; }

.session-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 1rem;
}

@media (max-width: 1120px) {
  .schedule-grid {
    grid-template-columns: 1fr;
  }

  .schedule-left {
    grid-template-columns: minmax(0, 1fr) minmax(280px, 0.65fr);
  }
}

@media (max-width: 760px) {
  .schedule-left,
  .summary-grid,
  .meta-grid {
    grid-template-columns: 1fr;
  }

  .list-head,
  .calendar-header,
  .session-top,
  .schedule-alert {
    align-items: stretch;
    flex-direction: column;
  }

  .session-card {
    display: flex;
    flex-direction: column;
  }

  .time-tile {
    align-items: flex-start;
    min-height: auto;
    padding: 0.85rem;
  }

  .session-actions {
    flex-direction: column;
  }
}
</style>
