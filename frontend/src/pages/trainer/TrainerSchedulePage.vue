<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { trainerSidebarItems } from '../../components/layout/sidebarItems'
import { useBookingStore } from '../../stores/bookingStore'
import SkeletonScheduleSession from '../../components/ui/skeleton/SkeletonScheduleSession.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'

const store = useBookingStore()
const { run } = useDeferredLoading()
const hasLoadedBookings = ref(false)
const activeTab = ref('Upcoming') // Upcoming, History, Request

// Checkbox Filters — mapped to database values: 'online' and 'offline'
const filterOnline = ref(true)
const filterOffline = ref(true)

function formatTime(iso: string) {
  try {
    return new Intl.DateTimeFormat('en-US', { dateStyle: 'long' }).format(new Date(iso))
  } catch {
    return iso
  }
}

function formatPrice(n: number) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(n)
}

async function updateStatus(id: number, status: string) {
  await store.updateStatus(id, status)
  store.loadBookings() // Reload list to reflect changes immediately
}

// Filtered Bookings list based on checkboxes
const filteredBookings = computed(() => {
  return store.bookings.filter((b) => {
    const type = (b.session_type || '').toLowerCase()
    if (type === 'online' && !filterOnline.value) return false
    if (type === 'offline' && !filterOffline.value) return false
    return true
  })
})

// Tab Filters
const upcomingBookings = computed(() => {
  return filteredBookings.value.filter((b) => b.status === 'confirmed')
})

const requestBookings = computed(() => {
  return filteredBookings.value.filter((b) => b.status === 'pending')
})

const pastBookings = computed(() => {
  return filteredBookings.value.filter((b) => ['completed', 'rejected', 'cancelled'].includes(b.status ?? ''))
})

const currentList = computed(() => {
  if (activeTab.value === 'Upcoming') return upcomingBookings.value
  if (activeTab.value === 'Request') return requestBookings.value
  return pastBookings.value
})

// Interactive Calendar with month/year navigation
const now = new Date()
const currentMonth = ref(now.getMonth())
const currentYear = ref(now.getFullYear())

const currentMonthYear = computed(() => {
  const date = new Date(currentYear.value, currentMonth.value, 1)
  return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
})

function prevMonth() {
  if (currentMonth.value === 0) {
    currentMonth.value = 11
    currentYear.value--
  } else {
    currentMonth.value--
  }
}

function nextMonth() {
  if (currentMonth.value === 11) {
    currentMonth.value = 0
    currentYear.value++
  } else {
    currentMonth.value++
  }
}

