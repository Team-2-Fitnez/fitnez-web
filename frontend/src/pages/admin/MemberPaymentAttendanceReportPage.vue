<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import { http } from '../../api/http'
import { useMemberPaymentAttendanceReportStore } from '../../stores/memberPaymentAttendanceReportStore'
import SkeletonStatGrid from '../../components/ui/skeleton/SkeletonStatGrid.vue'
import SkeletonTable from '../../components/ui/SkeletonTable.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'

const store = useMemberPaymentAttendanceReportStore()
const { loading: initialLoading, run, shimmerStyle } = useDeferredLoading()
const showFilters = ref(false)

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

function statusClass(status?: string | null) {
  if (status === 'paid' || status === 'completed' || status === 'approved') return 'pill pill-success'
  if (status === 'pending') return 'pill pill-warning'
  if (status === 'failed' || status === 'rejected') return 'pill pill-danger'
  return 'pill pill-muted'
}

function statusLabel(status?: string | null) {
  const labels: Record<string, string> = {
    approved: 'Approved',
    completed: 'Completed',
    failed: 'Failed',
    paid: 'Successful',
    pending: 'Pending',
    rejected: 'Rejected',
  }

  return status ? labels[status] || status : 'Not configured'
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

function paymentDescription(row: { invoice_number?: string; user?: { full_name?: string | null } | null }) {
  const name = row.user?.full_name
  if (name) return `Payment by ${name}`
  return row.invoice_number || 'Member Payment'
}

const retentionRate = computed(() => {
  const attendance = Number(store.summary?.total_attendance || 0)
  const payments = Number(store.summary?.total_payments || 0)
  if (!payments) return '0%'
  return `${Math.min(100, Math.round((attendance / payments) * 100))}%`
})

const activePeriodLabel = computed(() => {
  if (store.startDate || store.endDate) {
    const start = store.startDate
      ? new Date(store.startDate).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' })
      : 'Start'
    const end = store.endDate
      ? new Date(store.endDate).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' })
      : 'Now'
    return `${start} - ${end}`
  }

  return new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(new Date())
})

const periodButtonLabel = computed(() => {
  if (store.startDate || store.endDate) return activePeriodLabel.value
  return `This Month (${new Intl.DateTimeFormat('en-US', { month: 'short', year: 'numeric' }).format(new Date())})`
})



function setPaymentStatusChip(status: string) {
  store.paymentStatus = status
}

const hasActiveFilters = computed(() => {
  return Boolean(
    store.search ||
      store.paymentStatus ||
      store.paymentType ||
      store.attendanceType ||
      store.startDate ||
      store.endDate,
  )
})

function applyFilters() {
  store.refresh()
  showFilters.value = false
}

function resetFilters() {
  store.search = ''
  store.paymentStatus = ''
  store.paymentType = ''
  store.attendanceType = ''
  store.startDate = ''
  store.endDate = ''
  store.refresh()
  showFilters.value = false
}

onMounted(() => {
  run(async () => {
    await store.loadSummary()
    await store.loadPayments()
    await store.loadAttendance()
  })
})
</script>

<template>
  <WorkspaceLayout
    role="admin"
    sidebar-title="Admin"
    title="Admin Reports"
    subtitle="Summary of member payments and attendance in one admin report."
    :sidebar-items="adminSidebarItems"
  >
    <div v-if="initialLoading && !store.payments.length" class="report-overview" :style="shimmerStyle">
      <SkeletonStatGrid :count="3" />
      <SkeletonTable :columns="5" :rows="6" style="margin-top: 1.25rem" />
      <SkeletonTable :columns="6" :rows="6" style="margin-top: 1.25rem" />
    </div>
    <div v-else class="report-overview">
      <div class="overview-toolbar">
        <p class="period-summary">
          <span class="ui-icon ui-icon-calendar period-icon" aria-hidden="true"></span>
          Period: {{ periodButtonLabel }}
        </p>
        <div class="overview-actions">
          <button
            type="button"
            class="filter-toggle-btn"
            :class="{ 'filter-toggle-btn--open': showFilters }"
            :aria-expanded="showFilters"
            @click="showFilters = !showFilters"
          >
            <span class="ui-icon ui-icon-filter" aria-hidden="true"></span>
            Filter
            <span v-if="hasActiveFilters" class="filter-active-dot" aria-hidden="true"></span>
            <span :class="['ui-icon', 'ui-icon-chevron', { 'ui-icon-chevron--up': showFilters }]" aria-hidden="true"></span>
          </button>
          <a :href="http.url('/admin/export/member-reports')" class="print-btn">
            <span class="ui-icon ui-icon-print" aria-hidden="true"></span>
            Print Monthly Report
          </a>
        </div>
      </div>

      <div v-show="showFilters" class="filter-panel">
        <div class="filter-panel-head">
          <h3>Filter Admin Reports</h3>
          <button type="button" class="filter-close-btn" aria-label="Close filter" @click="showFilters = false">
            <span class="ui-icon ui-icon-close" aria-hidden="true"></span>
          </button>
        </div>

        <p class="filter-section-label">Payment Status</p>
        <div class="filter-chips">
          <button type="button" class="chip-btn" :class="{ active: store.paymentStatus === '' }" @click="setPaymentStatusChip('')">All</button>
          <button type="button" class="chip-btn" :class="{ active: store.paymentStatus === 'paid' }" @click="setPaymentStatusChip('paid')">Successful</button>
          <button type="button" class="chip-btn" :class="{ active: store.paymentStatus === 'pending' }" @click="setPaymentStatusChip('pending')">Pending</button>
          <button type="button" class="chip-btn" :class="{ active: store.paymentStatus === 'failed' }" @click="setPaymentStatusChip('failed')">Failed</button>
        </div>

        <div class="filter-grid">
          <label class="filter-field filter-span-full">
            <span>Search</span>
            <input v-model="store.search" class="filter-input" placeholder="Member name, invoice, or type" @keyup.enter="applyFilters" />
          </label>
          <label class="filter-field">
            <span>Payment Type</span>
            <input v-model="store.paymentType" class="filter-input" placeholder="membership, trainer" />
          </label>
          <label class="filter-field">
            <span>Attendance Type</span>
            <input v-model="store.attendanceType" class="filter-input" placeholder="gym, trainer" />
          </label>
          <label class="filter-field">
            <span>Start Date</span>
            <input v-model="store.startDate" class="filter-input" type="date" />
          </label>
          <label class="filter-field">
            <span>End Date</span>
            <input v-model="store.endDate" class="filter-input" type="date" />
          </label>
        </div>

        <div class="filter-panel-actions">
          <button type="button" class="filter-secondary-btn" @click="resetFilters">Reset</button>
          <button type="button" class="filter-primary-btn" @click="applyFilters">Apply Filters</button>
        </div>
      </div>

      <div class="metric-row">
        <article class="metric-card">
          <p class="metric-label">Total Revenue</p>
          <p class="metric-value">{{ currency(store.summary?.total_payment_amount || 0) }}</p>
          <div class="metric-foot">
            <span :class="['trend', (store.summary?.total_payment_amount_trend ?? 0) > 0 ? 'trend-up' : (store.summary?.total_payment_amount_trend ?? 0) < 0 ? 'trend-down' : 'trend-neutral']">
              <span class="material-symbols-outlined metric-icon" aria-hidden="true">{{ (store.summary?.total_payment_amount_trend ?? 0) > 0 ? 'trending_up' : (store.summary?.total_payment_amount_trend ?? 0) < 0 ? 'trending_down' : 'trending_flat' }}</span>
              {{ (store.summary?.total_payment_amount_trend ?? 0) > 0 ? '+' : '' }}{{ store.summary?.total_payment_amount_trend ?? 0 }}%
            </span>
            <span class="metric-compare">this week</span>
          </div>
        </article>

        <article class="metric-card">
          <p class="metric-label">Total Transactions</p>
          <p class="metric-value">{{ (store.summary?.total_payments ?? 0).toLocaleString('en-US') }}</p>
          <div class="metric-foot">
            <span :class="['trend', (store.summary?.total_payments_trend ?? 0) > 0 ? 'trend-up' : (store.summary?.total_payments_trend ?? 0) < 0 ? 'trend-down' : 'trend-neutral']">
              <span class="material-symbols-outlined metric-icon" aria-hidden="true">{{ (store.summary?.total_payments_trend ?? 0) > 0 ? 'trending_up' : (store.summary?.total_payments_trend ?? 0) < 0 ? 'trending_down' : 'trending_flat' }}</span>
              {{ (store.summary?.total_payments_trend ?? 0) > 0 ? '+' : '' }}{{ store.summary?.total_payments_trend ?? 0 }}%
            </span>
            <span class="metric-compare">this week</span>
          </div>
        </article>

        <article class="metric-card">
          <p class="metric-label">Attendance Ratio</p>
          <p class="metric-value">{{ retentionRate }}</p>
          <div class="metric-foot">
            <span :class="['trend', (store.summary?.attendance_today_trend ?? 0) > 0 ? 'trend-up' : (store.summary?.attendance_today_trend ?? 0) < 0 ? 'trend-down' : 'trend-neutral']">
              <span class="material-symbols-outlined metric-icon" aria-hidden="true">{{ (store.summary?.attendance_today_trend ?? 0) > 0 ? 'trending_up' : (store.summary?.attendance_today_trend ?? 0) < 0 ? 'trending_down' : 'trending_flat' }}</span>
              {{ (store.summary?.attendance_today_trend ?? 0) > 0 ? '+' : '' }}{{ store.summary?.attendance_today_trend ?? 0 }}%
            </span>
            <span class="metric-compare">today</span>
          </div>
        </article>
      </div>

      <section class="table-panel">
        <div class="table-panel-head">
          <h2>Payment Details</h2>
        </div>

        <div class="table-scroll">
          <table class="report-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Type</th>
                <th>Status</th>
                <th class="col-amount">Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in store.payments" :key="row.id">
                <td class="col-date">{{ formatDate(row.payment_date) }}</td>
                <td class="col-desc">{{ paymentDescription(row) }}</td>
                <td><span :class="categoryClass(row.payment_type)">{{ formatTypeLabel(row.payment_type) }}</span></td>
                <td><span :class="statusClass(row.payment_status)">{{ statusLabel(row.payment_status) }}</span></td>
                <td class="col-amount">{{ currency(row.amount) }}</td>
              </tr>
              <tr v-if="store.loadingPayments && store.payments.length">
                <td colspan="5" class="empty-row">Updating...</td>
              </tr>
              <tr v-else-if="!store.payments.length">
                <td colspan="5" class="empty-row">No payment data available.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="pager-bar">
          <p>{{ store.paymentsTotal }} records - page {{ store.paymentsPage }} of {{ store.paymentsLastPage }}</p>
          <div>
            <button type="button" class="pager-btn" :disabled="store.paymentsPage <= 1" @click="store.previousPaymentsPage">Previous</button>
            <button type="button" class="pager-btn" :disabled="store.paymentsPage >= store.paymentsLastPage" @click="store.nextPaymentsPage">Next</button>
          </div>
        </div>
      </section>

      <section class="table-panel table-panel-spaced">
        <div class="table-panel-head">
          <h2>Attendance History</h2>
        </div>

        <div class="table-scroll">
          <table class="report-table">
            <thead>
              <tr>
                <th>Member</th>
                <th>Type</th>
                <th>Check In</th>
                <th>Check Out</th>
                <th>Booking</th>
                <th>Trainer</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in store.attendance" :key="row.id">
                <td>
                  <p class="member-name">{{ row.user?.full_name || '-' }}</p>
                  <p class="member-email">{{ row.user?.email || '-' }}</p>
                </td>
                <td><span :class="categoryClass(row.attendance_type)">{{ formatTypeLabel(row.attendance_type) }}</span></td>
                <td class="col-date">{{ formatDateTime(row.check_in_time) }}</td>
                <td class="col-date">{{ formatDateTime(row.check_out_time) }}</td>
                <td>{{ row.booking?.session_type || '-' }}</td>
                <td>{{ row.booking?.trainer?.full_name || '-' }}</td>
              </tr>
              <tr v-if="store.loadingAttendance && store.attendance.length">
                <td colspan="6" class="empty-row">Updating...</td>
              </tr>
              <tr v-else-if="!store.attendance.length">
                <td colspan="6" class="empty-row">No attendance data available.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="pager-bar">
          <p>{{ store.attendanceTotal }} records - page {{ store.attendancePage }} of {{ store.attendanceLastPage }}</p>
          <div>
            <button type="button" class="pager-btn" :disabled="store.attendancePage <= 1" @click="store.previousAttendancePage">Previous</button>
            <button type="button" class="pager-btn" :disabled="store.attendancePage >= store.attendanceLastPage" @click="store.nextAttendancePage">Next</button>
          </div>
        </div>
      </section>
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
  font-size: 1rem;
  font-style: normal;
  font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
  font-weight: normal;
  letter-spacing: normal;
  line-height: 1;
  overflow: hidden;
  text-transform: none;
  white-space: nowrap;
  width: 1em;
  word-wrap: normal;
  -webkit-font-feature-settings: 'liga';
  -webkit-font-smoothing: antialiased;
}

