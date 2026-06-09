<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import { useProspectiveMemberStore } from '../../stores/prospectiveMemberStore'
import { useBookingStore } from '../../stores/bookingStore'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import FitnezButton from '../../components/ui/FitnezButton.vue'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import SkeletonTable from '../../components/ui/SkeletonTable.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'
import { useAutoRefresh } from '../../composables/useAutoRefresh'

const prospectiveStore = useProspectiveMemberStore()
const bookingStore = useBookingStore()
const { loading, run, shimmerStyle } = useDeferredLoading()

const activeTab = ref<'registration' | 'booking'>('registration')

// Registration reject modal
const showRejectModal = ref(false)
const selectedRegistrationId = ref<number | null>(null)
const rejectionReasonOption = ref('The transfer proof is invalid or unreadable (blurry)')
const customRejectionReason = ref('')
const rejectionReasonOptions = [
  'The transfer proof is invalid or unreadable (blurry)',
  'The transferred amount does not match the package price',
  'The transfer was sent to the wrong account or destination',
  'The transfer proof is fake or has already been used',
  'Other'
]

// Booking reject modal
const showBookingRejectModal = ref(false)
const selectedBookingId = ref<number | null>(null)
const bookingRejectReason = ref('')

onMounted(() => run(async () => {
  await prospectiveStore.load()
  await bookingStore.loadPendingPayments()
}))
useAutoRefresh(() => {
  if (activeTab.value === 'registration') prospectiveStore.load()
  else bookingStore.loadPendingPayments()
}, 8000)

const visiblePages = computed(() => {
  const last = Number(store.lastPage)
  const current = Number(store.page)
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
  if (p < 1 || p > store.lastPage || p === store.page) return
  store.page = p
  store.load()
}

function openRejectModal(id: number) {
  selectedRegistrationId.value = id
  rejectionReasonOption.value = rejectionReasonOptions[0]
  customRejectionReason.value = ''
  showRejectModal.value = true
}

function closeRejectModal() {
  showRejectModal.value = false
  selectedRegistrationId.value = null
}

function submitRejection() {
  if (!selectedRegistrationId.value) return
  const finalReason = rejectionReasonOption.value === 'Other'
    ? customRejectionReason.value.trim()
    : rejectionReasonOption.value

  if (!finalReason) {
    alert('Please enter or select a rejection reason.')
    return
  }

  prospectiveStore.reject(selectedRegistrationId.value, finalReason)
  closeRejectModal()
}

function openBookingRejectModal(id: number) {
  selectedBookingId.value = id
  bookingRejectReason.value = 'Bukti transfer tidak valid. Silakan upload ulang.'
  showBookingRejectModal.value = true
}

function closeBookingRejectModal() {
  showBookingRejectModal.value = false
  selectedBookingId.value = null
}

async function submitBookingRejection() {
  if (!selectedBookingId.value || !bookingRejectReason.value.trim()) return
  try {
    await bookingStore.rejectPayment(selectedBookingId.value, bookingRejectReason.value)
    window.showFitnezToast('Booking payment rejected.', 'success')
  } catch (e: any) {
    window.showFitnezToast(e?.message || 'Failed to reject.', 'error')
  }
  closeBookingRejectModal()
}

async function confirmBooking(id: number) {
  try {
    await bookingStore.confirmPayment(id)
    window.showFitnezToast('Payment confirmed. Chat is now open.', 'success')
  } catch (e: any) {
    window.showFitnezToast(e?.message || 'Failed to confirm.', 'error')
  }
}

function formatPrice(n: number | string | null | undefined) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(Number(n || 0))
}
</script>

