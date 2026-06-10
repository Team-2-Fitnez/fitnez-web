<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import StatCard from '../../components/ui/StatCard.vue'
import { http } from '../../api/http'
import type { MemberReportPayment, MemberPaymentAttendanceSummary } from '../../types/memberPaymentAttendanceReport'
import SkeletonStatGrid from '../../components/ui/skeleton/SkeletonStatGrid.vue'
import SkeletonTable from '../../components/ui/SkeletonTable.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'
import { useAutoRefresh } from '../../composables/useAutoRefresh'

const payments = ref<MemberReportPayment[]>([])
const { loading, run, shimmerStyle } = useDeferredLoading()
const summary = ref<Pick<MemberPaymentAttendanceSummary, 'total_payments' | 'total_payment_amount' | 'paid_payments' | 'pending_payments'>>({
  total_payments: 0,
  total_payment_amount: 0,
  paid_payments: 0,
  pending_payments: 0,
})

function formatMoney(value: string | number) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(Number(value))
}

function formatDate(value?: string | null) {
  if (!value) return '-'
  return new Date(value).toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' })
}

async function loadSummary() {
  try {
    const response = await http.get<MemberPaymentAttendanceSummary>('/admin/member-reports/summary')
    summary.value = response.data
  } catch {
    window.showFitnezToast('Failed to load payment summary.', 'error')
  }
}

const page = ref(1)
const lastPage = ref(1)
const total = ref(0)
const perPage = ref(15)

async function loadPayments() {
  try {
    const response = await http.get<any>(`/admin/member-reports/payments?per_page=${perPage.value}&page=${page.value}`)
    payments.value = response.data.data || []
    page.value = response.data.current_page || 1
    lastPage.value = response.data.last_page || 1
    total.value = response.data.total || 0
  } catch {
    window.showFitnezToast('Failed to load payment data.', 'error')
  }
}

const visiblePages = computed(() => {
  const last = Number(lastPage.value)
  const current = Number(page.value)
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

function goToPage(p: number | string) {
  if (typeof p === 'string') return
  if (p < 1 || p > lastPage.value || p === page.value) return
  page.value = p
  loadPayments()
}

async function refreshData() {
  await Promise.all([loadSummary(), loadPayments()])
}

onMounted(() => {
  run(refreshData)
})
useAutoRefresh(refreshData, 8000)
</script>

<template>
  <WorkspaceLayout
    role="admin"
    sidebar-title="Admin"
    title="Payments"
    subtitle="Review all member payments."
    :sidebar-items="adminSidebarItems"
  >
    <div v-if="loading && !payments.length" :style="shimmerStyle">
      <SkeletonStatGrid :count="4" />
      <SkeletonTable :columns="7" :rows="8" style="margin-top: 1.25rem" />
    </div>

    <template v-else>
      <div class="grid gap-4 md:grid-cols-4" style="margin-bottom: 1.25rem;">
        <StatCard label="Total Payments" :value="summary.total_payments.toString()" />
        <StatCard label="Total Amount" :value="formatMoney(summary.total_payment_amount)" />
        <StatCard label="Paid" :value="summary.paid_payments.toString()" hint="Paid" />
        <StatCard label="Pending" :value="summary.pending_payments.toString()" hint="Pending" />
      </div>

      <FitnezCard>
        <div class="responsive-table">
          <table class="data-table">
            <thead>
              <tr>
                <th>Invoice</th>
                <th>Member</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="payment in payments" :key="payment.id">
                <td style="font-weight: 700;">{{ payment.invoice_number }}</td>
                <td>{{ payment.user?.full_name || '-' }}</td>
                <td>{{ payment.payment_type }}</td>
                <td>{{ formatMoney(payment.amount) }}</td>
                <td>{{ payment.payment_method || '-' }}</td>
                <td>
                  <span :class="['status', payment.payment_status === 'paid' ? 'status-success' : 'status-warning']">
                    {{ payment.payment_status || 'pending' }}
                  </span>
                </td>
                <td>{{ formatDate(payment.payment_date) }}</td>
              </tr>
              <tr v-if="!payments.length && !loading">
                <td colspan="7" class="empty-cell">No payment data available.</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="pager-bar">
          <p>Page {{ page }} of {{ lastPage }}</p>
          <div class="pagination">
            <button type="button" class="pagination-arrow" :disabled="page <= 1" @click="goToPage(page - 1)">‹</button>
            <button
              v-for="p in visiblePages"
              :key="p"
              :class="{ active: Number(p) === Number(page), disabled: p === '...' }"
              :disabled="p === '...'"
              type="button"
              @click="goToPage(p)"
            >
              {{ p }}
            </button>
            <button type="button" class="pagination-arrow" :disabled="page >= lastPage" @click="goToPage(page + 1)">›</button>
          </div>
        </div>
      </FitnezCard>
    </template>
  </WorkspaceLayout>
</template>

<style scoped>
.responsive-table {
  overflow-x: auto;
}

.data-table {
  border-collapse: collapse;
  width: 100%;
}

.data-table th,
.data-table td {
  border-bottom: 1px solid var(--color-border);
  padding: 0.75rem;
  text-align: left;
  white-space: nowrap;
}

.data-table th {
  color: var(--color-muted);
  font-size: 0.8rem;
  font-weight: 700;
}

.empty-cell {
  color: var(--color-muted);
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
</style>