.icon-fill {
  font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}

.metric-icon {
  font-size: 1rem;
}

.ui-icon {
  display: inline-block;
  flex: 0 0 auto;
  height: 1rem;
  position: relative;
  width: 1em;
}

.ui-icon-calendar {
  border: 2px solid currentColor;
  border-radius: 0.22rem;
}

.ui-icon-calendar::before {
  background: currentColor;
  content: '';
  height: 2px;
  left: 0.12rem;
  position: absolute;
  right: 0.12rem;
  top: 0.28rem;
}

.ui-icon-filter::before,
.ui-icon-filter::after {
  background: currentColor;
  border-radius: 999px;
  content: '';
  height: 2px;
  left: 0.05rem;
  position: absolute;
  right: 0.05rem;
}

.ui-icon-filter::before {
  top: 0.28rem;
}

.ui-icon-filter::after {
  bottom: 0.28rem;
}

.ui-icon-chevron {
  border-bottom: 2px solid currentColor;
  border-right: 2px solid currentColor;
  height: 0.55rem;
  transform: rotate(45deg);
  transition: transform 0.15s ease;
  width: 0.55rem;
}

.ui-icon-chevron--up {
  transform: rotate(225deg);
}

.ui-icon-print {
  border: 2px solid currentColor;
  border-radius: 0.18rem;
  height: 0.72rem;
  margin-top: 0.18rem;
}