<template>
  <WorkspaceLayout role="admin" sidebar-title="Admin" title="Payment Review" subtitle="Approve or reject payment proofs from members." :sidebar-items="adminSidebarItems">

    <!-- Tabs -->
    <div style="display: flex; gap: 0.25rem; margin-bottom: 1rem; background: #f1f5f9; border-radius: 0.75rem; padding: 0.25rem; width: fit-content;">
      <button
        :class="['tab-btn', activeTab === 'registration' && 'tab-active']"
        @click="activeTab = 'registration'"
      >
        Registration
      </button>
      <button
        :class="['tab-btn', activeTab === 'booking' && 'tab-active']"
        @click="activeTab = 'booking'; bookingStore.loadPendingPayments()"
      >
        Booking
      </button>
    </div>

    <!-- Registration Tab -->
    <template v-if="activeTab === 'registration'">
      <div v-if="loading && !prospectiveStore.items.length" :style="shimmerStyle">
        <SkeletonTable :columns="6" :rows="8" />
      </div>
      <FitnezCard v-else>
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem; align-items: center; margin-bottom: 1.25rem;">
          <div>
            <h2 class="title-md">Review queue</h2>
            <p class="text-muted">Verify amount, payment method, and proof screenshot before approval.</p>
          </div>

          <select v-model="prospectiveStore.status" class="form-input" style="width: auto; min-width: 220px;" @change="prospectiveStore.load()">
            <option value="awaiting_admin_review">Awaiting Review</option>
            <option value="awaiting_payment">Awaiting Payment</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="">All Statuses</option>
          </select>
        </div>

        <div v-if="!prospectiveStore.items.length && prospectiveStore.status === 'awaiting_admin_review'" class="p-8 text-center bg-blue-50 rounded-xl mb-6">
          <p class="text-blue-600 font-medium">No new payments awaiting review.</p>
          <p class="text-xs text-blue-400 mt-1">Use the filters above to see registrations that are approved or awaiting payment.</p>
        </div>

        <div class="data-table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Code</th>
                <th>Applicant</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Proof</th>
                <th style="text-align: right;">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="row in prospectiveStore.items" :key="row.id">
                <td style="max-width: 240px; word-break: break-word; font-weight: 900;">{{ row.registration_code }}</td>
                <td>
                  <p style="font-weight: 900;">{{ row.full_name }}</p>
                  <p class="text-muted" style="font-size: 0.75rem;">{{ row.email }}</p>
                </td>
                <td style="font-weight: 800;">Rp {{ Number(row.amount).toLocaleString('en-US') }}</td>
                <td><StatusBadge :status="row.status" /></td>
                <td>
                  <a v-if="row.payment_proof_url" :href="row.payment_proof_url" target="_blank" style="color: var(--color-blue-dark); font-weight: 900; text-decoration: underline;">Open Proof</a>
                  <span v-else class="text-muted">No proof</span>
                </td>
                <td>
                  <div style="display: flex; justify-content: end; gap: 0.5rem;">
                    <FitnezButton size="sm" variant="secondary" @click="prospectiveStore.approve(row.id)">Approve</FitnezButton>
                    <FitnezButton size="sm" variant="danger" @click="openRejectModal(row.id)">Reject</FitnezButton>
                  </div>
                </td>
              </tr>

              <tr v-if="!prospectiveStore.items.length">
                <td colspan="6" style="padding-block: 4rem; text-align: center; font-weight: 800; color: var(--color-muted);">
                  <div class="text-4xl mb-4">📂</div>
                  No registration data found for this filter.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </FitnezCard>
    </template>

    <!-- Booking Tab -->
    <template v-else-if="activeTab === 'booking'">
      <div v-if="loading && !bookingStore.pendingBookings.length" :style="shimmerStyle">
        <SkeletonTable :columns="6" :rows="8" />
      </div>
      <FitnezCard v-else>
        <div style="margin-bottom: 1.25rem;">
          <h2 class="title-md">Booking Payments</h2>
          <p class="text-muted">Verify transfer proof and amount before confirming the booking.</p>
        </div>

        <div v-if="!bookingStore.pendingBookings.length" class="p-8 text-center bg-blue-50 rounded-xl mb-6">
          <p class="text-blue-600 font-medium">No pending booking payments.</p>
          <p class="text-xs text-blue-400 mt-1">All booking payments have been reviewed.</p>
        </div>

        <div class="data-table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Member</th>
                <th>Trainer</th>
                <th>Amount</th>
                <th>Period</th>
                <th>Proof</th>
                <th style="text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="b in bookingStore.pendingBookings" :key="b.id">
                <td style="font-weight: 900;">#{{ b.id }}</td>
                <td>
                  <p style="font-weight: 900;">{{ b.member?.full_name ?? 'N/A' }}</p>
                </td>
                <td>
                  <p style="font-weight: 900;">{{ b.trainer?.full_name ?? 'N/A' }}</p>
                  <p class="text-muted" style="font-size: 0.75rem;">{{ b.sessions_per_week }}×/week</p>
                </td>
                <td style="font-weight: 800;">{{ formatPrice(b.total_member_price) }}</td>
                <td style="font-size: 0.8rem;">
                  {{ b.start_date }} – {{ b.end_date }}<br>
                  <span class="text-muted">{{ b.total_sessions }} sessions</span>
                </td>
                <td>
                  <a v-if="b.payment_proof_url" :href="b.payment_proof_url" target="_blank" style="color: var(--color-blue-dark); font-weight: 900; text-decoration: underline; font-size: 0.85rem;">Open Proof</a>
                  <span v-else class="text-muted">No proof</span>
                </td>
                <td>
                  <div style="display: flex; justify-content: end; gap: 0.5rem;">
                    <FitnezButton size="sm" variant="secondary" @click="confirmBooking(b.id)">Confirm</FitnezButton>
                    <FitnezButton size="sm" variant="danger" @click="openBookingRejectModal(b.id)">Reject</FitnezButton>
                  </div>
                </td>
              </tr>

              <tr v-if="!bookingStore.pendingBookings.length">
                <td colspan="7" style="padding-block: 4rem; text-align: center; font-weight: 800; color: var(--color-muted);">
                  No pending booking payments.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </FitnezCard>
    </template>

            <tr v-if="!store.items.length">
              <td colspan="6" style="padding-block: 4rem; text-align: center; font-weight: 800; color: var(--color-muted);">
                <div class="text-4xl mb-4">📂</div>
                No registration data found for this filter.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="pager-bar">
        <p>Page {{ store.page }} of {{ store.lastPage }}</p>
        <div class="pagination">
          <button type="button" class="pagination-arrow" :disabled="store.page <= 1" @click="goToPage(store.page - 1)">‹</button>
          <button
            v-for="p in visiblePages"
            :key="p"
            :class="{ active: Number(p) === Number(store.page), disabled: p === '...' }"
            :disabled="p === '...'"
            type="button"
            @click="goToPage(p)"
          >
            {{ p }}
          </button>
          <button type="button" class="pagination-arrow" :disabled="store.page >= store.lastPage" @click="goToPage(store.page + 1)">›</button>
        </div>
      </div>
    </FitnezCard>

    <!-- Rejection Modal -->
    <div v-if="showRejectModal && selectedRegistrationId" class="modal-overlay" @click.self="closeRejectModal">
      <div class="modal-card">
        <div class="modal-header">
          <div class="warning-icon-wrapper">
            <span class="material-symbols-outlined">gavel</span>
          </div>
          <div>
            <h3 class="title-md" style="margin: 0; font-size: 1.25rem;">Reject Registrasi</h3>
            <p class="text-muted text-xs" style="margin-top: 0.25rem; font-size: 0.75rem;">Choose the rejection reason for the manual payment.</p>
          </div>
        </div>

        <div class="modal-body">
          <div class="form-field">
            <label class="form-label">Rejection Reason</label>
            <select v-model="rejectionReasonOption" class="form-input custom-select">
              <option v-for="opt in rejectionReasonOptions" :key="opt" :value="opt">{{ opt }}</option>
            </select>
          </div>

          <div v-if="rejectionReasonOption === 'Other'" class="form-field animate-slide-down">
            <label class="form-label">Write Another Reason</label>
            <textarea v-model="customRejectionReason" class="form-input custom-textarea" placeholder="Write a specific rejection reason..." rows="3" required />
          </div>
        </div>

        <div class="form-actions">
          <button class="button button-ghost" type="button" @click="closeRejectModal">Cancel</button>
          <button class="button button-danger" type="button" @click="submitRejection" :disabled="rejectionReasonOption === 'Other' && !customRejectionReason.trim()">
            Reject Registrasi
          </button>
        </div>
      </div>
    </div>

    <!-- Booking Rejection Modal -->
    <div v-if="showBookingRejectModal && selectedBookingId" class="modal-overlay" @click.self="closeBookingRejectModal">
      <div class="modal-card">
        <div class="modal-header">
          <div class="warning-icon-wrapper">
            <span class="material-symbols-outlined">gavel</span>
          </div>
          <div>
            <h3 class="title-md" style="margin: 0; font-size: 1.25rem;">Reject Booking Payment</h3>
            <p class="text-muted text-xs" style="margin-top: 0.25rem; font-size: 0.75rem;">Provide a reason for rejection. The member will be notified.</p>
          </div>
        </div>

        <div class="modal-body">
          <div class="form-field">
            <label class="form-label">Rejection Reason</label>
            <textarea v-model="bookingRejectReason" class="form-input" rows="3" placeholder="Why is this payment rejected?" />
          </div>
        </div>

        <div class="form-actions">
          <button class="button button-ghost" type="button" @click="closeBookingRejectModal">Cancel</button>
          <button class="button button-danger" type="button" @click="submitBookingRejection" :disabled="!bookingRejectReason.trim()">
            Reject Booking
          </button>
        </div>
      </div>
    </div>
  </WorkspaceLayout>
