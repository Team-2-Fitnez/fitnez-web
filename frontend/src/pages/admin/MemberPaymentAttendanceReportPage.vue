<script setup lang="ts">
import { onMounted } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import StatCard from '../../components/ui/StatCard.vue'
import ExcelImportModal from '../../components/ExcelImportModal.vue'
import { http } from '../../api/http'
import { useMemberPaymentAttendanceReportStore } from '../../stores/memberPaymentAttendanceReportStore'

const store = useMemberPaymentAttendanceReportStore()

function currency(value?: string | number | null) {
  const numberValue = Number(value || 0)
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(numberValue)
}

function formatDateTime(value?: string | null) {
  if (!value) return '-'
  return new Date(value).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function statusClass(status?: string | null) {
  if (status === 'paid' || status === 'completed' || status === 'approved') return 'status status-success'
  if (status === 'pending') return 'status status-warning'
  if (status === 'failed' || status === 'rejected') return 'status status-danger'
  return 'status status-info'
}

onMounted(() => {
  store.loadSummary()
  store.loadPayments()
  store.loadAttendance()
})
</script>

<template>
  <WorkspaceLayout
    role="admin"
    sidebar-title="Admin"
    title="Member Payment & Attendance Report"
    subtitle="View all member payment data and attendance history in one admin report."
    :sidebar-items="adminSidebarItems"
  >
    <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-bottom: 0.75rem;">
      <button class="button button-ghost" style="font-size: 0.85rem;" @click="http.downloadBlob('/admin/export/payments', 'payments.xlsx')">Export Payments</button>
      <button class="button button-ghost" style="font-size: 0.85rem;" @click="http.downloadBlob('/admin/export/attendance', 'attendance.xlsx')">Export Attendance</button>
      <ExcelImportModal import-type="schedules" label="Import Excel" />
    </div>
    <div class="report-stat-grid">
      <StatCard label="Payments" :value="store.summary?.total_payments || 0" hint="All payment rows" />
      <StatCard label="Payment Amount" :value="currency(store.summary?.total_payment_amount || 0)" hint="Total recorded amount" />
      <StatCard label="Attendance" :value="store.summary?.total_attendance || 0" hint="All check-in rows" />
      <StatCard label="Today Attendance" :value="store.summary?.attendance_today || 0" hint="Today check-ins" />
    </div>

    <FitnezCard class="report-card">
      <div class="report-toolbar">
        <div>
          <p class="eyebrow">Unified Report</p>
          <h2 class="title-md">Report filters</h2>
          <p class="text-muted">Filter payment and attendance records by member, status, type, or date range.</p>
        </div>
        <button class="button button-primary toolbar-button" @click="store.refresh">Apply Filter</button>
      </div>

      <div class="filter-grid">
        <label class="filter-field filter-field-wide">
          <span>Search</span>
          <input v-model="store.search" class="form-input" placeholder="Member, invoice, type..." />
        </label>
        <label class="filter-field">
          <span>Payment status</span>
          <select v-model="store.paymentStatus" class="form-input">
            <option value="">All payment status</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="failed">Failed</option>
            <option value="rejected">Rejected</option>
          </select>
        </label>
        <label class="filter-field">
          <span>Payment type</span>
          <input v-model="store.paymentType" class="form-input" placeholder="membership, trainer..." />
        </label>
        <label class="filter-field">
          <span>Attendance type</span>
          <input v-model="store.attendanceType" class="form-input" placeholder="gym, trainer..." />
        </label>
        <label class="filter-field">
          <span>Start date</span>
          <input v-model="store.startDate" class="form-input" type="date" />
        </label>
        <label class="filter-field">
          <span>End date</span>
          <input v-model="store.endDate" class="form-input" type="date" />
        </label>
      </div>
    </FitnezCard>

    <FitnezCard class="report-card">
      <div class="table-heading">
        <div>
          <p class="eyebrow">Payment</p>
          <h2 class="title-md">Payment records</h2>
          <p class="text-muted">All member payment rows, including membership and trainer booking payments.</p>
        </div>
      </div>

      <div class="responsive-table">
        <table class="data-table report-table">
          <thead>
            <tr>
              <th>Invoice</th>
              <th>Member</th>
              <th>Type</th>
              <th>Amount</th>
              <th>Method</th>
              <th>Status</th>
              <th>Payment Date</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in store.payments" :key="row.id">
              <td class="strong-text">{{ row.invoice_number }}</td>
              <td>
                <p class="strong-text">{{ row.user?.full_name || '-' }}</p>
                <p class="text-muted small-text">{{ row.user?.email || '-' }}</p>
              </td>
              <td>{{ row.payment_type }}</td>
              <td class="strong-text">{{ currency(row.amount) }}</td>
              <td>{{ row.payment_method }}</td>
              <td><span :class="statusClass(row.payment_status)">{{ row.payment_status || 'not set' }}</span></td>
              <td>{{ formatDateTime(row.payment_date) }}</td>
            </tr>
            <tr v-if="!store.payments.length && !store.loadingPayments">
              <td colspan="7" class="empty-cell">No payment records found.</td>
            </tr>
            <tr v-if="store.loadingPayments">
              <td colspan="7" class="empty-cell">Loading payment records...</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mobile-record-list">
        <article v-for="row in store.payments" :key="`payment-${row.id}`" class="mobile-record-card">
          <div class="mobile-record-head">
            <div>
              <strong>{{ row.invoice_number }}</strong>
              <small>{{ row.user?.full_name || '-' }} · {{ row.user?.email || '-' }}</small>
            </div>
            <span :class="statusClass(row.payment_status)">{{ row.payment_status || 'not set' }}</span>
          </div>
          <div class="mobile-amount">
            <span>Amount</span>
            <strong>{{ currency(row.amount) }}</strong>
          </div>
          <dl class="mobile-detail-grid">
            <div>
              <dt>Type</dt>
              <dd>{{ row.payment_type }}</dd>
            </div>
            <div>
              <dt>Method</dt>
              <dd>{{ row.payment_method }}</dd>
            </div>
            <div>
              <dt>Payment Date</dt>
              <dd>{{ formatDateTime(row.payment_date) }}</dd>
            </div>
          </dl>
        </article>
        <div v-if="!store.payments.length && !store.loadingPayments" class="soft-empty">No payment records found.</div>
        <div v-if="store.loadingPayments" class="soft-empty">Loading payment records...</div>
      </div>

      <div class="pager-bar">
        <p>{{ store.paymentsTotal }} records · Page {{ store.paymentsPage }} of {{ store.paymentsLastPage }}</p>
        <div>
          <button class="button button-ghost button-small" :disabled="store.paymentsPage <= 1" @click="store.previousPaymentsPage">Previous</button>
          <button class="button button-ghost button-small" :disabled="store.paymentsPage >= store.paymentsLastPage" @click="store.nextPaymentsPage">Next</button>
        </div>
      </div>
    </FitnezCard>

    <FitnezCard class="report-card">
      <div class="table-heading">
        <div>
          <p class="eyebrow">Attendance</p>
          <h2 class="title-md">Attendance records</h2>
          <p class="text-muted">All member check-in and check-out activity.</p>
        </div>
      </div>

      <div class="responsive-table">
        <table class="data-table report-table">
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
                <p class="strong-text">{{ row.user?.full_name || '-' }}</p>
                <p class="text-muted small-text">{{ row.user?.email || '-' }}</p>
              </td>
              <td><span class="status status-info">{{ row.attendance_type }}</span></td>
              <td>{{ formatDateTime(row.check_in_time) }}</td>
              <td>{{ formatDateTime(row.check_out_time) }}</td>
              <td>{{ row.booking?.session_type || '-' }}</td>
              <td>{{ row.booking?.trainer?.full_name || '-' }}</td>
            </tr>
            <tr v-if="!store.attendance.length && !store.loadingAttendance">
              <td colspan="6" class="empty-cell">No attendance records found.</td>
            </tr>
            <tr v-if="store.loadingAttendance">
              <td colspan="6" class="empty-cell">Loading attendance records...</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mobile-record-list">
        <article v-for="row in store.attendance" :key="`attendance-${row.id}`" class="mobile-record-card">
          <div class="mobile-record-head">
            <div>
              <strong>{{ row.user?.full_name || '-' }}</strong>
              <small>{{ row.user?.email || '-' }}</small>
            </div>
            <span class="status status-info">{{ row.attendance_type }}</span>
          </div>
          <dl class="mobile-detail-grid">
            <div>
              <dt>Check In</dt>
              <dd>{{ formatDateTime(row.check_in_time) }}</dd>
            </div>
            <div>
              <dt>Check Out</dt>
              <dd>{{ formatDateTime(row.check_out_time) }}</dd>
            </div>
            <div>
              <dt>Booking</dt>
              <dd>{{ row.booking?.session_type || '-' }}</dd>
            </div>
            <div>
              <dt>Trainer</dt>
              <dd>{{ row.booking?.trainer?.full_name || '-' }}</dd>
            </div>
          </dl>
        </article>
        <div v-if="!store.attendance.length && !store.loadingAttendance" class="soft-empty">No attendance records found.</div>
        <div v-if="store.loadingAttendance" class="soft-empty">Loading attendance records...</div>
      </div>

      <div class="pager-bar">
        <p>{{ store.attendanceTotal }} records · Page {{ store.attendancePage }} of {{ store.attendanceLastPage }}</p>
        <div>
          <button class="button button-ghost button-small" :disabled="store.attendancePage <= 1" @click="store.previousAttendancePage">Previous</button>
          <button class="button button-ghost button-small" :disabled="store.attendancePage >= store.attendanceLastPage" @click="store.nextAttendancePage">Next</button>
        </div>
      </div>
    </FitnezCard>
  </WorkspaceLayout>
</template>

<style scoped>
.report-stat-grid {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 13rem), 1fr));
}

