<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import StatCard from '../../components/ui/StatCard.vue'
import SkeletonStatGrid from '../../components/ui/skeleton/SkeletonStatGrid.vue'
import SkeletonCard from '../../components/ui/SkeletonCard.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'
import { useAutoRefresh } from '../../composables/useAutoRefresh'
import { useAuthStore } from '../../stores/authStore'
import { http } from '../../api/http'

type AttendanceItem = {
  id: number
  check_in_time?: string | null
  check_out_time?: string | null
  attendance_type?: string | null
}

const history = ref<AttendanceItem[]>([])
const checkInStatus = ref<'none' | 'checked_in' | 'checked_out'>('none')
const actionLoading = ref(false)
const payments = ref({ total_payments: 0, total_amount: 0, paid_count: 0, pending_count: 0 })
const paymentsHistory = ref<any[]>([])
const classesTotal = ref(0)

const authStore = useAuthStore()
const { loading, run, shimmerStyle } = useDeferredLoading()

// Restrict actions: Check if role is strictly 'member'
const isMember = computed(() => authStore.user?.role === 'member')

function formatMoney(val: string | number) {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Number(val))
}

async function loadData() {
  const [historyRes, paymentSummaryRes, classesRes, paymentListRes] = await Promise.allSettled([
    http.get<{ data: AttendanceItem[] }>('/attendance/history?per_page=10'),
    http.get<typeof payments.value>('/member/payments/summary'),
    http.get<unknown[]>('/member/classes'),
    http.get<{ data: any[] }>('/member/payments?per_page=10'),
  ])

  if (historyRes.status === 'fulfilled') {
    history.value = historyRes.value.data.data || []
    const active = history.value.find(item => !item.check_out_time)
    checkInStatus.value = active ? 'checked_in' : 'checked_out'
  }
  
  if (paymentSummaryRes.status === 'fulfilled') {
    payments.value = paymentSummaryRes.value.data
  }
  
  if (classesRes.status === 'fulfilled') {
    classesTotal.value = classesRes.value.data?.length || 0
  }

  if (paymentListRes.status === 'fulfilled') {
    paymentsHistory.value = paymentListRes.value.data.data || []
  }
}

async function doCheckIn() {
  if (!isMember.value) return
  actionLoading.value = true
  try {
    const res = await http.post<{ id: number; check_in_time: string }>('/attendance/check-in', {})
    window.showFitnezToast('Check-in successful.', 'success')
    history.value.unshift({
      id: res.data.id,
      check_in_time: res.data.check_in_time,
      check_out_time: null,
      attendance_type: 'member_checkin',
    })
    checkInStatus.value = 'checked_in'
  } catch (error: any) {
    window.showFitnezToast(error?.message || 'Failed to check in.', 'error')
  } finally {
    actionLoading.value = false
  }
}