const calendarDays = computed(() => {
  const year = currentYear.value
  const month = currentMonth.value

  const firstDayIndex = new Date(year, month, 1).getDay() // Day of week (0=Sunday)
  const totalDays = new Date(year, month + 1, 0).getDate() // Days in month

  const days = []

  // Add empty placeholders for padding before the 1st of the month (Min is Sunday = 0)
  for (let i = 0; i < firstDayIndex; i++) {
    days.push({ day: null, dateStr: '', isToday: false })
  }

  // Add the actual days of the month
  const today = new Date()
  for (let d = 1; d <= totalDays; d++) {
    const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`
    const isToday = d === today.getDate() && month === today.getMonth() && year === today.getFullYear()
    days.push({ day: d, dateStr, isToday })
  }

  return days
})

function getDayDots(dateStr: string) {
  if (!dateStr) return []
  
  const dateBookings = store.bookings.filter(b => b.booking_date.startsWith(dateStr))
  
  const dots: string[] = []
  dateBookings.forEach(b => {
    const type = (b.session_type || '').toLowerCase()
    if (type === 'online' && !dots.includes('online')) dots.push('online')
    if (type === 'offline' && !dots.includes('offline')) dots.push('offline')
  })
  return dots
}

onMounted(() => {
  run(async () => {
    await store.loadBookings()
    hasLoadedBookings.value = true
  })
})
</script>

<template>
  <WorkspaceLayout
    role="trainer"
    sidebar-title="Trainer"
    title="Session Schedule"
    subtitle="Manage your training schedule with clients and fitness classes."
    :sidebar-items="trainerSidebarItems"
  >
    <div class="trainer-schedule-layout">
      
      <div class="schedule-grid">
        
        <!-- LEFT COLUMN: CALENDAR & FILTERS -->
        <div class="left-column">
          <!-- Calendar Card -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <div class="calendar-header mb-4">
              <h3 class="font-bold text-gray-900">{{ currentMonthYear }}</h3>
              <div class="flex gap-2">
                <button class="nav-arrow-btn" type="button" aria-label="Previous month" @click="prevMonth">
                  <span class="material-symbols-outlined">chevron_left</span>
                </button>
                <button class="nav-arrow-btn" type="button" aria-label="Next month" @click="nextMonth">
                  <span class="material-symbols-outlined">chevron_right</span>
                </button>
              </div>
            </div>

            <!-- Calendar Days of Week Header -->
            <div class="calendar-days-week-grid mb-2">
              <div class="day-label">Sun</div>
              <div class="day-label">Mon</div>
              <div class="day-label">Tue</div>
              <div class="day-label">Wed</div>
              <div class="day-label">Thu</div>
              <div class="day-label">Fri</div>
              <div class="day-label">Sat</div>
            </div>

            <!-- Calendar Grid Days -->
            <div class="calendar-days-numbers-grid">
              <div
                v-for="(d, idx) in calendarDays"
                :key="idx"
                :class="['day-number-cell', { 'today-cell': d.isToday }]"
              >
                <span v-if="d.day">{{ d.day }}</span>
                
                <!-- Indicators Dots -->
                <div v-if="d.day" class="dots-wrapper">
                  <span v-for="dot in getDayDots(d.dateStr)" :key="dot" :class="['dot-indicator', `dot-${dot}`]"></span>
                </div>
              </div>
            </div>
          </div>

          <!-- Kategori Sesi Filters -->
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex-1">
            <h3 class="font-bold text-gray-900 mb-4">Session Category</h3>
            <div class="checkboxes-stack">
              <label class="checkbox-label-card">
                <input
                  v-model="filterOnline"
                  type="checkbox"
                  class="custom-checkbox"
                />
                <span class="checkbox-text">Online Session</span>
                <span class="dot-indicator dot-online w-3 h-3 rounded-full"></span>
              </label>

              <label class="checkbox-label-card">
                <input
                  v-model="filterOffline"
                  type="checkbox"
                  class="custom-checkbox"
                />
                <span class="checkbox-text">Offline Session</span>
                <span class="dot-indicator dot-offline w-3 h-3 rounded-full"></span>
              </label>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: SCHEDULE LISTING -->
        <div class="right-column bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col overflow-hidden">
          
          <!-- TAB HEADERS -->
          <div class="flex border-b border-gray-100">
            <button
              :class="['tab-link', { 'tab-active': activeTab === 'Upcoming' }]"
              @click="activeTab = 'Upcoming'"
            >
              Upcoming Schedule
            </button>
            <button
              :class="['tab-link', { 'tab-active': activeTab === 'History' }]"
              @click="activeTab = 'History'"
            >
              Session History
            </button>
            <button
              :class="['tab-link', { 'tab-active': activeTab === 'Request' }]"
              @click="activeTab = 'Request'"
            >
              Session Requests
            </button>
          </div>

          <!-- SESSION LISTING SCROLLER -->
          <div class="flex-1 p-6 overflow-y-auto">
            <SkeletonScheduleSession v-if="store.loading && !hasLoadedBookings" :rows="5" />
            <div v-else-if="store.loading && hasLoadedBookings" class="text-center py-6">
              <p class="text-gray-500 font-semibold text-sm">Updating schedule...</p>
            </div>

            <!-- Dynamic Session List -->
            <div v-else-if="currentList.length > 0" class="sessions-stack">
              <div v-for="b in currentList" :key="b.id" class="session-detail-card bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
                <div class="flex justify-between items-start mb-4">
                  <div>
                    <h4 class="member-name">{{ b.member?.full_name ?? 'Member' }}</h4>
                    <p class="session-desc-meta uppercase tracking-wider">{{ b.session_type }} - {{ b.location || 'Fitnez Gym' }}</p>
                  </div>
                  <span
                    :class="['status-pill border', b.status === 'pending' ? 'bg-amber-100 text-amber-800 border-amber-200' : b.status === 'confirmed' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-gray-100 text-gray-800 border-gray-200']"
                  >
                    {{ b.status === 'pending' ? 'Pending Confirmation' : b.status === 'confirmed' ? 'Confirmed' : b.status }}
                  </span>
                </div>

                <div class="session-time-block mb-4">
                  <div class="flex items-center gap-2 font-bold text-gray-800">
                    <span class="material-symbols-outlined text-sm">calendar_today</span>
                    <span>{{ formatTime(b.booking_date) }}</span>
                  </div>
                  <div class="text-xs text-gray-500 font-bold mt-1">
                    At {{ b.start_time.substring(0, 5) }} - {{ b.end_time.substring(0, 5) }}
                  </div>
                </div>

                <div v-if="b.member_notes" class="session-notes-box mb-4">
                  <span class="notes-title">Member Notes:</span>
                  <p class="notes-text">"{{ b.member_notes }}"</p>
                </div>

                <div class="session-card-footer mt-auto">
                  <p class="session-price">{{ formatPrice(Number(b.total_price)) }}</p>
                  
                  <div v-if="b.status === 'pending'" class="flex gap-2">
                    <button class="btn-ghost-small" @click="updateStatus(b.id, 'rejected')">Reject</button>
                    <button class="btn-primary-small" @click="updateStatus(b.id, 'confirmed')">Accept</button>
                  </div>
                  <div v-else-if="b.status === 'confirmed'" class="flex gap-2">
                    <button class="btn-primary-small" @click="updateStatus(b.id, 'completed')">Complete</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Fallback Empty State -->
            <div v-else class="empty-state-container">
              <div class="circle-illustration mb-6">
                <div class="ring-bg ring-scale-1"></div>
                <div class="ring-bg ring-scale-2"></div>
                <span class="material-symbols-outlined xmark-icon">calendar_today</span>
              </div>
              <h3 class="empty-title">
                {{ activeTab === 'Upcoming' ? 'No active sessions yet' : activeTab === 'Request' ? 'No incoming requests yet' : 'No session history yet' }}
              </h3>
              <p class="empty-desc">
                {{ activeTab === 'Upcoming' ? 'You do not have any training sessions scheduled for today or the next few days.' : activeTab === 'Request' ? 'There are currently no training session booking requests from members.' : 'All completed or rejected sessions will be saved in the history.' }}
              </p>
            </div>
          </div>

        </div>

      </div>

    </div>
  </WorkspaceLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');

.material-symbols-outlined {
  direction: ltr;
  display: inline-block;
  flex: 0 0 auto;
  font-family: 'Material Symbols Outlined';
  font-feature-settings: 'liga';
  font-size: 1.25rem;
  font-style: normal;
  font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
  font-weight: normal;
  letter-spacing: normal;
  line-height: 1;
  text-transform: none;
  white-space: nowrap;
  width: 1em;
  word-wrap: normal;
  -webkit-font-feature-settings: 'liga';
  -webkit-font-smoothing: antialiased;
}

.trainer-schedule-layout {
  font-family: 'Outfit', sans-serif !important;
  color: #1f2937;
  display: flex;
  flex-direction: column;
}

.schedule-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
}

@media (min-width: 1024px) {
  .schedule-grid {
    grid-template-columns: 340px 1fr;
  }
}

/* LEFT COLUMN */
.left-column {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.calendar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.nav-arrow-btn {
  background-color: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #4b5563;
  cursor: pointer;
  transition: all 0.2s ease;
}

.nav-arrow-btn:hover {
  background-color: #f3f4f6;
}

.calendar-days-week-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  text-align: center;
  font-size: 0.75rem;
}

.day-label {
  color: #9ca3af;
  font-weight: 700;
  padding: 0.25rem 0;
}

.calendar-days-numbers-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 0.25rem;
  text-align: center;
  font-size: 0.85rem;
}

.day-number-cell {
  position: relative;
  height: 2.25rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border-radius: 0.5rem;
  cursor: pointer;
  color: #374151;
  transition: all 0.2s ease;
}

.day-number-cell:hover {
  background-color: #f3f4f6;
}

.today-cell {
  background-color: #3b82f6 !important;
  color: #ffffff !important;
  font-weight: 800;
  box-shadow: 0 4px 10px rgba(59, 130, 246, 0.25);
}

.dots-wrapper {
  position: absolute;
  bottom: 0.2rem;
  display: flex;
  gap: 0.15rem;
}

.dot-indicator {
  width: 0.25rem;
  height: 0.25rem;
  border-radius: 999px;
}

.dot-online { background-color: #3b82f6; }
.dot-offline { background-color: #f59e0b; }

/* CHECKBOX FILTERS */
.checkboxes-stack {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.checkbox-label-card {
  display: flex;
  align-items: center;
  border: 1px solid #f3f4f6;
  border-radius: 0.75rem;
  padding: 0.75rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.checkbox-label-card:hover {
  background-color: #f9fafb;
}

.custom-checkbox {
  width: 1rem;
  height: 1rem;
  color: #3b82f6;
  border-color: #d1d5db;
  border-radius: 0.25rem;
}

.checkbox-text {
  margin-left: 0.75rem;
  font-size: 0.85rem;
  font-weight: 700;
  color: #374151;
  flex: 1;
}

/* RIGHT COLUMN */
.right-column {
  min-height: 520px;
}

.tab-link {
  flex: 1;
  background: transparent;
  border: none;
  border-bottom: 2px solid transparent;
  color: #6b7280;
  font-weight: 800;
  font-size: 0.88rem;
  padding: 1rem 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: center;
}

.tab-link:hover {
  color: #111827;
  background-color: #f9fafb;
}

.tab-active {
  color: #3b82f6 !important;
  border-bottom-color: #3b82f6 !important;
  background-color: transparent !important;
}

.sessions-stack {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.session-detail-card {
  transition: all 0.2s ease;
}

.session-detail-card:hover {
  border-color: #d1d5db;
  box-shadow: 0 4px 12px rgba(15,23,42,0.03);
}

.member-name {
  font-size: 1.1rem;
  font-weight: 900;
  color: #111827;
  margin: 0;
}

.session-desc-meta {
  font-size: 0.78rem;
  color: #6b7280;
  margin-top: 0.15rem;
}

.status-pill {
  font-size: 0.68rem;
  font-weight: 900;
  padding: 0.25rem 0.5rem;
  border-radius: 999px;
  text-transform: uppercase;
}

.session-time-block {
  background-color: #fcfbf7; /* Fitnez warm cream tint */
  border-radius: 0.75rem;
  padding: 0.75rem 1rem;
  border: 1px solid #f1f5f9;
}

.session-notes-box {
  background-color: #f8fafc;
  border-radius: 0.75rem;
  padding: 0.75rem 1rem;
  border: 1px solid #f1f5f9;
}

.notes-title {
  display: block;
  font-size: 0.7rem;
  font-weight: 800;
  color: #6b7280;
  text-transform: uppercase;
}

.notes-text {
  font-size: 0.8rem;
  font-style: italic;
  color: #4b5563;
  margin: 0.15rem 0 0 0;
  line-height: 1.4;
}

.session-card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.session-price {
  font-weight: 900;
  color: #ea580c;
  font-size: 1rem;
  margin: 0;
}

.btn-primary-small {
  background-color: #3b82f6;
  color: #ffffff;
  font-weight: 800;
  font-size: 0.75rem;
  border-radius: 0.5rem;
  padding: 0.35rem 0.75rem;
  border: none;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-primary-small:hover {
  background-color: #2563eb;
}

.btn-ghost-small {
  background-color: #ffffff;
  border: 1px solid #e5e7eb;
  color: #4b5563;
  font-weight: 800;
  font-size: 0.75rem;
  border-radius: 0.5rem;
  padding: 0.35rem 0.75rem;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-ghost-small:hover {
  background-color: #f9fafb;
}

/* EMPTY STATE */
.empty-state-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 3rem 1.5rem;
  height: 100%;
}

.circle-illustration {
  position: relative;
  width: 7rem;
  height: 7rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.ring-bg {
  position: absolute;
  inset: 0;
  background-color: #eff6ff;
  border-radius: 999px;
}

.ring-scale-1 {
  opacity: 0.5;
  transform: scale(1.15);
}

.ring-scale-2 {
  opacity: 0.8;
  transform: scale(0.95);
}

.xmark-icon {
  position: relative;
  z-index: 10;
  font-size: 2.75rem;
  color: #3b82f6;
}

.empty-title {
  font-size: 1.25rem;
  font-weight: 800;
  color: #111827;
  margin: 0 0 0.5rem 0;
}

.empty-desc {
  color: #6b7280;
  font-size: 0.85rem;
  max-width: 22rem;
  line-height: 1.5;
  margin: 0 0 1.5rem 0;
}

.create-btn {
  background-color: #3b82f6;
  color: #ffffff;
  font-weight: 800;
  font-size: 0.82rem;
  border-radius: 0.75rem;
  padding: 0.6rem 1.25rem;
  border: none;
  cursor: pointer;
  transition: all 0.15s ease;
  box-shadow: 0 4px 10px rgba(59, 130, 246, 0.25);
}

.create-btn:hover {
  background-color: #2563eb;
}

.mb-2 { margin-bottom: 0.5rem; }
.mb-4 { margin-bottom: 1rem; }
.mt-1 { margin-top: 0.25rem; }
.flex { display: flex; }
.gap-2 { gap: 0.5rem; }
</style>
