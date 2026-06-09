<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import StatCard from '../../components/ui/StatCard.vue'
import { memberMembershipApi, type MembershipPackage, type MembershipStatusPayload } from '../../api/memberMembershipApi'
import { useAuthStore } from '../../stores/authStore'
import { manualRegistrationApi } from '../../api/manualRegistrationApi'

const auth = useAuthStore()
const packages = ref<MembershipPackage[]>([])
const status = ref<MembershipStatusPayload | null>(null)
const selectedPackageId = ref<number | null>(null)
const loading = ref(false)
const paying = ref(false)
const message = ref('')
const error = ref('')

// Payment proof modal refs
const showPaymentModal = ref(false)
const activePayment = ref<any>(null)
const paymentFile = ref<File | null>(null)
const uploading = ref(false)
const uploadError = ref('')
const dragging = ref(false)
const fileInput = ref<HTMLInputElement>()
const qrisImageUrl = ref('')

const currentPackage = computed(() => status.value?.membership_package || auth.user?.membership_package || null)
const queuedPackage = computed(() => status.value?.queued_membership_package || auth.user?.renewal_package || null)
const daysLeft = computed(() => status.value?.days_left ?? auth.user?.membership_days_left ?? null)
const membershipStatus = computed(() => status.value?.status || auth.user?.membership_status || 'active')
const needsRenewal = computed(() => ['expired', 'expiring_soon', 'no_package'].includes(membershipStatus.value))

const selectedPackage = computed(() => {
  return packages.value.find(p => p.id === selectedPackageId.value) || null
})

function formatDate(value?: string | null) {
  if (!value) return '-'
  return new Intl.DateTimeFormat('en-US', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value))
}

function formatCurrency(value?: number | string | null) {
  const amount = Number(value || 0)
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount)
}

function statusLabel(value: string) {
  if (value === 'expiring_soon') return 'Expiring Soon'
  if (value === 'no_package') return 'No Package'
  if (value === 'grace_expired') return 'Renewal Deadline Passed'
  return value.charAt(0).toUpperCase() + value.slice(1)
}

async function loadPaymentMethods() {
  try {
    const res = await manualRegistrationApi.paymentMethods()
    const qris = res.data.find((m: any) => m.type === 'qris')
    if (qris) {
      qrisImageUrl.value = qris.qris_image_url || '/images/payment/qris-fitnez-placeholder.svg'
    }
  } catch (e) {
    console.error(e)
    qrisImageUrl.value = '/images/payment/qris-fitnez-placeholder.svg'
  }
}

async function loadData() {
  loading.value = true
  error.value = ''

  try {
    const [statusResponse, packageResponse] = await Promise.all([
      memberMembershipApi.status(),
      memberMembershipApi.packages(),
    ])
    status.value = statusResponse.data
    packages.value = packageResponse.data.filter(item => item.is_active !== false)
    selectedPackageId.value = packages.value[0]?.id ?? null
    await auth.loadMe()
  } catch (e: any) {
    error.value = e?.message || 'Failed to load membership information.'
  } finally {
    loading.value = false
  }
}

async function renewMembership() {
  if (!selectedPackageId.value) return
  paying.value = true
  message.value = ''
  error.value = ''

  try {
    const response = await memberMembershipApi.renew(selectedPackageId.value)
    activePayment.value = response.data.payment
    showPaymentModal.value = true
  } catch (e: any) {
    error.value = e?.message || 'Failed to initiate renewal.'
  } finally {
    paying.value = false
  }
}

function closePaymentModal() {
  showPaymentModal.value = false
  activePayment.value = null
  paymentFile.value = null
  uploadError.value = ''
}

function browseFiles() {
  fileInput.value?.click()
}

function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement
  if (target.files?.length) {
    paymentFile.value = target.files[0]
    uploadError.value = ''
  }
}

function onDrop(e: DragEvent) {
  dragging.value = false
  if (e.dataTransfer?.files.length) {
    paymentFile.value = e.dataTransfer.files[0]
    uploadError.value = ''
  }
}

async function uploadPayment() {
  if (!paymentFile.value || !activePayment.value) return
  uploading.value = true
  uploadError.value = ''
  try {
    await memberMembershipApi.uploadProof(activePayment.value.id, paymentFile.value)
    closePaymentModal()
    message.value = 'Payment proof submitted. Awaiting admin confirmation.'
    await loadData()
  } catch (e: any) {
    uploadError.value = e?.message || 'Upload failed. Please try again.'
  } finally {
    uploading.value = false
  }
}

onMounted(() => {
  loadData()
  loadPaymentMethods()
})
</script>