.ui-icon-print::before {
  background: #0058be;
  border: 2px solid currentColor;
  content: '';
  height: 0.45rem;
  left: 0.13rem;
  position: absolute;
  right: 0.13rem;
  top: -0.48rem;
}

.ui-icon-close::before,
.ui-icon-close::after {
  background: currentColor;
  border-radius: 999px;
  content: '';
  height: 2px;
  left: 0.1rem;
  position: absolute;
  right: 0.1rem;
  top: 0.48rem;
}

.ui-icon-close::before {
  transform: rotate(45deg);
}

.ui-icon-close::after {
  transform: rotate(-45deg);
}

.report-overview {
  color: #0f172a;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  padding-bottom: 1rem;
}

.overview-toolbar {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  justify-content: space-between;
  margin-bottom: 0.25rem;
}

.period-summary {
  align-items: center;
  color: #64748b;
  display: flex;
  font-size: 0.875rem;
  font-weight: 600;
  gap: 0.35rem;
  margin: 0;
  min-width: 0;
}

.period-icon {
  color: #94a3b8;
  font-size: 1.1rem;
}

.overview-actions {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  justify-content: flex-end;
  min-width: min(100%, 20rem);
}

.filter-toggle-btn,
.print-btn {
  align-items: center;
  border-radius: 0.65rem;
  display: inline-flex;
  font-size: 0.8125rem;
  font-weight: 700;
  gap: 0.5rem;
  min-height: 2.5rem;
  padding: 0 0.9rem;
  text-decoration: none;
  transition: background 0.15s, border-color 0.15s;
  white-space: nowrap;
}