.report-card {
  margin-top: 1.25rem;
}

.report-toolbar,
.table-heading,
.pager-bar,
.mobile-record-head {
  align-items: flex-start;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
}

.toolbar-button {
  flex: 0 0 auto;
}

.filter-grid {
  display: grid;
  gap: 0.85rem;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 12rem), 1fr));
  margin-top: 1.25rem;
}

.filter-field {
  display: grid;
  gap: 0.5rem;
}

.filter-field span {
  font-size: 0.85rem;
  font-weight: 900;
}

.responsive-table {
  margin-top: 1rem;
  overflow-x: auto;
}

.report-table {
  min-width: 1040px;
}

.strong-text {
  font-weight: 900;
}

.small-text {
  font-size: 0.8rem;
}

.empty-cell {
  color: var(--color-muted);
  font-weight: 800;
  padding-block: 2.5rem;
  text-align: center;
}

.pager-bar {
  align-items: center;
  border-top: 1px solid rgba(0, 0, 0, 0.10);
  margin-top: 1rem;
  padding-top: 1rem;
}

.pager-bar p {
  color: var(--color-muted);
  font-size: 0.875rem;
  font-weight: 800;
}

.pager-bar div {
  display: flex;
  gap: 0.5rem;
}

.mobile-record-list {
  display: none;
}