<template>
  <WorkspaceLayout
    role="member"
    sidebar-title="Member"
    title="Membership Renewal"
    subtitle="Review your package status, choose a new package, and complete renewal payment."
    :sidebar-items="memberSidebarItems"
  >
    <div class="feature-grid">
      <StatCard label="Status" :value="statusLabel(membershipStatus)" hint="Current membership state" />
      <StatCard label="Current Package" :value="currentPackage?.name || 'No Package'" hint="Active package until expiry" />
      <StatCard label="Days Left" :value="daysLeft === null ? '-' : String(daysLeft)" hint="Warning starts three days before expiry" />
    </div>

    <p v-if="error" class="membership-alert error">{{ error }}</p>
    <p v-if="message" class="membership-alert success">{{ message }}</p>

    <div v-if="status?.pending_renewal_payment" class="membership-alert warning" style="background: #eff6ff; border: 1px solid #93c5fd; color: #1e3a8a; display: flex; flex-direction: column; gap: 0.25rem;">
      <p style="margin: 0; font-weight: 800;">Awaiting Payment Confirmation</p>
      <p style="margin: 0; font-size: 0.8rem; font-weight: 600;">
        Your payment for renewal to package <strong>{{ status.pending_renewal_payment.package_name }}</strong> is awaiting admin review.
      </p>
    </div>

    <section class="membership-grid">
      <FitnezCard>
        <div class="section-head">
          <div>
            <p class="eyebrow">Current Membership</p>
            <h2 class="title-md">{{ currentPackage?.name || 'No active package' }}</h2>
          </div>
          <span :class="['status-chip', membershipStatus]">{{ statusLabel(membershipStatus) }}</span>
        </div>

        <div class="status-list">
          <div>
            <span>Started</span>
            <strong>{{ formatDate(status?.membership_started_at || auth.user?.membership_started_at) }}</strong>
          </div>
          <div>
            <span>Expires</span>
            <strong>{{ formatDate(status?.membership_expires_at || auth.user?.membership_expires_at) }}</strong>
          </div>
          <div>
            <span>Renewal Deadline</span>
            <strong>{{ formatDate(status?.renewal_deadline_at || auth.user?.membership_renewal_deadline_at) }}</strong>
          </div>
        </div>

        <p v-if="membershipStatus === 'expired'" class="helper-copy">
          Your membership has expired. You can still log in during the one-month grace period, but workspace features stay locked until renewal is completed.
        </p>
        <p v-else-if="membershipStatus === 'expiring_soon'" class="helper-copy warning">
          Your membership will expire within three days. Renew now to queue the next package without interrupting your current package.
        </p>
        <p v-else class="helper-copy">
          Early renewal is supported. The current package will finish first, then the queued package and its benefits will start.
        </p>
      </FitnezCard>

      <FitnezCard>
        <div class="section-head">
          <div>
            <p class="eyebrow">Queued Package</p>
            <h2 class="title-md">{{ queuedPackage?.name || 'No queued renewal' }}</h2>
          </div>
        </div>

        <div v-if="queuedPackage" class="queued-box">
          <p>Your next package is already paid and will start automatically after the active package ends.</p>
          <div class="status-list compact">
            <div>
              <span>Starts</span>
              <strong>{{ formatDate(status?.queued_membership_starts_at || auth.user?.membership_renewal_starts_at) }}</strong>
            </div>
            <div>
              <span>Expires</span>
              <strong>{{ formatDate(status?.queued_membership_expires_at || auth.user?.membership_renewal_expires_at) }}</strong>
            </div>
          </div>
        </div>
        <p v-else class="helper-copy">There is no queued renewal yet. Select a package below to renew.</p>
      </FitnezCard>
    </section>

    <FitnezCard style="margin-top: 1.25rem;">
      <div class="section-head">
        <div>
          <p class="eyebrow">Renewal Payment</p>
          <h2 class="title-md">Choose your next package</h2>
        </div>
      </div>

      <div v-if="loading" class="loading-box">Loading membership packages...</div>
      <div v-else class="package-grid">
        <label v-for="item in packages" :key="item.id" :class="['package-option', { selected: selectedPackageId === item.id }]">
          <input v-model="selectedPackageId" type="radio" :value="item.id" />
          <span class="package-title">{{ item.name }}</span>
          <strong>{{ formatCurrency(item.price) }}</strong>
          <small>{{ item.duration_months }} month(s) access</small>
          <small>{{ item.free_class_access ? 'Free class access included' : 'Gym access package' }}</small>
        </label>
      </div>

      <button class="renew-button" type="button" :disabled="paying || !selectedPackageId || Boolean(queuedPackage) || Boolean(status?.pending_renewal_payment)" @click="renewMembership">
        {{ paying ? 'Processing Payment...' : queuedPackage ? 'Renewal Already Queued' : status?.pending_renewal_payment ? 'Awaiting Confirmation' : needsRenewal ? 'Pay and Renew Membership' : 'Pay and Queue Renewal' }}
      </button>
    </FitnezCard>

    <!-- Payment Proof Modal -->
    <div v-if="showPaymentModal && activePayment" class="modal-overlay" @click.self="closePaymentModal">
      <div class="modal-card">
        <div class="modal-header">
          <div class="booking-icon-wrapper" style="background: rgba(251, 191, 36, 0.12); color: #d97706;">
            <span class="material-symbols-outlined">payments</span>
          </div>
          <div>
            <p class="eyebrow" style="margin: 0; font-size: 0.7rem;">Payment Required</p>
            <h2 class="title-md" style="margin: 0; font-size: 1.25rem;">Complete Payment</h2>
          </div>
        </div>

        <div class="payment-info">
          <div class="payment-detail">
            <span>Package</span>
            <strong>{{ selectedPackage?.name }}</strong>
          </div>
          <div class="payment-detail">
            <span>Total to Pay</span>
            <strong class="text-orange" style="font-size: 1.25rem;">{{ formatCurrency(activePayment.amount) }}</strong>
          </div>
          <div class="payment-detail">
            <span>Duration</span>
            <strong>{{ selectedPackage?.duration_months }} month(s)</strong>
          </div>
        </div>

        <div class="payment-instructions">
          <p class="instructions-title">How to Pay</p>
          <ol class="instructions-list">
            <li>Transfer <strong>{{ formatCurrency(activePayment.amount) }}</strong> to one of the following methods:</li>
            <li><strong>QRIS</strong> — Scan the QR code below</li>
            <li><strong>Bank Transfer</strong> — BCA 1234567890 a.n. PT Fitnez Sehat Indonesia</li>
          </ol>
          <div v-if="qrisImageUrl" class="qris-box" style="margin-top: 1rem; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; background: white; padding: 1rem; border-radius: 0.75rem; border: 1px solid rgba(0,0,0,0.06);">
            <img
              :src="qrisImageUrl"
              alt="QRIS Code"
              class="qris-img"
              style="width: 200px; height: 200px; object-fit: contain; margin-bottom: 0.5rem;"
            />
            <span class="qris-caption" style="font-size: 0.75rem; color: #64748b; font-weight: 700;">Scan QRIS Code to Pay</span>
          </div>
        </div>

        <div class="upload-section">
          <label class="form-label">Upload Payment Proof</label>
          <div
            class="upload-zone"
            :class="{ 'has-file': paymentFile, dragging }"
            @dragover.prevent="dragging = true"
            @dragleave="dragging = false"
            @drop.prevent="onDrop"
            @click="browseFiles"
          >
            <input
              ref="fileInput"
              type="file"
              accept="image/jpeg,image/png,image/webp"
              hidden
              @change="onFileChange"
            />
            <template v-if="!paymentFile">
              <span class="material-symbols-outlined upload-icon">cloud_upload</span>
              <p>Drag & drop screenshot here, or <span class="link-btn" style="cursor: pointer;">browse</span></p>
              <small>JPG, PNG, or WebP. Max 4MB.</small>
            </template>
            <template v-else>
              <span class="material-symbols-outlined upload-icon success">check_circle</span>
              <p>{{ paymentFile.name }}</p>
              <small>{{ (paymentFile.size / 1024).toFixed(0) }} KB</small>
            </template>
          </div>
          <p v-if="uploadError" class="field-error">{{ uploadError }}</p>
        </div>

        <div class="form-actions">
          <button type="button" class="button button-ghost" @click="closePaymentModal">Close</button>
          <button
            type="button"
            class="button button-primary"
            :disabled="!paymentFile || uploading"
            @click="uploadPayment"
          >
            {{ uploading ? 'Uploading...' : 'Submit Payment Proof' }}
          </button>
        </div>
      </div>
    </div>
  </WorkspaceLayout>