</template>

<style scoped>
.tab-btn {
  padding: 0.5rem 1.25rem;
  border-radius: 0.6rem;
  border: none;
  background: transparent;
  font-weight: 700;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.15s ease;
  color: #64748b;
}

.tab-btn:hover {
  color: var(--color-blue-dark);
}

.tab-btn.tab-active {
  background: white;
  color: var(--color-blue-dark);
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(11, 28, 48, 0.45);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  animation: fadeIn 0.25s ease-out;
}

.modal-card {
  background: rgba(255, 255, 255, 0.98);
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 1.25rem;
  padding: 2rem;
  width: 100%;
  max-width: 480px;
  box-shadow: 0 24px 60px rgba(11, 28, 48, 0.18);
  animation: scaleUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal-header {
  display: flex;
  gap: 1rem;
  align-items: center;
  margin-bottom: 1.5rem;
  border-bottom: 1px solid rgba(0,0,0,0.06);
  padding-bottom: 1rem;
}

.warning-icon-wrapper {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 0.75rem;
  display: grid;
  place-items: center;
}

.modal-body {
  margin-bottom: 1.5rem;
}

.custom-select {
  appearance: none;
  background-image: url("data:image/svg+xml;utf8,<svg fill='%23213C5E' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/><path d='M0 0h24v24H0z' fill='none'/></svg>");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  background-size: 1.25rem;
  padding-right: 2.5rem !important;
}

.form-field {
  margin-bottom: 1.25rem;
}

.form-label {
  display: block;
  font-weight: 800;
  margin-bottom: 0.5rem;
  font-size: 0.85rem;
  color: var(--color-blue-dark);
}

.form-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid rgba(0, 0, 0, 0.15);
  border-radius: 0.75rem;
  font-size: 0.9rem;
  font-weight: 600;
  background-color: #f8fafc;
  color: var(--color-black);
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.form-input:focus {
  border-color: var(--color-blue);
  background-color: white;
  box-shadow: 0 0 0 4px rgba(183, 211, 244, 0.5);
  outline: none;
}

.form-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}

.button {
  align-items: center;
  border-radius: 0.75rem;
  display: inline-flex;
  font-size: 0.875rem;
  font-weight: 900;
  justify-content: center;
  min-height: 2.5rem;
  padding: 0.6rem 1.25rem;
  transition: 160ms ease;
  border: none;
  cursor: pointer;
}

.button-ghost {
  background: white;
  border: 1px solid rgba(0, 0, 0, 0.15);
  color: var(--color-muted);
}

.button-ghost:hover {
  background: #f1f5f9;
}

.button-danger {
  background: #ef4444;
  color: white;
}

.button-danger:hover {
  background: #dc2626;
}

.button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes scaleUp {
  from { transform: scale(0.95); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

.animate-slide-down {
  animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
  from { transform: translateY(-10px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.pager-bar {
  align-items: center;
  border-top: 1px solid rgba(0, 0, 0, 0.10);
  display: flex;
  justify-content: space-between;
  padding: 0.9rem 1.25rem;
  margin-top: 1rem;
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
