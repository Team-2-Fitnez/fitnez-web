<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import StatCard from '../../components/ui/StatCard.vue'
import { memberMembershipApi, type MembershipPackage, type MembershipStatusPayload } from '../../api/memberMembershipApi'
import { useAuthStore } from '../../stores/authStore'

const auth = useAuthStore()
const packages = ref<MembershipPackage[]>([])
const status = ref<MembershipStatusPayload | null>(null)
const selectedPackageId = ref<number | null>(null)
const loading = ref(false)
const paying = ref(false)
const message = ref('')
const error = ref('')

const currentPackage = computed(() => status.value?.membership_package || auth.user?.membership_package || null)
const queuedPackage = computed(() => status.value?.queued_membership_package || auth.user?.renewal_package || null)
const daysLeft = computed(() => status.value?.days_left ?? auth.user?.membership_days_left ?? null)
const membershipStatus = computed(() => status.value?.status || auth.user?.membership_status || 'active')
const needsRenewal = computed(() => ['expired', 'expiring_soon', 'no_package'].includes(membershipStatus.value))

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
    status.value = response.data.membership
    await auth.loadMe()
    message.value = status.value.queued_membership_package
      ? 'Payment completed. Your selected package has been queued and will start after your current package ends.'
      : 'Payment completed. Your membership package is now active.'
  } catch (e: any) {
    error.value = e?.message || 'Failed to renew membership.'
  } finally {
    paying.value = false
  }
}

onMounted(loadData)
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

      <button class="renew-button" type="button" :disabled="paying || !selectedPackageId || Boolean(queuedPackage)" @click="renewMembership">
        {{ paying ? 'Processing Payment...' : queuedPackage ? 'Renewal Already Queued' : needsRenewal ? 'Pay and Renew Membership' : 'Pay and Queue Renewal' }}
      </button>
    </FitnezCard>
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
</style>
