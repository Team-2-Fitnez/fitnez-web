<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import { http } from '../../api/http'
import { useMemberPaymentAttendanceReportStore } from '../../stores/memberPaymentAttendanceReportStore'
import SkeletonStatGrid from '../../components/ui/skeleton/SkeletonStatGrid.vue'
import SkeletonTable from '../../components/ui/SkeletonTable.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'
import { getSocket } from '../../services/socket'
import { useSse } from '../../composables/useSse'

const store = useMemberPaymentAttendanceReportStore()
const { loading: initialLoading, run, shimmerStyle } = useDeferredLoading()

// Report Export SSE
const showExportModal = ref(false)
const { progress, status, message, connect, disconnect } = useSse()

async function startReportExport() {
  showExportModal.value = true
  const url = http.url('/admin/export/member-reports/sse')
  const eventSource = new EventSource(url)

  eventSource.addEventListener('progress', (e: MessageEvent) => {
    try {
      const data = JSON.parse(e.data)
      progress.value = data.progress
      status.value = data.status
      message.value = data.message || ''

      if (data.status === 'completed' && data.download_url) {
        eventSource.close()
        // Short delay so user sees 100% then download
        setTimeout(() => {
          window.location.href = data.download_url
          showExportModal.value = false
        }, 500)
      }
    } catch { /* ignore parse errors */ }
  })

  eventSource.onerror = () => {
    status.value = 'failed'
    message.value = 'Connection lost. Please try again.'
    eventSource.close()
  }
}

// Formatting helpers
function currency(value?: string | number | null) {
  const numberValue = Number(value || 0)
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(numberValue)
}

