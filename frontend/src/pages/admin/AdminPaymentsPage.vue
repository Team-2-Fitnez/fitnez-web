<script setup lang="ts">
import { onMounted, ref } from 'vue'
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

async function loadPayments() {
  try {
    const response = await http.get<{ data: MemberReportPayment[] }>('/admin/member-reports/payments?per_page=50')
    payments.value = response.data.data || []
  } catch {
    window.showFitnezToast('Failed to load payment data.', 'error')
  }
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
</style>
