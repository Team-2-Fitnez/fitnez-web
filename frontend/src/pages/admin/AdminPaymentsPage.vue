<script setup lang="ts">
import { onMounted, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import StatCard from '../../components/ui/StatCard.vue'
import { http } from '../../api/http'
import type { MemberReportPayment } from '../../types/memberPaymentAttendanceReport'
import SkeletonStatGrid from '../../components/ui/skeleton/SkeletonStatGrid.vue'
import SkeletonTable from '../../components/ui/SkeletonTable.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'

const payments = ref<MemberReportPayment[]>([])
const { loading, run, shimmerStyle } = useDeferredLoading()
const summary = ref({ total_payments: 0, total_payment_amount: 0, paid_payments: 0, pending_payments: 0, total_attendance: 0 })

function formatMoney(val: string | number) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Number(val))
import SkeletonList from '../../components/ui/SkeletonList.vue'
import { useMemberPaymentAttendanceReportStore } from '../../stores/memberPaymentAttendanceReportStore'

const store = useMemberPaymentAttendanceReportStore()

function formatMoney(val: string | number) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Number(val))
}

function formatDate(val?: string | null) {
  if (!val) return '-'
  return new Date(val).toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' })
}

async function loadSummary() {
  try {
    const res = await http.get<{
      total_payments: number
      total_payment_amount: number
      paid_payments: number
      pending_payments: number
      total_attendance: number
    }>('/admin/member-reports/summary')
    summary.value = res.data
  } catch {}
}

async function loadPayments() {
  try {
    const res = await http.get<{ data: MemberReportPayment[] }>('/admin/member-reports/payments?per_page=50')
    payments.value = res.data.data || []
  } catch {
    window.showFitnezToast('Failed to load payment data.', 'error')
  }
}

onMounted(() => {
  run(async () => {
    await loadSummary()
    await loadPayments()
  })
})
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
            <tr v-for="p in payments" :key="p.id">
              <td style="font-weight: 700;">{{ p.invoice_number }}</td>
              <td>{{ p.user?.full_name || '-' }}</td>
              <td>{{ p.payment_type }}</td>
              <td>{{ formatMoney(p.amount) }}</td>
              <td>{{ p.payment_method || '-' }}</td>
              <td><span :class="['status', p.payment_status === 'paid' ? 'status-success' : 'status-warning']">{{ p.payment_status || 'pending' }}</span></td>
              <td>{{ formatDate(p.payment_date) }}</td>
            </tr>
            <tr v-if="!payments.length && !loading">
              <td colspan="7" class="empty-cell">No payment data available.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </FitnezCard>
<<<<<<< HEAD
    </template>
=======
>>>>>>> origin/Trainer-Booking-Chat
  </WorkspaceLayout>
</template>

<style scoped>
.responsive-table { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--color-border); white-space: nowrap; }
.data-table th { font-weight: 700; font-size: 0.8rem; color: var(--color-muted); }
.empty-cell { color: var(--color-muted); font-weight: 800; padding: 2rem; text-align: center; }
</style>
