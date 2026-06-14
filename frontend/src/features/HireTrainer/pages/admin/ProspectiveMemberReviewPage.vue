<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import WorkspaceLayout from '@/shared/components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '@/shared/components/layout/sidebarItems'
import { useProspectiveMemberStore } from '@/features/Registration/stores/prospectiveMemberStore'
import { useBookingStore } from '@/features/HireTrainer/stores/bookingStore'
import { memberMembershipApi } from '@/features/Payments/api/memberMembershipApi'
import StatusBadge from '@/shared/components/ui/StatusBadge.vue'
import FitnezButton from '@/shared/components/ui/FitnezButton.vue'
import FitnezCard from '@/shared/components/ui/FitnezCard.vue'
import SkeletonTable from '@/shared/components/ui/SkeletonTable.vue'
import { useDeferredLoading } from '@/shared/composables/useDeferredLoading'
import { useAutoRefresh } from '@/shared/composables/useAutoRefresh'

const store = useProspectiveMemberStore()
const bookingStore = useBookingStore()
const { loading, run, shimmerStyle } = useDeferredLoading()

const activeTab = ref<'registrations' | 'bookings' | 'renewals'>('registrations')

const pendingRenewalsList = ref<any[]>([])
const renewalsLoading = ref(false)
const renewalsPage = ref(1)
const renewalsLastPage = ref(1)

const showRejectModal = ref(false)
const selectedRegistrationId = ref<number | null>(null)
const selectedType = ref<'registration' | 'booking' | 'renewal'>('registration')
const rejectionReasonOption = ref('The transfer proof is invalid or unreadable (blurry)')
const customRejectionReason = ref('')
const rejectionReasonOptions = [
  'The transfer proof is invalid or unreadable (blurry)',
  'The transferred amount does not match the package price',
  'The transfer was sent to the wrong account or destination',
  'The transfer proof is fake or has already been used',
  'Other'
]

async function loadPendingRenewals() {
  renewalsLoading.value = true
  try {
    const res = await memberMembershipApi.pendingRenewals(renewalsPage.value)
    pendingRenewalsList.value = res.data.data
    renewalsPage.value = res.data.current_page
    renewalsLastPage.value = res.data.last_page
  } catch (e) {
    console.error(e)
  } finally {
    renewalsLoading.value = false
  }
}

async function loadData() {
  if (activeTab.value === 'registrations') {
    await store.load()
  } else if (activeTab.value === 'bookings') {
    await bookingStore.loadPendingPayments()
  } else if (activeTab.value === 'renewals') {
    await loadPendingRenewals()
  }
}

onMounted(() => run(() => loadData()))
useAutoRefresh(() => loadData(), 8000)

async function switchTab(tab: 'registrations' | 'bookings' | 'renewals') {
  activeTab.value = tab
  store.page = 1
  bookingStore.page = 1
  renewalsPage.value = 1
  await run(() => loadData())
}