</template>

<style scoped>
.membership-grid {
  display: grid;
  gap: 1.25rem;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  margin-top: 1.25rem;
}

.section-head {
  align-items: flex-start;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.eyebrow {
  color: #0058be;
  font-size: 0.72rem;
  font-weight: 900;
  letter-spacing: 0.12em;
  margin: 0 0 0.25rem;
  text-transform: uppercase;
}

.status-chip {
  border-radius: 999px;
  background: #dcfce7;
  color: #047857;
  font-size: 0.74rem;
  font-weight: 950;
  padding: 0.4rem 0.75rem;
  text-transform: capitalize;
}

.status-chip.expired,
.status-chip.grace_expired {
  background: #fee2e2;
  color: #be123c;
}

.status-chip.expiring_soon {
  background: #fef3c7;
  color: #b45309;
}

.status-list {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.status-list.compact {
  grid-template-columns: repeat(2, minmax(0, 1fr));
  margin-top: 1rem;
}

.status-list div,
.queued-box {
  background: #f8fafc;
  border: 1px solid #dfe6ef;
  border-radius: 1rem;
  padding: 0.9rem;
}

.status-list span {
  color: #596579;
  display: block;
  font-size: 0.72rem;
  font-weight: 850;
}

.status-list strong {
  color: #07172f;
  display: block;
  font-size: 0.92rem;
  font-weight: 950;
  margin-top: 0.2rem;
}

.helper-copy,
.queued-box p {
  color: #596579;
  font-size: 0.9rem;
  font-weight: 750;
  line-height: 1.6;
  margin: 1rem 0 0;
}

.helper-copy.warning {
  color: #92400e;
}

.package-grid {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.package-option {
  background: #f8fafc;
  border: 2px solid #dfe6ef;
  border-radius: 1.1rem;
  cursor: pointer;
  display: grid;
  gap: 0.35rem;
  padding: 1rem;
}

.package-option.selected {
  border-color: #f97316;
  box-shadow: 0 14px 35px rgba(249, 115, 22, 0.12);
}

.package-option input {
  accent-color: #f97316;
}

.package-title {
  color: #07172f;
  font-weight: 950;
}

.package-option strong {
  color: #0058be;
  font-size: 1.15rem;
  font-weight: 950;
}

.package-option small {
  color: #596579;
  font-weight: 750;
}

.renew-button {
  background: #f97316;
  border: 0;
  border-radius: 1rem;
  color: #fff;
  font-weight: 950;
  margin-top: 1.25rem;
  padding: 0.95rem 1.2rem;
}

.renew-button:disabled {
  cursor: not-allowed;
  opacity: 0.55;
}

.membership-alert,
.loading-box {
  border-radius: 1rem;
  font-size: 0.9rem;
  font-weight: 850;
  margin-top: 1rem;
  padding: 0.9rem 1rem;
}

.membership-alert.error {
  background: #fee2e2;
  color: #be123c;
}

.membership-alert.success {
  background: #dcfce7;
  color: #047857;
}

.loading-box {
  background: #f8fafc;
  color: #596579;
}

@media (max-width: 980px) {
  .membership-grid,
  .package-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .status-list,
  .status-list.compact {
    grid-template-columns: 1fr;
  }
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
  width: min(100%, 480px);
  display: grid;
  gap: 1.5rem;
  border-radius: 1.5rem;
  background: rgba(255, 255, 255, 0.98);
  border: 1px solid rgba(15, 23, 42, 0.08);
  padding: 2rem;
  box-shadow: 0 24px 60px rgba(11, 28, 48, 0.18);
  max-height: 90vh;
  overflow-y: auto;
  animation: scaleUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  color: var(--color-black);
}

.modal-header {
  display: flex;
  gap: 1rem;
  align-items: center;
  border-bottom: 1px solid rgba(0,0,0,0.06);
  padding-bottom: 1rem;
}

.booking-icon-wrapper {
  background: rgba(54, 90, 130, 0.1);
  color: var(--color-blue);
  width: 3rem;
  height: 3rem;
  border-radius: 0.75rem;
  display: grid;
  place-items: center;
  flex-shrink: 0;
}

.payment-info {
  background: #f8fafc;
  border-radius: 0.75rem;
  padding: 1rem;
  display: grid;
  gap: 0.5rem;
}

.payment-detail {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  align-items: center;
}

.payment-instructions {
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 0.75rem;
  padding: 1rem;
}

.instructions-title {
  font-weight: 800;
  font-size: 0.85rem;
  margin-bottom: 0.5rem;
}

.instructions-list {
  margin: 0;
  padding-left: 1.25rem;
  font-size: 0.8rem;
  display: grid;
  gap: 0.35rem;
  color: #475569;
}

.upload-section {
  display: flex;
  flex-direction: column;
}

.upload-zone {
  border: 2px dashed #cbd5e1;
  border-radius: 0.75rem;
  padding: 1.5rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s ease;
  background: #f8fafc;
  display: grid;
  gap: 0.35rem;
  place-items: center;
}

.upload-zone:hover,
.upload-zone.dragging {
  border-color: var(--color-blue);
  background: #f0f4ff;
}

.upload-zone.has-file {
  border-color: #22c55e;
  background: #f0fdf4;
}

.upload-icon {
  font-size: 2rem !important;
  color: #94a3b8;
}

.upload-icon.success {
  color: #22c55e;
}

.upload-zone p {
  font-size: 0.85rem;
  font-weight: 600;
  margin: 0;
}

.upload-zone small {
  font-size: 0.7rem;
  color: #64748b;
}

.link-btn {
  color: var(--color-blue);
  font-weight: 700;
  text-decoration: underline;
}

.form-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  margin-top: 0.5rem;
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

.button-primary {
  background: var(--color-blue);
  color: white;
}

.button-primary:hover {
  background: var(--color-blue-dark);
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes scaleUp {
  from { transform: scale(0.95); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}
</style>