.filter-toggle-btn {
  background: #fff;
  border: 1px solid #e2e8f0;
  color: #475569;
  cursor: pointer;
  position: relative;
}

.filter-toggle-btn:hover,
.filter-toggle-btn--open {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: #0f172a;
}

.filter-active-dot {
  background: #2563eb;
  border-radius: 999px;
  height: 0.5rem;
  position: absolute;
  right: 0.55rem;
  top: 0.55rem;
  width: 0.5rem;
}

.print-btn {
  background: #0058be;
  border: none;
  color: #fff;
}

.print-btn:hover {
  background: #004395;
}

.filter-panel {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 0.875rem;
  box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
  display: grid;
  gap: 0.9rem;
  padding: 1rem;
}

.filter-panel-head {
  align-items: center;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
}

.filter-panel-head h3 {
  color: #0f172a;
  font-size: 0.95rem;
  font-weight: 800;
  margin: 0;
}

.filter-close-btn {
  align-items: center;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.55rem;
  color: #64748b;
  cursor: pointer;
  display: inline-flex;
  height: 1.9rem;
  justify-content: center;
  width: 1.9rem;
}

.filter-close-btn:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.filter-section-label {
  color: #64748b;
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  margin: 0;
  text-transform: uppercase;
}

.filter-chips {
  display: grid;
  gap: 0.5rem;
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.chip-btn {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 0.625rem;
  color: #475569;
  cursor: pointer;
  font-size: 0.8125rem;
  font-weight: 700;
  min-height: 2.25rem;
  padding: 0 0.75rem;
  text-align: center;
  transition: background 0.15s, border-color 0.15s, color 0.15s;
  white-space: nowrap;
}

.chip-btn:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.chip-btn.active {
  background: #e0e7ff;
  border-color: #c7d2fe;
  color: #2563eb;
}

.filter-grid {
  display: grid;
  gap: 0.85rem;
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.filter-field {
  display: grid;
  gap: 0.35rem;
  min-width: 0;
}

.filter-field span {
  color: #64748b;
  font-size: 0.75rem;
  font-weight: 700;
}

.filter-span-full {
  grid-column: span 2;
}

.filter-input {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.55rem;
  color: #0f172a;
  font-size: 0.875rem;
  font-weight: 500;
  min-height: 2.35rem;
  padding: 0 0.75rem;
  width: 100%;
}

.filter-panel-actions {
  border-top: 1px solid #e2e8f0;
  display: grid;
  gap: 0.75rem;
  grid-template-columns: repeat(2, minmax(0, 10rem));
  justify-content: flex-end;
  padding-top: 1rem;
}

.filter-primary-btn,
.filter-secondary-btn {
  align-items: center;
  border-radius: 0.65rem;
  cursor: pointer;
  display: inline-flex;
  font-size: 0.875rem;
  font-weight: 700;
  justify-content: center;
  min-height: 2.35rem;
  padding: 0 1rem;
  width: 100%;
}

.filter-primary-btn {
  background: #0058be;
  border: none;
  color: #fff;
}

.filter-primary-btn:hover {
  background: #004395;
}

.filter-secondary-btn {
  background: #fff;
  border: 1px solid #e2e8f0;
  color: #475569;
}

.filter-secondary-btn:hover {
  background: #f8fafc;
}

.metric-row {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.metric-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 0.875rem;
  box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
  min-height: 8.25rem;
  padding: 1.25rem;
}

.metric-label {
  color: #94a3b8;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  margin: 0 0 0.65rem;
  text-transform: uppercase;
}

.metric-value {
  color: #0f172a;
  font-size: clamp(1.35rem, 2.2vw, 1.65rem);
  font-weight: 800;
  line-height: 1.1;
  margin: 0 0 0.85rem;
}

.metric-foot {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.trend {
  align-items: center;
  border-radius: 999px;
  display: inline-flex;
  font-size: 0.75rem;
  font-weight: 800;
  gap: 0.35rem;
  line-height: 1;
  min-height: 1.6rem;
  padding: 0.25rem 0.5rem;
}

.trend-up {
  background: #d1fae5;
  color: #047857;
}

.trend-neutral {
  background: #e0e7ff;
  color: #3730a3;
}

.metric-compare {
  color: #94a3b8;
  font-size: 0.75rem;
  font-weight: 600;
}

.table-panel {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 0.875rem;
  box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
  overflow: hidden;
}

.table-panel-spaced {
  margin-top: 0.25rem;
}

.table-panel-head {
  align-items: center;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  justify-content: space-between;
  padding: 1rem 1.25rem;
}

.table-panel-head h2 {
  color: #0f172a;
  font-size: 1rem;
  font-weight: 800;
  margin: 0;
}

.table-scroll {
  overflow-x: auto;
}

.report-table {
  border-collapse: collapse;
  min-width: 880px;
  width: 100%;
}

.report-table th {
  background: #f1f5f9;
  color: #64748b;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  padding: 0.75rem 1.25rem;
  text-align: left;
  text-transform: uppercase;
  white-space: nowrap;
}

.report-table td {
  border-bottom: 1px solid #f1f5f9;
  color: #475569;
  font-size: 0.875rem;
  font-weight: 600;
  padding: 0.85rem 1.25rem;
  vertical-align: middle;
}

.report-table tbody tr:last-child td {
  border-bottom: none;
}

.col-date {
  color: #64748b;
  white-space: nowrap;
}

.col-desc {
  color: #334155;
  font-weight: 700;
}

.col-amount {
  color: #0f172a;
  font-weight: 800;
  text-align: right;
  white-space: nowrap;
}

.member-name {
  color: #0f172a;
  font-weight: 800;
  margin: 0;
}

.member-email {
  color: #94a3b8;
  font-size: 0.75rem;
  font-weight: 500;
  margin: 0.15rem 0 0;
}

.cat {
  font-size: 0.8125rem;
  font-weight: 800;
  text-transform: capitalize;
}

.cat-premium {
  color: #2563eb;
}

.cat-basic {
  color: #1e40af;
}

.cat-upgrade {
  color: #7c3aed;
}

.cat-corporate {
  color: #c2410c;
}

.cat-trainer {
  color: #0d9488;
}

.cat-default {
  color: #0058be;
}

.pill {
  border-radius: 999px;
  display: inline-block;
  font-size: 0.75rem;
  font-weight: 800;
  padding: 0.25rem 0.65rem;
}

.pill-success {
  background: #d1fae5;
  color: #047857;
}

.pill-warning {
  background: #fef3c7;
  color: #b45309;
}

.pill-danger {
  background: #fee2e2;
  color: #b91c1c;
}

.pill-muted {
  background: #f1f5f9;
  color: #64748b;
}

.empty-row {
  color: #94a3b8 !important;
  font-weight: 700 !important;
  padding: 2rem !important;
  text-align: center !important;
}

.pager-bar {
  align-items: center;
  border-top: 1px solid #e2e8f0;
  color: #64748b;
  display: flex;
  flex-wrap: wrap;
  font-size: 0.8125rem;
  font-weight: 600;
  gap: 0.75rem;
  justify-content: space-between;
  padding: 0.85rem 1.25rem;
}

.pager-bar p {
  margin: 0;
}

.pager-bar div {
  display: flex;
  gap: 0.5rem;
}

.pager-btn {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 0.55rem;
  color: #475569;
  cursor: pointer;
  font-size: 0.8125rem;
  font-weight: 700;
  min-height: 2.1rem;
  padding: 0 0.75rem;
}

.pager-btn:hover:not(:disabled) {
  background: #f8fafc;
}

.pager-btn:disabled {
  cursor: not-allowed;
  opacity: 0.45;
}

@media (max-width: 960px) {
  .metric-row {
    grid-template-columns: 1fr;
  }

  .filter-chips,
  .filter-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .filter-span-full {
    grid-column: 1 / -1;
  }
}

@media (max-width: 640px) {
  .overview-toolbar,
  .overview-actions,
  .table-panel-head,
  .pager-bar,
  .filter-panel-actions {
    align-items: stretch;
    flex-direction: column;
  }

  .filter-toggle-btn,
  .print-btn,
  .filter-primary-btn,
  .filter-secondary-btn,
  .pager-btn {
    justify-content: center;
    width: 100%;
  }

  .pager-bar div {
    width: 100%;
  }

  .pager-bar .pager-btn {
    flex: 1;
  }

  .filter-chips,
  .filter-grid,
  .filter-panel-actions {
    grid-template-columns: 1fr;
  }

  .overview-actions {
    min-width: 0;
    width: 100%;
  }
}
</style>