const visiblePages = computed(() => {
  let last = 1
  let current = 1
  if (activeTab.value === 'registrations') {
    last = Number(store.lastPage)
    current = Number(store.page)
  } else if (activeTab.value === 'bookings') {
    last = Number(bookingStore.lastPage)
    current = Number(bookingStore.page)
  } else if (activeTab.value === 'renewals') {
    last = Number(renewalsLastPage.value)
    current = Number(renewalsPage.value)
  }
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
  if (activeTab.value === 'registrations') {
    if (p < 1 || p > store.lastPage || p === store.page) return
    store.page = p
    store.load()
  } else if (activeTab.value === 'bookings') {
    if (p < 1 || p > bookingStore.lastPage || p === bookingStore.page) return
    bookingStore.page = p
    bookingStore.loadPendingPayments()
  } else if (activeTab.value === 'renewals') {
    if (p < 1 || p > renewalsLastPage.value || p === renewalsPage.value) return
    renewalsPage.value = p
    loadPendingRenewals()
  }
}

function openRejectModal(id: number, type: 'registration' | 'booking' | 'renewal') {
  selectedRegistrationId.value = id
  selectedType.value = type
  rejectionReasonOption.value = rejectionReasonOptions[0]
  customRejectionReason.value = ''
  showRejectModal.value = true
}

function closeRejectModal() {
  showRejectModal.value = false
  selectedRegistrationId.value = null
}

async function submitRejection() {
  if (!selectedRegistrationId.value) return
  const finalReason = rejectionReasonOption.value === 'Other'
    ? customRejectionReason.value.trim()
    : rejectionReasonOption.value

  if (!finalReason) {
    alert('Please enter or select a rejection reason.')
    return
  }

  try {
    if (selectedType.value === 'registration') {
      await store.reject(selectedRegistrationId.value, finalReason)
    } else if (selectedType.value === 'booking') {
      await bookingStore.rejectPayment(selectedRegistrationId.value, finalReason)
    } else if (selectedType.value === 'renewal') {
      await memberMembershipApi.rejectRenewal(selectedRegistrationId.value, finalReason)
      await loadPendingRenewals()
    }
  } catch (e: any) {
    alert(e.response?.data?.message || e.message || 'Failed to reject')
  }
  closeRejectModal()
}

async function approveRenewal(id: number) {
  try {
    await memberMembershipApi.confirmRenewal(id)
    await loadPendingRenewals()
  } catch (e: any) {
    alert(e.response?.data?.message || e.message || 'Failed to approve renewal')
  }
}
</script>

<template>
  <WorkspaceLayout role="admin" sidebar-title="Admin" title="Payment Review" subtitle="Approve or reject payments." :sidebar-items="adminSidebarItems">
    <div v-if="loading && activeTab === 'registrations' && !store.items.length" :style="shimmerStyle">
      <SkeletonTable :columns="6" :rows="8" />
    </div>
    <div v-else-if="loading && activeTab === 'bookings' && !bookingStore.pendingBookings.length" :style="shimmerStyle">
      <SkeletonTable :columns="8" :rows="8" />
    </div>
    <div v-else-if="loading && activeTab === 'renewals' && !pendingRenewalsList.length" :style="shimmerStyle">
      <SkeletonTable :columns="7" :rows="8" />
    </div>
    <FitnezCard v-else>
      <!-- Tab Toggles -->
      <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(0,0,0,0.06); padding-bottom: 0.75rem;">
        <button
          type="button"
          :class="['button', activeTab === 'registrations' ? 'button-primary' : 'button-ghost']"
          style="min-height: 2.25rem; padding: 0.4rem 1rem;"
          @click="switchTab('registrations')"
        >
          New Registrations
        </button>
        <button
          type="button"
          :class="['button', activeTab === 'bookings' ? 'button-primary' : 'button-ghost']"
          style="min-height: 2.25rem; padding: 0.4rem 1rem;"
          @click="switchTab('bookings')"
        >
          Trainer Bookings
        </button>
        <button
          type="button"
          :class="['button', activeTab === 'renewals' ? 'button-primary' : 'button-ghost']"
          style="min-height: 2.25rem; padding: 0.4rem 1rem;"
          @click="switchTab('renewals')"
        >
          Membership Renewals
        </button>
      </div>

      <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem; align-items: center; margin-bottom: 1.25rem;">
        <div>
          <h2 class="title-md">
            {{ activeTab === 'registrations' ? 'Registration queue' : activeTab === 'bookings' ? 'Trainer Booking queue' : 'Membership Renewal queue' }}
          </h2>
          <p class="text-muted">Verify amount, payment method, and proof screenshot before approval.</p>
        </div>

        <select v-if="activeTab === 'registrations'" v-model="store.status" class="form-input" style="width: auto; min-width: 220px;" @change="store.load()">
          <option value="awaiting_admin_review">Awaiting Review</option>
          <option value="awaiting_payment">Awaiting Payment</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
          <option value="">All Statuses</option>
        </select>
      </div>

      <!-- Table for Registrations -->
      <div v-if="activeTab === 'registrations'" class="data-table-wrapper">
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
            <tr v-for="row in store.items" :key="row.id">
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
                  <FitnezButton size="sm" variant="secondary" @click="store.approve(row.id)">Approve</FitnezButton>
                  <FitnezButton size="sm" variant="danger" @click="openRejectModal(row.id, 'registration')">Reject</FitnezButton>
                </div>
              </td>
            </tr>

            <tr v-if="!store.items.length">
              <td colspan="6" style="padding-block: 4rem; text-align: center; font-weight: 800; color: var(--color-muted);">
                <div class="text-4xl mb-4">📂</div>
                No registration data found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Table for Trainer Bookings -->
      <div v-else-if="activeTab === 'bookings'" class="data-table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Member</th>
              <th>Trainer</th>
              <th>Amount</th>
              <th>Sessions</th>
              <th>Status</th>
              <th>Proof</th>
              <th style="text-align: right;">Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="row in bookingStore.pendingBookings" :key="row.id">
              <td style="font-weight: 950;">#{{ row.id }}</td>
              <td>
                <p style="font-weight: 900;">{{ row.member?.full_name || 'Unknown' }}</p>
                <p class="text-muted" style="font-size: 0.75rem;">{{ row.member?.email }}</p>
              </td>
              <td style="font-weight: 800;">{{ row.trainer?.full_name || 'Unknown' }}</td>
              <td style="font-weight: 800;">Rp {{ Number(row.total_member_price).toLocaleString('en-US') }}</td>
              <td>{{ row.sessions_per_week }}x/wk ({{ row.total_sessions }} total)</td>
              <td><StatusBadge :status="row.status || 'pending'" /></td>
              <td>
                <a v-if="row.payment_proof_url" :href="row.payment_proof_url" target="_blank" style="color: var(--color-blue-dark); font-weight: 900; text-decoration: underline;">Open Proof</a>
                <span v-else class="text-muted">No proof</span>
              </td>
              <td>
                <div style="display: flex; justify-content: end; gap: 0.5rem;">
                  <FitnezButton size="sm" variant="secondary" @click="bookingStore.confirmPayment(row.id)">Approve</FitnezButton>
                  <FitnezButton size="sm" variant="danger" @click="openRejectModal(row.id, 'booking')">Reject</FitnezButton>
                </div>
              </td>
            </tr>

            <tr v-if="!bookingStore.pendingBookings.length">
              <td colspan="8" style="padding-block: 4rem; text-align: center; font-weight: 800; color: var(--color-muted);">
                <div class="text-4xl mb-4">📂</div>
                No trainer bookings waiting review.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Table for Membership Renewals -->
      <div v-else-if="activeTab === 'renewals'" class="data-table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Invoice</th>
              <th>Member</th>
              <th>Package</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Proof</th>
              <th style="text-align: right;">Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="row in pendingRenewalsList" :key="row.id">
              <td style="font-weight: 900;">{{ row.invoice_number }}</td>
              <td>
                <p style="font-weight: 900;">{{ row.user?.full_name || 'Unknown' }}</p>
                <p class="text-muted" style="font-size: 0.75rem;">{{ row.user?.email }}</p>
              </td>
              <td style="font-weight: 800;">{{ row.membership_package?.name || 'Unknown' }}</td>
              <td style="font-weight: 800;">Rp {{ Number(row.amount).toLocaleString('en-US') }}</td>
              <td><StatusBadge :status="row.payment_status" /></td>
              <td>
                <a v-if="row.payment_proof_url" :href="row.payment_proof_url" target="_blank" style="color: var(--color-blue-dark); font-weight: 900; text-decoration: underline;">Open Proof</a>
                <span v-else class="text-muted">No proof</span>
              </td>
              <td>
                <div style="display: flex; justify-content: end; gap: 0.5rem;">
                  <FitnezButton size="sm" variant="secondary" @click="approveRenewal(row.id)">Approve</FitnezButton>
                  <FitnezButton size="sm" variant="danger" @click="openRejectModal(row.id, 'renewal')">Reject</FitnezButton>
                </div>
              </td>
            </tr>

            <tr v-if="!pendingRenewalsList.length">
              <td colspan="7" style="padding-block: 4rem; text-align: center; font-weight: 800; color: var(--color-muted);">
                <div class="text-4xl mb-4">📂</div>
                No membership renewals waiting review.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="pager-bar">
        <p>Page {{ activeTab === 'registrations' ? store.page : activeTab === 'bookings' ? bookingStore.page : renewalsPage }} of {{ activeTab === 'registrations' ? store.lastPage : activeTab === 'bookings' ? bookingStore.lastPage : renewalsLastPage }}</p>
        <div class="pagination">
          <button type="button" class="pagination-arrow" :disabled="activeTab === 'registrations' ? store.page <= 1 : activeTab === 'bookings' ? bookingStore.page <= 1 : renewalsPage <= 1" @click="goToPage((activeTab === 'registrations' ? store.page : activeTab === 'bookings' ? bookingStore.page : renewalsPage) - 1)">‹</button>
          <button
            v-for="p in visiblePages"
            :key="p"
            :class="{ active: Number(p) === Number(activeTab === 'registrations' ? store.page : activeTab === 'bookings' ? bookingStore.page : renewalsPage), disabled: p === '...' }"
            :disabled="p === '...'"
            type="button"
            @click="goToPage(p)"
          >
            {{ p }}
          </button>
          <button type="button" class="pagination-arrow" :disabled="activeTab === 'registrations' ? store.page >= store.lastPage : activeTab === 'bookings' ? bookingStore.page >= bookingStore.lastPage : renewalsPage >= renewalsLastPage" @click="goToPage((activeTab === 'registrations' ? store.page : activeTab === 'bookings' ? bookingStore.page : renewalsPage) + 1)">›</button>
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
            <h3 class="title-md" style="margin: 0; font-size: 1.25rem;">Reject Payment</h3>
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
            Reject Payment
          </button>
        </div>
      </div>
    </div>
  </WorkspaceLayout>
</template>

<style scoped>
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
