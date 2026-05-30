<script setup lang="ts">
import { onMounted, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import StatCard from '../../components/ui/StatCard.vue'
import { http } from '../../api/http'

const payments = ref<any[]>([])
const loading = ref(false)
const submitting = ref(false)
const summary = ref({ total_payments: 0, total_amount: 0, paid_count: 0, pending_count: 0 })
const demoAmount = ref(100000)

function formatMoney(val: string | number) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Number(val))
}

function formatDate(val?: string | null) {
  if (!val) return '-'
  return new Date(val).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

async function loadSummary() {
  try {
    const res = await http.get<{ total_payments: number; total_amount: number; paid_count: number; pending_count: number }>('/member/payments/summary')
    summary.value = res.data
  } catch {}
}

async function loadPayments() {
  loading.value = true
  try {
    const res = await http.get<{ data: any[] }>('/member/payments?per_page=50')
    payments.value = res.data.data || []
  } catch {
    window.showFitnezToast('Gagal memuat riwayat pembayaran.', 'error')
  } finally {
    loading.value = false
  }
}

async function createDemoPayment() {
  submitting.value = true
  try {
    await http.post('/member/payments/simulate-create', { amount: demoAmount.value })
    window.showFitnezToast('Tagihan demo berhasil dibuat!', 'success')
    await Promise.all([loadSummary(), loadPayments()])
  } catch {
    window.showFitnezToast('Gagal membuat tagihan demo.', 'error')
  } finally {
    submitting.value = false
  }
}

async function payNow(paymentId: number) {
  submitting.value = true
  try {
    const res = await http.post('/member/payments/simulate-pay', { payment_id: paymentId })
    window.showFitnezToast(res.message || 'Pembayaran demo berhasil!', 'success')
    await Promise.all([loadSummary(), loadPayments()])
  } catch {
    window.showFitnezToast('Gagal memproses pembayaran.', 'error')
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadSummary()
  loadPayments()
})
</script>

<template>
  <WorkspaceLayout
    role="member"
    sidebar-title="Member"
    title="My Payments"
    subtitle="Riwayat pembayaran Anda."
    :sidebar-items="memberSidebarItems"
  >
    <div class="grid gap-4 md:grid-cols-4" style="margin-bottom: 1.25rem;">
      <StatCard label="Total Pembayaran" :value="summary.total_payments.toString()" />
      <StatCard label="Total Nominal" :value="formatMoney(summary.total_amount)" />
      <StatCard label="Lunas" :value="summary.paid_count.toString()" hint="Paid" />
      <StatCard label="Pending" :value="summary.pending_count.toString()" hint="Menunggu" />
    </div>

    <div class="card mb-6">
      <h3 class="title-md mb-4">🧪 Demo Payment Gateway (Simulasi)</h3>
      <div class="flex flex-wrap items-end gap-4">
        <div>
          <label class="form-label">Jumlah (Rp)</label>
          <input type="number" v-model.number="demoAmount" class="form-input" min="1000" />
        </div>
        <button type="button" class="button button-primary" :disabled="submitting" @click="createDemoPayment">
          {{ submitting ? 'Processing...' : '💰 Buat Tagihan Demo' }}
        </button>
      </div>
      <p class="text-xs text-muted mt-2">Klik untuk membuat tagihan palsu, lalu klik "Bayar Demo" untuk simulasi pembayaran sukses.</p>
    </div>

    <FitnezCard>
      <div class="responsive-table">
        <table class="data-table">
          <thead>
            <tr>
              <th>Invoice</th>
              <th>Tipe</th>
              <th>Amount</th>
              <th>Metode</th>
              <th>Status</th>
              <th>Tanggal</th>
              <th>Aksi</th>
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
                  Bayar Demo
                </button>
                <span v-else class="text-xs text-muted">✓ Lunas</span>
              </td>
            </tr>
            <tr v-if="!payments.length && !loading">
              <td colspan="7" class="empty-cell">Belum ada riwayat pembayaran. Buat tagihan demo di atas.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </FitnezCard>
  </WorkspaceLayout>
</template>

<style scoped>
.responsive-table { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--color-border); white-space: nowrap; }
.data-table th { font-weight: 700; font-size: 0.8rem; color: var(--color-muted); }
.empty-cell { color: var(--color-muted); font-weight: 800; padding: 2rem; text-align: center; }
</style>