async function doCheckOut() {
  if (!isMember.value) return
  actionLoading.value = true
  try {
    await http.post('/attendance/check-out', {})
    window.showFitnezToast('Check-out successful.', 'success')
    const active = history.value.find((item: any) => !item.check_out_time)
    if (active) active.check_out_time = new Date().toISOString()
    checkInStatus.value = 'checked_out'
  } catch (error: any) {
    window.showFitnezToast(error?.message || 'Failed to check out.', 'error')
  } finally {
    actionLoading.value = false
  }
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

function formatTime(value?: string | null) {
  if (!value) return '-'
  return new Date(value).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

function formatDate(val?: string | null) {
  if (!val) return '-'
  return new Date(val).toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' })
}

onMounted(() => run(loadData))
useAutoRefresh(loadData, 8000)
</script>

<template>
  <WorkspaceLayout
    role="member"
    sidebar-title="Member"
    title="Attendance & Dashboard"
    subtitle="Perform check-in/out and review your operational statistics & payment history."
    :sidebar-items="memberSidebarItems"
  >
    <div class="attendance-dashboard-page space-y-6" :style="shimmerStyle">
      <template v-if="loading">
        <SkeletonStatGrid :count="4" />
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2"><SkeletonCard heading :lines="4" wide /></div>
          <div class="lg:col-span-1"><SkeletonCard :lines="5" /></div>
        </div>
      </template>

      <template v-else>
        <!-- Top Section: Check-In Hero Card -->
        <FitnezCard>
          <div class="attendance-hero">
            <div>
              <p class="eyebrow">Today's Attendance</p>
              <h2 class="title-md">
                {{ checkInStatus === 'checked_in' ? 'You are checked in' : checkInStatus === 'checked_out' ? 'Ready to check in' : 'Checked Out' }}
              </h2>
            </div>
            
            <!-- Restrict Check-in & Check-out actions to members only -->
            <div class="action-row" v-if="isMember">
              <button
                v-if="checkInStatus !== 'checked_in'"
                class="button button-primary"
                type="button"
                :disabled="actionLoading"
                @click="doCheckIn"
              >
                {{ actionLoading ? 'Processing...' : 'Check In' }}
              </button>
              <button
                v-if="checkInStatus === 'checked_in'"
                class="button button-danger"
                type="button"
                :disabled="actionLoading"
                @click="doCheckOut"
              >
                {{ actionLoading ? 'Processing...' : 'Check Out' }}
              </button>
            </div>
            <div v-else class="p-3 bg-amber-50 text-amber-800 rounded-xl border border-amber-200 text-xs font-semibold">
              ⚠️ Only registered member role can check-in & check-out.
            </div>
          </div>
        </FitnezCard>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <StatCard label="Total Payments" :value="payments.total_payments" :hint="`Amount Rp ${payments.total_amount.toLocaleString('en-US')}`" />
          <StatCard label="Successful Payments" :value="payments.paid_count" hint="successful transactions" />
          <StatCard label="Active Classes" :value="classesTotal" hint="available to join" />
          <StatCard label="Total Check-ins" :value="history.length" hint="monitored logs" />
        </div>

        <!-- Main Content Area: Separate Tables for Attendance and Payment histories -->
        <div class="space-y-6">
          <!-- Attendance History Card -->
          <FitnezCard>
            <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-3">
              <div>
                <p class="eyebrow text-blue-600">Attendance Log</p>
                <h3 class="text-lg font-extrabold text-gray-900 mt-0.5">Check-In & Check-Out History</h3>
              </div>
              <span class="text-xs text-gray-400 font-bold bg-gray-50 px-2.5 py-1 rounded-md">{{ history.length }} logs</span>
            </div>
            <div class="responsive-table">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Type</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in history" :key="item.id">
                    <td>{{ formatDateTime(item.check_in_time) }}</td>
                    <td>{{ formatTime(item.check_in_time) }}</td>
                    <td>{{ formatTime(item.check_out_time) }}</td>
                    <td>{{ item.attendance_type || '-' }}</td>
                    <td>
                      <span :class="['status', item.check_out_time ? 'status-success' : 'status-warning']">
                        {{ item.check_out_time ? 'Completed' : 'Active' }}
                      </span>
                    </td>
                  </tr>
                  <tr v-if="!history.length">
                    <td colspan="5" class="empty-cell">No attendance history yet.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </FitnezCard>

          <!-- Payment History Card -->
          <FitnezCard>
            <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-3">
              <div>
                <p class="eyebrow text-emerald-600">Transactions Log</p>
                <h3 class="text-lg font-extrabold text-gray-900 mt-0.5">Payment & Invoice History</h3>
              </div>
              <RouterLink to="/member/payments" class="text-xs font-bold text-blue-600 hover:text-blue-800 transition-colors">
                View All
              </RouterLink>
            </div>
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
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="p in paymentsHistory.slice(0, 5)" :key="p.id">
                    <td class="font-bold text-gray-900">{{ p.invoice_number }}</td>
                    <td>{{ p.payment_type }}</td>
                    <td>{{ formatMoney(p.amount) }}</td>
                    <td>{{ p.payment_method || '-' }}</td>
                    <td>
                      <span :class="['status', p.payment_status === 'paid' ? 'status-success' : 'status-warning']">
                        {{ p.payment_status || 'pending' }}
                      </span>
                    </td>
                    <td>{{ formatDate(p.payment_date) }}</td>
                  </tr>
                  <tr v-if="!paymentsHistory.length">
                    <td colspan="6" class="empty-cell">No payment history yet.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </FitnezCard>
        </div>
      </template>
    </div>
  </WorkspaceLayout>
</template>

<style scoped>
.attendance-hero {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  justify-content: space-between;
}

.action-row {
  display: flex;
  gap: 0.75rem;
}

.responsive-table {
  margin-top: 1rem;
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

.button-danger {
  background: #dc2626;
  border: none;
  color: #fff;
}

.button-danger:hover {
  background: #b91c1c;
}

.action-list {
  display: grid;
  gap: 0.75rem;
  margin-top: 1rem;
}

.action-list a {
  background: #f8fafc;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 0.9rem;
  color: #0f172a;
  font-weight: 900;
  padding: 0.9rem 1rem;
  text-decoration: none;
  transition: all 0.2s ease;
}

.action-list a:hover {
  background: #f1f5f9;
  border-color: rgba(15, 23, 42, 0.15);
}
</style>
