<script setup lang="ts">
import { onMounted, ref } from 'vue'
import WorkspaceLayout from '@/shared/components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '@/shared/components/layout/sidebarItems'
import FitnezCard from '@/shared/components/ui/FitnezCard.vue'
import StatCard from '@/shared/components/ui/StatCard.vue'
import { http } from '@/shared/api/http'
import SkeletonStatGrid from '@/shared/components/ui/skeleton/SkeletonStatGrid.vue'
import SkeletonTable from '@/shared/components/ui/SkeletonTable.vue'
import { useDeferredLoading } from '@/shared/composables/useDeferredLoading'
import { useAutoRefresh } from '@/shared/composables/useAutoRefresh'
import { useForm, useField } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import FormError from '@/shared/components/base/FormError.vue'

const payments = ref<any[]>([])
const { loading, run, shimmerStyle } = useDeferredLoading()
const submitting = ref(false)
const summary = ref({ total_payments: 0, total_amount: 0, paid_count: 0, pending_count: 0 })

const paymentSchema = toTypedSchema(
  z.object({
    amount: z.number({ invalid_type_error: 'Amount must be a number' }).min(1000, 'Minimum amount is Rp 1,000'),
  }),
)
const { handleSubmit, resetForm } = useForm({
  validationSchema: paymentSchema,
  initialValues: { amount: 100000 },
})
const { value: demoAmount, errorMessage: amountError } = useField<number>('amount')

type PaymentSummary = typeof summary.value
type PaymentList = { data: any[] }

function formatMoney(val: string | number) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Number(val))
}

function formatDate(val?: string | null) {
  if (!val) return '-'
  return new Date(val).toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' })
}

async function loadSummary() {
  try {
    const res = await http.get<PaymentSummary>('/member/payments/summary')
    summary.value = res.data
  } catch {
    window.showFitnezToast('Failed to load payment summary.', 'error')
  }
}

async function loadPayments() {
  try {
    const res = await http.get<PaymentList>('/member/payments?per_page=50')
    payments.value = res.data.data || []
  } catch {
    window.showFitnezToast('Failed to load payment history.', 'error')
  }
}

const createDemoPayment = handleSubmit(async (values) => {
  submitting.value = true
  try {
    await http.post('/member/payments/simulate-create', { amount: values.amount })
    window.showFitnezToast('Demo invoice successfully created!', 'success')
    resetForm({ values: { amount: 100000 } })
    await Promise.all([loadSummary(), loadPayments()])
  } catch {
    window.showFitnezToast('Failed to create demo invoice.', 'error')
  } finally {
    submitting.value = false
  }
})

async function payNow(paymentId: number) {
  submitting.value = true
  try {
    const res = await http.post('/member/payments/simulate-pay', { payment_id: paymentId })
    window.showFitnezToast(res.message || 'Demo payment successful!', 'success')
    await Promise.all([loadSummary(), loadPayments()])
  } catch {
    window.showFitnezToast('Failed to process payment.', 'error')
  } finally {
    submitting.value = false
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
    role="member"
    sidebar-title="Member"
    title="My Payments"
    subtitle="Your payment history."
    :sidebar-items="memberSidebarItems"
  >
    <div v-if="loading && !payments.length" :style="shimmerStyle">
      <SkeletonStatGrid :count="4" />
      <SkeletonTable :columns="5" :rows="8" style="margin-top: 1.25rem" />
    </div>
    <template v-else>
    <div class="grid gap-4 md:grid-cols-4" style="margin-bottom: 1.25rem;">
      <StatCard label="Total Payments" :value="summary.total_payments.toString()" />
      <StatCard label="Total Amount" :value="formatMoney(summary.total_amount)" />
      <StatCard label="Paid" :value="summary.paid_count.toString()" hint="Paid" />
      <StatCard label="Pending" :value="summary.pending_count.toString()" hint="Pending" />
    </div>

    <div class="card mb-6">
      <h3 class="title-md mb-4">🧪 Demo Payment Gateway (Simulation)</h3>
      <form @submit="createDemoPayment">
        <div class="flex flex-wrap items-end gap-4">
          <div>
            <label class="form-label">Amount (Rp)</label>
            <input type="number" v-model.number="demoAmount" class="form-input" min="1000" />
            <FormError :message="amountError" />
          </div>
          <button type="submit" class="button button-primary" :disabled="submitting">
            {{ submitting ? 'Processing...' : '💰 Create Demo Invoice' }}
          </button>
        </div>
      </form>
      <p class="text-xs text-muted mt-2">Click to create a mock invoice, then click "Pay Demo" to simulate a successful payment.</p>
    </div>

    <FitnezCard>
      <div class="responsive-table">
        <table class="data-table">
          <thead>
            <tr>
              <th>Invoice</th>
              <th>Type</th>
              <th>Amount</th>
              <th>Method</th>
              <th>Status</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in payments" :key="p.id">
              <td style="font-weight: 700;">{{ p.invoice_number }}</td>
              <td>{{ p.payment_type }}</td>
              <td>{{ formatMoney(p.amount) }}</td>
              <td>{{ p.payment_method || '-' }}</td>
              <td><span :class="['status', p.payment_status === 'paid' ? 'status-success' : 'status-warning']">{{ p.payment_status || 'pending' }}</span></td>
              <td>{{ formatDate(p.payment_date) }}</td>
              <td>
                <button
                  v-if="p.payment_status === 'pending'"
                  type="button"
                  class="button button-sm button-primary"
                  :disabled="submitting"
                  @click="payNow(p.id)"
                >
                  Pay Demo
                </button>
                <span v-else class="text-xs text-muted">✓ Paid</span>
              </td>
            </tr>
            <tr v-if="!payments.length && !loading">
              <td colspan="7" class="empty-cell">No payment history yet. Create a demo invoice above.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </FitnezCard>
    </template>
  </WorkspaceLayout>
</template>

<style scoped>
.responsive-table { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--color-border); white-space: nowrap; }
.data-table th { font-weight: 700; font-size: 0.8rem; color: var(--color-muted); }
.empty-cell { color: var(--color-muted); font-weight: 800; padding: 2rem; text-align: center; }
</style>