function formatDateTime(value?: string | null) {
  if (!value) return '-'
  return new Date(value).toLocaleString('en-US', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatDate(value?: string | null) {
  if (!value) return '-'
  return new Date(value).toLocaleDateString('en-US', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

function categoryClass(type?: string | null) {
  const key = (type || '').toLowerCase()
  if (key.includes('premium')) return 'cat cat-premium'
  if (key.includes('corporate') || key.includes('enterprise')) return 'cat cat-corporate'
  if (key.includes('upgrade')) return 'cat cat-upgrade'
  if (key.includes('basic')) return 'cat cat-basic'
  if (key.includes('trainer')) return 'cat cat-trainer'
  return 'cat cat-default'
}

function formatTypeLabel(type?: string | null) {
  if (!type) return '-'
  return type
    .replace(/[_-]+/g, ' ')
    .split(' ')
    .filter(Boolean)
    .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
    .join(' ')
}

function durationLabel(checkIn?: string | null, checkOut?: string | null) {
  if (!checkIn) return '-'
  if (!checkOut) return 'In progress'

  const start = new Date(checkIn).getTime()
  const end = new Date(checkOut).getTime()
  if (Number.isNaN(start) || Number.isNaN(end) || end <= start) return '-'

  const minutes = Math.round((end - start) / 60000)
  const hours = Math.floor(minutes / 60)
  const remainder = minutes % 60

  if (!hours) return `${remainder}m`
  return `${hours}h ${remainder}m`
}

function typeClass(type?: string | null) {
  const key = (type || '').toLowerCase()
  if (key.includes('trainer')) return 'type-pill type-pill-trainer'
  if (key.includes('class')) return 'type-pill type-pill-class'
  return 'type-pill type-pill-member'
}

function getRemainingDays(expiresAt?: string | null) {
  if (!expiresAt) return 0
  const expireDate = new Date(expiresAt)
  const today = new Date()
  expireDate.setHours(0, 0, 0, 0)
  today.setHours(0, 0, 0, 0)
  const diffTime = expireDate.getTime() - today.getTime()
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return diffDays > 0 ? diffDays : 0
}

function getMembershipStatus(expiresAt?: string | null) {
  if (!expiresAt) return 'No Subscription'
  const remaining = getRemainingDays(expiresAt)
  if (remaining === 0) return 'Expired'
  if (remaining <= 7) return 'Expiring Soon'
  return 'Active'
}

function membershipStatusClass(expiresAt?: string | null) {
  const status = getMembershipStatus(expiresAt)
  if (status === 'Active') return 'pill pill-success'
  if (status === 'Expiring Soon') return 'pill pill-warning'
  if (status === 'Expired') return 'pill pill-danger'
  return 'pill pill-muted'
}

// Computed Metrics
const retentionRate = computed(() => {
  const attendance = Number(store.summary?.total_attendance || 0)
  const payments = Number(store.summary?.total_payments || 0)
  if (!payments) return '0%'
  return `${Math.min(100, Math.round((attendance / payments) * 100))}%`
})

const activePeriodLabel = computed(() => {
  if (!store.startDate && !store.endDate) return 'All available dates'

  const start = store.startDate
    ? new Date(store.startDate).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' })
    : 'First record'
  const end = store.endDate
    ? new Date(store.endDate).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' })
    : 'Today'

  return `${start} - ${end}`
})
const visiblePaymentsPages = computed(() => {
  const last = Number(store.paymentsLastPage)
  const current = Number(store.paymentsPage)
  if (last <= 5) {
    return Array.from({ length: last }, (_, i) => i + 1)
  }
  if (current <= 2) {
    return [1, 2, 3, '...', last]
  }
  if (current >= last - 1) {
    return [1, '...', last - 2, last - 1, last]
  }
  if (current === 3) {
    return [1, 2, 3, 4, '...', last]
  }
  if (current === last - 2) {
    return [1, '...', last - 3, last - 2, last - 1, last]
  }
  return [1, '...', current - 1, current, current + 1, '...', last]
})

const visibleAttendancePages = computed(() => {
  const last = Number(store.attendanceLastPage)
  const current = Number(store.attendancePage)
  if (last <= 5) {
    return Array.from({ length: last }, (_, i) => i + 1)
  }
  if (current <= 2) {
    return [1, 2, 3, '...', last]
  }
  if (current >= last - 1) {
    return [1, '...', last - 2, last - 1, last]
  }
  if (current === 3) {
    return [1, 2, 3, 4, '...', last]
  }
  if (current === last - 2) {
    return [1, '...', last - 3, last - 2, last - 1, last]
  }
  return [1, '...', current - 1, current, current + 1, '...', last]
})

function goToPaymentsPage(page: number | string) {
  if (typeof page === 'string') return
  if (page < 1 || page > store.paymentsLastPage || page === store.paymentsPage) return
  store.paymentsPage = page
  store.loadPayments()
}

function goToAttendancePage(page: number | string) {
  if (typeof page === 'string') return
  if (page < 1 || page > store.attendanceLastPage || page === store.attendancePage) return
  store.attendancePage = page
  store.loadAttendance()
}

// Filter Actions
function applyFilters() {
  store.paymentsPage = 1
  store.attendancePage = 1
  store.loadSummary()
  store.loadPayments()
  store.loadAttendance()
}

function resetFilters() {
  store.search = ''
  store.paymentStatus = ''
  store.attendanceType = ''
  store.startDate = ''
  store.endDate = ''
  applyFilters()
}

let pollInterval: number | null = null

onMounted(() => {
  run(async () => {
    await store.loadSummary()
    await store.loadPayments()
    await store.loadAttendance()
  })

  // Silent polling every 10 seconds in the background
  pollInterval = window.setInterval(async () => {
    try {
      await store.loadSummary(true)
      await store.loadPayments(true)
      await store.loadAttendance(true)
    } catch (err) {
      console.error('Polling operations data failed', err)
    }
  }, 10000)

  // Real-time update via Socket.io
  const socket = getSocket()
  if (socket) {
    socket.on('attendance-update', () => {
      store.loadSummary()
      store.loadAttendance()
    })
  }
})

onBeforeUnmount(() => {
  if (pollInterval) {
    window.clearInterval(pollInterval)
  }
  const socket = getSocket()
  if (socket) {
    socket.off('attendance-update')
  }
})
</script>

<template>
  <WorkspaceLayout
    role="admin"
    sidebar-title="Admin"
    title="Check-In Logs & Reports"
    subtitle="Monitor gym operational logs, payments, attendance history, and key business metrics in one unified dashboard."
    :sidebar-items="adminSidebarItems"
  >
    <div class="operations-page" :style="shimmerStyle">
      <template v-if="initialLoading && !store.attendance.length && !store.payments.length">
        <SkeletonStatGrid :count="5" />
        <SkeletonTable :columns="5" :rows="6" style="margin-top: 1.25rem" />
        <SkeletonTable :columns="6" :rows="6" style="margin-top: 1.25rem" />
      </template>

      <template v-else>
        <!-- Header Actions -->
        <div class="operations-header-actions">
          <p class="period-info">
            <span class="material-symbols-outlined font-icon">calendar_month</span>
            Period: {{ activePeriodLabel }}
          </p>
          <button type="button" class="export-report-btn" @click="startReportExport" :disabled="status === 'processing'">
            <span class="material-symbols-outlined font-icon">print</span>
            {{ status === 'processing' ? 'Generating...' : 'Print Monthly Report' }}
          </button>
        </div>

        <!-- Metric Cards Grid -->
        <section class="metric-grid">
          <article class="metric-card metric-card-blue">
            <p>Total Revenue</p>
            <strong>{{ currency(store.summary?.total_payment_amount || 0) }}</strong>
            <div class="metric-foot">
              <span :class="['trend', (store.summary?.total_payment_amount_trend ?? 0) > 0 ? 'trend-up' : (store.summary?.total_payment_amount_trend ?? 0) < 0 ? 'trend-down' : 'trend-neutral']">
                <span class="material-symbols-outlined trend-icon">{{ (store.summary?.total_payment_amount_trend ?? 0) > 0 ? 'trending_up' : (store.summary?.total_payment_amount_trend ?? 0) < 0 ? 'trending_down' : 'trending_flat' }}</span>
                {{ (store.summary?.total_payment_amount_trend ?? 0) > 0 ? '+' : '' }}{{ store.summary?.total_payment_amount_trend ?? 0 }}%
              </span>
              <span>this week</span>
            </div>
          </article>

          <article class="metric-card metric-card-indigo">
            <p>Total Transactions</p>
            <strong>{{ (store.summary?.total_payments ?? 0).toLocaleString('en-US') }}</strong>
            <div class="metric-foot">
              <span :class="['trend', (store.summary?.total_payments_trend ?? 0) > 0 ? 'trend-up' : (store.summary?.total_payments_trend ?? 0) < 0 ? 'trend-down' : 'trend-neutral']">
                <span class="material-symbols-outlined trend-icon">{{ (store.summary?.total_payments_trend ?? 0) > 0 ? 'trending_up' : (store.summary?.total_payments_trend ?? 0) < 0 ? 'trending_down' : 'trending_flat' }}</span>
                {{ (store.summary?.total_payments_trend ?? 0) > 0 ? '+' : '' }}{{ store.summary?.total_payments_trend ?? 0 }}%
              </span>
              <span>this week</span>
            </div>
          </article>

          <article class="metric-card metric-card-purple">
            <p>Attendance Ratio</p>
            <strong>{{ retentionRate }}</strong>
            <div class="metric-foot">
              <span :class="['trend', (store.summary?.attendance_today_trend ?? 0) > 0 ? 'trend-up' : (store.summary?.attendance_today_trend ?? 0) < 0 ? 'trend-down' : 'trend-neutral']">
                <span class="material-symbols-outlined trend-icon">{{ (store.summary?.attendance_today_trend ?? 0) > 0 ? 'trending_up' : (store.summary?.attendance_today_trend ?? 0) < 0 ? 'trending_down' : 'trending_flat' }}</span>
                {{ (store.summary?.attendance_today_trend ?? 0) > 0 ? '+' : '' }}{{ store.summary?.attendance_today_trend ?? 0 }}%
              </span>
              <span>today</span>
            </div>
          </article>

          <article class="metric-card metric-card-orange">
            <p>Total Check-Ins</p>
            <strong>{{ (store.summary?.total_attendance ?? 0).toLocaleString('en-US') }}</strong>
            <span class="detail-label">All recorded entries</span>
          </article>

          <article class="metric-card metric-card-green">
            <p>Today's Entries</p>
            <strong>{{ (store.summary?.attendance_today ?? 0).toLocaleString('en-US') }}</strong>
            <span class="detail-label">Current day volume</span>
          </article>
        </section>

        <!-- Horizontal Operational Filter Panel -->
        <section class="filter-panel">
          <label class="filter-field search-field">
            <span>Search member, email or invoice</span>
            <input v-model="store.search" type="search" placeholder="Search..." @keyup.enter="applyFilters" />
          </label>

          <label class="filter-field">
            <span>Classification</span>
            <select v-model="store.attendanceType" @change="applyFilters">
              <option value="">All Classifications</option>
              <option value="member_checkin">Member Check-In</option>
              <option value="trainer_checkin">Trainer Check-In</option>
              <option value="class_attendance">Class Attendance</option>
            </select>
          </label>

          <label class="filter-field">
            <span>Payment Status</span>
            <select v-model="store.paymentStatus" @change="applyFilters">
              <option value="">All Payment Statuses</option>
              <option value="paid">Successful</option>
              <option value="pending">Pending</option>
              <option value="failed">Failed</option>
            </select>
          </label>

          <label class="filter-field">
            <span>Start Date</span>
            <input v-model="store.startDate" type="date" @change="applyFilters" />
          </label>

          <label class="filter-field">
            <span>End Date</span>
            <input v-model="store.endDate" type="date" @change="applyFilters" />
          </label>

          <div class="filter-actions">
            <button type="button" class="secondary-btn" @click="resetFilters">Reset</button>
            <button type="button" class="primary-btn" @click="applyFilters">Apply</button>
          </div>
        </section>

        <!-- Member Subscription & Package Status Table -->
        <section class="table-panel">
          <div class="table-head">
            <div>
              <p class="eyebrow">Membership Monitoring</p>
              <h3>Member Subscription & Package Status</h3>
            </div>
            <span>{{ store.paymentsTotal }} records</span>
          </div>

          <div class="table-scroll">
            <table class="report-table">
              <thead>
                <tr>
                  <th>Member</th>
                  <th>Package</th>
                  <th>Start Date</th>
                  <th>Expiration Date</th>
                  <th>Remaining Days</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in store.payments" :key="row.id">
                  <td>
                    <p class="user-name">{{ row.user?.full_name || '-' }}</p>
                    <p class="user-email">{{ row.user?.email || '-' }}</p>
                  </td>
                  <td>
                    <span :class="categoryClass(row.user?.membership_package?.name || row.payment_type)">
                      {{ row.user?.membership_package?.name || formatTypeLabel(row.payment_type) }}
                    </span>
                  </td>
                  <td class="col-date">{{ formatDate(row.user?.membership_started_at) }}</td>
                  <td class="col-date">{{ formatDate(row.user?.membership_expires_at) }}</td>
                  <td>
                    <span class="duration-pill">
                      {{ row.user?.membership_expires_at ? `${getRemainingDays(row.user.membership_expires_at)} days` : '-' }}
                    </span>
                  </td>
                  <td>
                    <span :class="membershipStatusClass(row.user?.membership_expires_at)">
                      {{ getMembershipStatus(row.user?.membership_expires_at) }}
                    </span>
                  </td>
                </tr>
                <tr v-if="store.loadingPayments && store.payments.length">
                  <td colspan="6" class="empty-row">Updating subscription records...</td>
                </tr>
                <tr v-else-if="!store.payments.length">
                  <td colspan="6" class="empty-row">No member subscription records match the current filters.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="pager-bar">
            <p>Page {{ store.paymentsPage }} of {{ store.paymentsLastPage }}</p>
            <div class="pagination">
              <button type="button" class="pagination-arrow" :disabled="store.paymentsPage <= 1" @click="goToPaymentsPage(store.paymentsPage - 1)">‹</button>
              <button
                v-for="page in visiblePaymentsPages"
                :key="page"
                :class="{ active: Number(page) === Number(store.paymentsPage), disabled: page === '...' }"
                :disabled="page === '...'"
                type="button"
                @click="goToPaymentsPage(page)"
              >
                {{ page }}
              </button>
              <button type="button" class="pagination-arrow" :disabled="store.paymentsPage >= store.paymentsLastPage" @click="goToPaymentsPage(store.paymentsPage + 1)">›</button>
            </div>
          </div>
        </section>

        <!-- Attendance Logs Table -->
        <section class="table-panel table-panel-spaced">
          <div class="table-head">
            <div>
              <p class="eyebrow">Access Control Log</p>
              <h3>Gym Access & Check-In History</h3>
            </div>
            <span>{{ store.attendanceTotal }} records</span>
          </div>

          <div class="table-scroll">
            <table>
              <thead>
                <tr>
                  <th>User</th>
                  <th>Classification</th>
                  <th>Check In</th>
                  <th>Check Out</th>
                  <th>Duration</th>
                  <th>Booking / Session</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in store.attendance" :key="row.id">
                  <td>
                    <p class="user-name">{{ row.user?.full_name || '-' }}</p>
                    <p class="user-email">{{ row.user?.email || '-' }}</p>
                  </td>
                  <td><span :class="typeClass(row.attendance_type)">{{ formatTypeLabel(row.attendance_type) }}</span></td>
                  <td>{{ formatDateTime(row.check_in_time) }}</td>
                  <td>{{ formatDateTime(row.check_out_time) }}</td>
                  <td><span class="duration-pill">{{ durationLabel(row.check_in_time, row.check_out_time) }}</span></td>
                  <td>
                    <p class="booking-main">{{ row.booking?.session_type || '-' }}</p>
                    <p v-if="row.booking?.trainer?.full_name" class="booking-sub">{{ row.booking.trainer.full_name }}</p>
                  </td>
                </tr>
                <tr v-if="store.loadingAttendance && store.attendance.length">
                  <td colspan="6" class="empty-row">Updating check-in logs...</td>
                </tr>
                <tr v-else-if="!store.attendance.length">
                  <td colspan="6" class="empty-row">No check-in logs match the current filters.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="pager-bar">
            <p>Page {{ store.attendancePage }} of {{ store.attendanceLastPage }}</p>
            <div class="pagination">
              <button type="button" class="pagination-arrow" :disabled="store.attendancePage <= 1" @click="goToAttendancePage(store.attendancePage - 1)">‹</button>
              <button
                v-for="page in visibleAttendancePages"
                :key="page"
                :class="{ active: Number(page) === Number(store.attendancePage), disabled: page === '...' }"
                :disabled="page === '...'"
                type="button"
                @click="goToAttendancePage(page)"
              >
                {{ page }}
              </button>
              <button type="button" class="pagination-arrow" :disabled="store.attendancePage >= store.attendanceLastPage" @click="goToAttendancePage(store.attendancePage + 1)">›</button>
            </div>
          </div>
        </section>
      </template>
    </div>

    <!-- Export Progress Modal -->
    <Teleport to="body">
      <div v-if="showExportModal" class="modal-overlay" @click.self="showExportModal = false">
        <div class="modal-card">
          <h3 class="modal-title">Generating Monthly Report</h3>
          <div class="progress-bar-track">
            <div class="progress-bar-fill" :style="{ width: progress + '%' }"></div>
          </div>
          <p class="progress-text">{{ message }}</p>
          <p class="progress-pct">{{ progress }}%</p>
          <button v-if="status === 'failed'" type="button" class="button button-primary" @click="startReportExport">Retry</button>
          <button v-if="status === 'completed'" type="button" class="button button-primary" @click="showExportModal = false">Close</button>
        </div>
      </div>
    </Teleport>
  </WorkspaceLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');

.operations-page {
  color: #0f172a;
  display: grid;
  gap: 1.25rem;
  padding-bottom: 1rem;
}

.operations-header-actions {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  justify-content: space-between;
}

.period-info {
  align-items: center;
  color: #64748b;
  display: flex;
  font-size: 0.875rem;
  font-weight: 600;
  gap: 0.35rem;
  margin: 0;
}

.font-icon {
  font-size: 1.1rem;
  color: #94a3b8;
}

.export-report-btn {
  align-items: center;
  background: #0058be;
  border: none;
  border-radius: 0.75rem;
  color: #fff;
  display: inline-flex;
  font-size: 0.8125rem;
  font-weight: 700;
  gap: 0.5rem;
  min-height: 2.55rem;
  padding: 0 1rem;
  text-decoration: none;
  transition: background 0.15s;
  white-space: nowrap;
}

.export-report-btn:hover {
  background: #004395;
}

.filter-panel,
.table-panel,
.metric-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 1.25rem;
  box-shadow: 0 6px 24px rgba(15, 23, 42, 0.04);
}

.eyebrow {
  color: #2563eb;
  font-size: 0.7rem;
  font-weight: 900;
  letter-spacing: 0.12em;
  margin: 0 0 0.35rem;
  text-transform: uppercase;
}

.table-head h3 {
  color: #0f172a;
  font-size: 1.35rem;
  font-weight: 900;
  margin: 0;
}

.metric-grid {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(5, minmax(0, 1fr));
}

.metric-card {
  min-height: 8.5rem;
  overflow: hidden;
  padding: 1.25rem;
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.metric-card p {
  color: #64748b;
  font-size: 0.72rem;
  font-weight: 900;
  letter-spacing: 0.08em;
  margin: 0;
  text-transform: uppercase;
}

.metric-card strong {
  color: #0f172a;
  display: block;
  font-size: 1.65rem;
  font-weight: 950;
  line-height: 1.2;
  margin: 0.5rem 0;
  word-break: break-word;
}

.detail-label {
  color: #94a3b8;
  font-size: 0.75rem;
  font-weight: 600;
}

.metric-foot {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  font-size: 0.75rem;
  color: #64748b;
}

.trend {
  align-items: center;
  border-radius: 999px;
  display: inline-flex;
  font-size: 0.72rem;
  font-weight: 800;
  gap: 0.2rem;
  padding: 0.15rem 0.45rem;
}

.trend-up {
  background: #d1fae5;
  color: #047857;
}

.trend-down {
  background: #fee2e2;
  color: #b91c1c;
}

.trend-neutral {
  background: #f1f5f9;
  color: #475569;
}

.trend-icon {
  font-size: 0.9rem;
}

.filter-panel {
  display: grid;
  gap: 1rem;
  grid-template-columns: minmax(12rem, 1.2fr) minmax(10rem, 1fr) minmax(10rem, 1fr) minmax(8rem, 0.8fr) minmax(8rem, 0.8fr) auto;
  padding: 1.25rem;
  align-items: end;
}

.filter-field {
  display: grid;
  gap: 0.4rem;
  min-width: 0;
}

.filter-field span {
  color: #64748b;
  font-size: 0.73rem;
  font-weight: 800;
}

.filter-field input,
.filter-field select {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  color: #0f172a;
  font-family: inherit;
  font-size: 0.8rem;
  font-weight: 800;
  min-height: 2.55rem;
  padding: 0 0.85rem;
  width: 100%;
  outline: none;
}

.primary-btn,
.secondary-btn,
.pager-bar button {
  border-radius: 0.75rem;
  cursor: pointer;
  font-size: 0.8rem;
  font-weight: 800;
  min-height: 2.55rem;
  padding: 0 0.85rem;
  transition: background 0.15s, border-color 0.15s, color 0.15s;
}

.filter-actions {
  align-items: end;
  display: flex;
  gap: 0.5rem;
}

.primary-btn {
  background: #2563eb;
  border: 1px solid #2563eb;
  color: #fff;
}

.primary-btn:hover {
  background: #1d4ed8;
}

.secondary-btn,
.pager-bar button {
  background: #fff;
  border: 1px solid #e2e8f0;
  color: #475569;
}

.secondary-btn:hover,
.pager-bar button:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.table-panel {
  overflow: hidden;
}

.table-panel-spaced {
  margin-top: 0.5rem;
}

.table-head {
  align-items: center;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
  padding: 1rem 1.25rem;
}

.table-head span {
  color: #64748b;
  font-size: 0.8rem;
  font-weight: 800;
}

.table-scroll {
  overflow-x: auto;
}

table {
  border-collapse: collapse;
  min-width: 940px;
  width: 100%;
}

th {
  background: #f8fafc;
  color: #64748b;
  font-size: 0.7rem;
  font-weight: 900;
  letter-spacing: 0.06em;
  padding: 0.85rem 1.25rem;
  text-align: left;
  text-transform: uppercase;
}

td {
  border-top: 1px solid #f1f5f9;
  color: #475569;
  font-size: 0.86rem;
  font-weight: 650;
  padding: 0.95rem 1.25rem;
  vertical-align: middle;
}

.col-date {
  white-space: nowrap;
}

.col-amount {
  font-weight: 800;
  color: #0f172a;
}

.col-desc {
  font-weight: 800;
}

.user-name,
.booking-main {
  color: #0f172a;
  font-weight: 900;
  margin: 0;
}

.user-email,
.booking-sub {
  color: #94a3b8;
  font-size: 0.75rem;
  font-weight: 600;
  margin: 0.15rem 0 0;
}

.type-pill,
.duration-pill,
.pill {
  border-radius: 999px;
  display: inline-flex;
  font-size: 0.75rem;
  font-weight: 900;
  padding: 0.32rem 0.7rem;
  white-space: nowrap;
}

.type-pill-member {
  background: #dbeafe;
  color: #1d4ed8;
}

.type-pill-trainer {
  background: #ccfbf1;
  color: #0f766e;
}

.type-pill-class {
  background: #ffedd5;
  color: #c2410c;
}

.duration-pill {
  background: #f1f5f9;
  color: #475569;
}

.pill-success {
  background: #d1fae5;
  color: #065f46;
}

.pill-warning {
  background: #fef3c7;
  color: #92400e;
}

.pill-danger {
  background: #fee2e2;
  color: #991b1b;
}

.pill-muted {
  background: #f1f5f9;
  color: #475569;
}

.cat {
  border-radius: 0.35rem;
  font-size: 0.72rem;
  font-weight: 800;
  padding: 0.25rem 0.5rem;
  text-transform: uppercase;
}

.cat-premium {
  background: #faf5ff;
  border: 1px solid #d8b4fe;
  color: #7e22ce;
}

.cat-corporate {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1d4ed8;
}

.cat-upgrade {
  background: #fffbeb;
  border: 1px solid #fde68a;
  color: #b45309;
}

.cat-basic {
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  color: #475569;
}

.cat-trainer {
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  color: #047857;
}

.cat-default {
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  color: #475569;
}

.empty-row {
  color: #94a3b8;
  font-weight: 800;
  padding: 2rem;
  text-align: center;
}

.pager-bar {
  align-items: center;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  padding: 0.9rem 1.25rem;
}

.pager-bar p {
  color: #64748b;
  font-size: 0.82rem;
  font-weight: 700;
  margin: 0;
}

.pager-bar div {
  display: flex;
  gap: 0.5rem;
}

.pager-bar button:disabled {
  cursor: not-allowed;
  opacity: 0.45;
}

.pagination {
  display: flex;
  align-items: center;
  gap: 6px;
}

.pagination button {
  background: white;
  border: 1px solid #e2e8f0;
  color: #334155;
  font-family: inherit;
  font-size: 13px;
  font-weight: 700;
  min-width: 32px;
  height: 32px;
  border-radius: 8px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  padding: 0;
}

.pagination button:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.pagination button.active {
  background: #0058be;
  color: white;
  border-color: #0058be;
}

.pagination button:disabled {
  color: #cbd5e1;
  cursor: not-allowed;
  background: #f8fafc;
}

@media (max-width: 1280px) {
  .metric-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 1024px) {
  .filter-panel {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
  .search-field {
    grid-column: span 2;
  }
  .filter-actions {
    grid-column: span 3;
    justify-content: flex-end;
  }
}

@media (max-width: 768px) {
  .metric-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .filter-panel {
    grid-template-columns: 1fr;
  }
  .search-field,
  .filter-actions {
    grid-column: span 1;
  }
  .filter-actions {
    flex-direction: column-reverse;
  }
  .primary-btn,
  .secondary-btn,
  .pager-bar button {
    width: 100%;
  }
  .table-head {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
}

@media (max-width: 480px) {
  .metric-grid {
    grid-template-columns: 1fr;
  }
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-card {
  background: #fff;
  border-radius: 1.25rem;
  padding: 2rem;
  width: 90%;
  max-width: 400px;
  text-align: center;
  box-shadow: 0 20px 60px rgba(0,0,0,0.15);
}

.modal-title {
  font-size: 1.15rem;
  font-weight: 900;
  color: #0f172a;
  margin: 0 0 1.25rem;
}

.progress-bar-track {
  height: 8px;
  background: #e2e8f0;
  border-radius: 999px;
  overflow: hidden;
  margin-bottom: 0.75rem;
}

.progress-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #2563eb, #7c3aed);
  border-radius: 999px;
  transition: width 0.4s ease;
}

.progress-text {
  color: #64748b;
  font-size: 0.82rem;
  font-weight: 600;
  margin: 0 0 0.25rem;
}

.progress-pct {
  color: #0f172a;
  font-size: 1.5rem;
  font-weight: 950;
  margin: 0.5rem 0 1rem;
}
</style>