.mobile-record-card {
  background: var(--color-white);
  border: 1px solid rgba(0, 0, 0, 0.10);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-panel);
  display: grid;
  gap: 1rem;
  padding: 1rem;
}

.mobile-record-head strong,
.mobile-amount strong {
  display: block;
  font-weight: 900;
}

.mobile-record-head small,
.mobile-amount span,
.mobile-detail-grid dt {
  color: var(--color-muted);
  display: block;
  font-size: 0.78rem;
  font-weight: 800;
  margin-top: 0.2rem;
}

.mobile-amount {
  background: var(--color-blue-dark);
  border-radius: var(--radius-md);
  color: var(--color-white);
  padding: 1rem;
}

.mobile-amount span {
  color: rgba(255, 255, 255, 0.72);
  margin-top: 0;
}

.mobile-amount strong {
  font-size: 1.35rem;
  margin-top: 0.25rem;
}

.mobile-detail-grid {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  margin: 0;
}

.mobile-detail-grid div {
  background: var(--color-cream);
  border-radius: 1rem;
  padding: 0.75rem;
}

.mobile-detail-grid dd {
  font-weight: 900;
  margin: 0.2rem 0 0;
}

.soft-empty {
  background: var(--color-cream);
  border: 1px dashed rgba(0, 0, 0, 0.18);
  border-radius: var(--radius-md);
  color: var(--color-muted);
  font-weight: 800;
  padding: 1rem;
  text-align: center;
}

@media (min-width: 960px) {
  .filter-field-wide {
    grid-column: span 2;
  }
}

@media (max-width: 760px) {
  .report-toolbar,
  .table-heading,
  .pager-bar,
  .mobile-record-head {
    align-items: stretch;
    flex-direction: column;
  }

  .toolbar-button,
  .pager-bar button,
  .pager-bar div {
    width: 100%;
  }

  .responsive-table {
    display: none;
  }

  .mobile-record-list {
    display: grid;
    gap: 0.85rem;
    margin-top: 1rem;
  }

  .mobile-detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>
