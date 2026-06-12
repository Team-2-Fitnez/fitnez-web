<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '../../components/layout/sidebarItems'
import FitnezButton from '../../components/ui/FitnezButton.vue'
import { trainerApplicationApi, type TrainerApplicationStatusResult } from '../../api/trainerApplicationApi'
import { memberMembershipApi } from '../../api/memberMembershipApi'
import { useAuthStore } from '../../stores/authStore'

const router = useRouter()
const auth = useAuthStore()
const status = ref<TrainerApplicationStatusResult | null>(null)
const loading = ref(false)
const error = ref('')
const message = ref('')
const showDeleteConfirm = ref(false)
const deletingAccount = ref(false)

const user = computed(() => auth.user)
const membership = computed(() => user.value?.membership_package)
const accountStatus = computed(() => user.value?.is_active ? 'Active' : 'Inactive')
const trainerStatus = computed(() => status.value?.status || user.value?.trainer_status || 'not_submitted')
const memberInitial = computed(() => (user.value?.full_name || user.value?.email || 'M').trim().charAt(0).toUpperCase())
const freeClassAccess = computed(() => Boolean(user.value?.free_class_access || membership.value?.free_class_access))
const membershipStatus = computed(() => {
  if (!user.value?.is_active) return 'Inactive'
  if (!user.value?.membership_expires_at) return membership.value ? 'Active' : 'No Package Yet'
  return new Date(user.value.membership_expires_at).getTime() >= Date.now() ? 'Active' : 'Expired'
})
const membershipDaysLeft = computed(() => {
  if (!user.value?.membership_expires_at) return null
  const diff = new Date(user.value.membership_expires_at).getTime() - Date.now()
  return Math.max(0, Math.ceil(diff / 86400000))
})
const membershipBenefits = computed(() => {
  const benefits = membership.value?.benefits
  if (Array.isArray(benefits) && benefits.length) return benefits
  return [
    freeClassAccess.value ? 'Class access available according to package' : 'Gym access according to active package duration',
    'Monitor workout plan, meal plan, payments, and schedule from member workspace',
  ]
})

const profileEditing = ref(false)
const profileSaving = ref(false)
const profileError = ref('')
const profileMessage = ref('')
const profileForm = ref({
  full_name: '',
  age: '',
  phone: '',
})
const profileAge = computed(() => user.value?.age ?? calculateAge(user.value?.birth_date))

function calculateAge(value?: string | null) {
  if (!value) return null
  const birthDate = new Date(value)
  if (Number.isNaN(birthDate.getTime())) return null

  const today = new Date()
  let age = today.getFullYear() - birthDate.getFullYear()
  const monthDiff = today.getMonth() - birthDate.getMonth()

  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
    age -= 1
  }

  return age >= 0 ? age : null
}

function syncProfileForm() {
  profileForm.value = {
    full_name: user.value?.full_name || '',
    age: profileAge.value?.toString() || '',
    phone: user.value?.phone || '',
  }
}

function startEditProfile() {
  syncProfileForm()
  profileError.value = ''
  profileMessage.value = ''
  profileEditing.value = true
}

function cancelEditProfile() {
  syncProfileForm()
  profileError.value = ''
  profileEditing.value = false
}

async function saveProfile() {
  profileError.value = ''
  profileMessage.value = ''

  const fullName = profileForm.value.full_name.trim()
  const phone = profileForm.value.phone.trim()
  const age = profileForm.value.age.trim() ? Number(profileForm.value.age) : null

  if (fullName.length < 3) {
    profileError.value = 'Full name must contain at least 3 characters.'
    return
  }

  if (age !== null && (!Number.isInteger(age) || age < 1 || age > 120)) {
    profileError.value = 'Age must be a number between 1 and 120.'
    return
  }

  profileSaving.value = true

  try {
    await auth.updateProfile({
      full_name: fullName,
      age,
      phone: phone || null,
    })
    profileEditing.value = false
    profileMessage.value = 'Profile updated successfully.'
    syncProfileForm()
  } catch (e: any) {
    const errors = e?.payload?.errors
    profileError.value = errors
      ? Object.values(errors).flat().join(' ')
      : e?.message || 'Failed to update profile.'
  } finally {
    profileSaving.value = false
  }
}

watch(user, syncProfileForm, { immediate: true })

function formatDate(value?: string | null) {
  if (!value) return '-'
  return new Intl.DateTimeFormat('en-US', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(value))
}

function formatCurrency(value?: number | null) {
  if (!value) return 'IDR 0'
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
}

async function loadTrainerStatus() {
  try {
    const response = await trainerApplicationApi.status()
    status.value = response.data
    if (auth.user) {
      auth.user.trainer_status = response.data.status
      auth.user.can_access_trainer_workspace = response.data.can_access_trainer_workspace
    }
  } catch (e) {
    status.value = null
  }
}

async function enterTrainerWorkspace() {
  error.value = ''
  loading.value = true

  try {
    const response = await trainerApplicationApi.enterWorkspace()
    if (auth.user) auth.user = response.data.user
    window.location.href = '/entries/trainer.html'
  } catch (e: any) {
    error.value = e?.message || 'Trainer workspace is not available yet.'
  } finally {
    loading.value = false
  }
}

function goToTrainerRegistration() {
  router.push('/trainer/apply')
}

function goToRenewal() {
  router.push('/member/memberships')
}

async function deleteAccount() {
  deletingAccount.value = true
  error.value = ''

  try {
    await memberMembershipApi.deleteAccount()
  } catch {
    // The account may already be deleted on the backend.
  } finally {
    auth.clearSession()
    window.location.href = '/'
  }
}

onMounted(loadTrainerStatus)
</script>

<template>
  <WorkspaceLayout
    role="member"
    sidebar-title="Member"
    title="Profile"
    subtitle="Manage account identity, membership status, and trainer access."
    :sidebar-items="memberSidebarItems"
  >
    <section class="profile-page">
      <div class="profile-grid">
        <aside class="profile-side">
          <article class="surface-card identity-card">
            <div class="avatar-wrap">
              <img v-if="user?.profile_picture_url" :src="user.profile_picture_url" alt="Member profile picture" />
              <span v-else>{{ memberInitial }}</span>
            </div>
            <h2>{{ user?.full_name || 'Fitnez Member' }}</h2>
            <p>{{ user?.email || '-' }}</p>
            <span class="status-pill">
              <span class="material-symbols-outlined">verified</span>
              {{ accountStatus }} Member
            </span>
          </article>

          <article class="surface-card summary-card">
            <h3>Account Summary</h3>
            <div class="summary-list">
              <div class="summary-item">
                <span class="material-symbols-outlined">badge</span>
                <div>
                  <small>Login Role</small>
                  <strong>{{ user?.role || 'member' }}</strong>
                </div>
              </div>
              <div class="summary-item">
                <span class="material-symbols-outlined">event_available</span>
                <div>
                  <small>Joined</small>
                  <strong>{{ formatDate(user?.membership_started_at) }}</strong>
                </div>
              </div>
              <div class="summary-item">
                <span class="material-symbols-outlined">directions_run</span>
                <div>
                  <small>Class Access</small>
                  <strong>{{ freeClassAccess ? 'Included' : 'Not Included' }}</strong>
                </div>
              </div>
            </div>
          </article>
        </aside>

        <main class="profile-main">
          <article class="surface-card stats-card">
            <div class="section-head">
              <div>
                <p>Member Profile</p>
                <h2>Personal Information</h2>
              </div>
              <button v-if="!profileEditing" class="profile-edit-button" type="button" @click="startEditProfile">
                Edit Profile
              </button>
            </div>

            <div class="info-grid">
              <div class="info-box">
                <span class="material-symbols-outlined">person</span>
                <small>Full Name</small>
                <strong>{{ user?.full_name || '-' }}</strong>
              </div>
              <div class="info-box">
                <span class="material-symbols-outlined">mail</span>
                <small>Email</small>
                <strong>{{ user?.email || '-' }}</strong>
              </div>
              <div class="info-box">
                <span class="material-symbols-outlined">cake</span>
                <small>Age</small>
                <strong>{{ profileAge ?? '-' }}</strong>
              </div>
              <div class="info-box">
                <span class="material-symbols-outlined">call</span>
                <small>Phone Number</small>
                <strong>{{ user?.phone || '-' }}</strong>
              </div>
            </div>

            <form v-if="profileEditing" class="profile-form" @submit.prevent="saveProfile">
              <div class="form-grid">
                <label class="form-field">
                  <span>Full Name</span>
                  <input v-model="profileForm.full_name" type="text" autocomplete="name" />
                </label>
                <label class="form-field">
                  <span>Age</span>
                  <input v-model="profileForm.age" type="number" min="1" max="120" inputmode="numeric" />
                </label>
                <label class="form-field">
                  <span>Phone Number</span>
                  <input v-model="profileForm.phone" type="tel" autocomplete="tel" />
                </label>
              </div>
              <p v-if="profileError" class="alert alert-error">{{ profileError }}</p>
              <p v-if="profileMessage" class="alert alert-info">{{ profileMessage }}</p>
              <div class="action-row">
                <button class="safe-button" type="button" :disabled="profileSaving" @click="cancelEditProfile">Cancel</button>
                <button class="profile-save-button" type="submit" :disabled="profileSaving">{{ profileSaving ? 'Saving...' : 'Save Profile' }}</button>
              </div>
            </form>
            <p v-else-if="profileMessage" class="alert alert-info">{{ profileMessage }}</p>
          </article>

          <article class="surface-card membership-card">
            <div class="section-head">
              <div>
                <p>Membership Details</p>
                <h2>{{ membership?.name || 'No Active Package' }}</h2>
              </div>
              <span :class="['membership-status', membershipStatus.toLowerCase()]">{{ membershipStatus }}</span>
            </div>

            <div class="membership-body">
              <div class="membership-copy">
                <p>
                  {{ membership ? `${membership.duration_months} month(s) of Fitnez access at a cost of ${formatCurrency(membership.price)}.` : 'Membership package will appear after registration or payment is approved by admin.' }}
                </p>
                <div class="membership-meta">
                  <div>
                    <small>Start</small>
                    <strong>{{ formatDate(user?.membership_started_at) }}</strong>
                  </div>
                  <div>
                    <small>Expires</small>
                    <strong>{{ formatDate(user?.membership_expires_at) }}</strong>
                  </div>
                  <div>
                    <small>Days Left</small>
                    <strong>{{ membershipDaysLeft ?? '-' }}</strong>
                  </div>
                </div>
              </div>

              <div class="benefit-list">
                <div v-for="benefit in membershipBenefits" :key="benefit" class="benefit-item">
                  <span class="material-symbols-outlined">check_circle</span>
                  <p>{{ benefit }}</p>
                </div>
              </div>
            </div>
            <div class="action-row">
              <FitnezButton type="button" @click="goToRenewal">Renew Membership</FitnezButton>
            </div>
          </article>

          <article class="surface-card trainer-card">
            <div class="section-head">
              <div>
                <p>Trainer Workspace</p>
                <h2>Trainer Application</h2>
              </div>
              <span class="trainer-status">{{ trainerStatus.replace('_', ' ') }}</span>
            </div>

            <p class="trainer-copy">
              Submit CV and certificate to be verified by admin. Once approved, trainer access will be active from this member profile.
            </p>
            <p v-if="status?.application?.admin_notes" class="note">Admin notes: {{ status.application.admin_notes }}</p>
            <p v-if="error" class="alert alert-error">{{ error }}</p>
            <p v-if="message" class="alert alert-info">{{ message }}</p>

            <div class="action-row">
              <FitnezButton
                v-if="!status?.can_access_trainer_workspace"
                type="button"
                :disabled="loading || trainerStatus === 'pending'"
                @click="goToTrainerRegistration"
              >
                {{ trainerStatus === 'pending' ? 'Waiting for Admin Review' : 'Apply to be a Trainer' }}
              </FitnezButton>
              <FitnezButton v-if="status?.can_access_trainer_workspace || user?.can_access_trainer_workspace" type="button" :disabled="loading" @click="enterTrainerWorkspace">
                Enter Trainer Workspace
              </FitnezButton>
            </div>
          </article>

          <article class="surface-card danger-card">
            <div class="section-head">
              <div>
                <p>Account Control</p>
                <h2>Delete Account</h2>
              </div>
            </div>
            <p class="trainer-copy">Delete your current member account from Fitnez. This action returns you to the landing page.</p>
            <div v-if="!showDeleteConfirm" class="action-row">
              <button class="danger-button" type="button" @click="showDeleteConfirm = true">Delete Account</button>
            </div>
            <div v-else class="confirm-box">
              <p>Are you sure you want to delete your account?</p>
              <div class="action-row">
                <button class="safe-button" type="button" :disabled="deletingAccount" @click="showDeleteConfirm = false">Back</button>
                <button class="danger-button" type="button" :disabled="deletingAccount" @click="deleteAccount">{{ deletingAccount ? 'Deleting...' : 'Yes, Delete Account' }}</button>
              </div>
            </div>
          </article>
        </main>
      </div>
    </section>
  </WorkspaceLayout>
</template>

<style scoped>
.profile-page {
  color: #07172f;
  margin-inline: auto;
  max-width: 1180px;
  width: 100%;
}

.profile-grid {
  align-items: start;
  display: grid;
  gap: 1.25rem;
  grid-template-columns: minmax(260px, 0.42fr) minmax(0, 1fr);
}

.profile-side,
.profile-main {
  display: grid;
  gap: 1.25rem;
}

.surface-card {
  background: #ffffff;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 1.25rem;
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
  padding: 1.35rem;
}

.identity-card {
  align-items: center;
  display: flex;
  flex-direction: column;
  text-align: center;
}

.avatar-wrap {
  align-items: center;
  background: #dfe7ff;
  border: 4px solid #ffffff;
  border-radius: 999px;
  box-shadow: 0 16px 35px rgba(15, 23, 42, 0.12);
  color: #0058be;
  display: flex;
  font-size: 2.6rem;
  font-weight: 950;
  height: 8rem;
  justify-content: center;
  overflow: hidden;
  width: 8rem;
}

.avatar-wrap img {
  height: 100%;
  object-fit: cover;
  width: 100%;
}

.identity-card h2 {
  color: #07172f;
  font-size: 1.25rem;
  font-weight: 950;
  margin: 1rem 0 0.25rem;
}

.identity-card p,
.trainer-copy,
.membership-copy p,
.note {
  color: #596579;
  font-size: 0.88rem;
  font-weight: 750;
  line-height: 1.55;
  margin: 0;
}

.status-pill,
.membership-status,
.trainer-status {
  align-items: center;
  background: #e8f1ff;
  border-radius: 999px;
  color: #0058be;
  display: inline-flex;
  font-size: 0.72rem;
  font-weight: 950;
  gap: 0.35rem;
  margin-top: 1rem;
  padding: 0.38rem 0.75rem;
  text-transform: capitalize;
}

.status-pill span {
  font-size: 1rem;
}

.summary-card h3,
.section-head h2 {
  color: #07172f;
  font-size: 1rem;
  font-weight: 950;
  margin: 0;
}

.summary-list {
  display: grid;
  gap: 0.75rem;
  margin-top: 1rem;
}

.summary-item {
  align-items: center;
  background: #f8fafc;
  border: 1px solid #dfe6ef;
  border-radius: 0.9rem;
  display: flex;
  gap: 0.8rem;
  padding: 0.85rem;
}

.summary-item > span {
  align-items: center;
  background: #e8f1ff;
  border-radius: 0.75rem;
  color: #0058be;
  display: flex;
  height: 2.4rem;
  justify-content: center;
  width: 2.4rem;
}

.summary-item small,
.info-box small,
.membership-meta small,
.section-head p {
  color: #596579;
  display: block;
  font-size: 0.7rem;
  font-weight: 850;
}

.summary-item strong,
.info-box strong,
.membership-meta strong {
  color: #07172f;
  display: block;
  font-size: 0.9rem;
  font-weight: 950;
  margin-top: 0.12rem;
}

.section-head {
  align-items: flex-start;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.section-head p {
  color: #0058be;
  margin: 0 0 0.25rem;
  text-transform: uppercase;
}

.info-grid {
  display: grid;
  gap: 0.85rem;
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.info-box {
  background: #f8fafc;
  border: 1px solid #dfe6ef;
  border-radius: 1rem;
  min-width: 0;
  padding: 0.95rem;
}

.info-box > span {
  color: #667085;
  display: block;
  margin-bottom: 0.7rem;
}

.info-box strong {
  overflow-wrap: anywhere;
}

.profile-edit-button,
.profile-save-button {
  background: #0058be;
  border: 0;
  border-radius: 999px;
  color: #ffffff;
  cursor: pointer;
  font-weight: 950;
  padding: 0.75rem 1rem;
}

.profile-save-button {
  background: #ff7a1a;
}

.profile-edit-button:disabled,
.profile-save-button:disabled {
  cursor: not-allowed;
  opacity: 0.65;
}

.profile-form {
  background: #f8fafc;
  border: 1px solid #dfe6ef;
  border-radius: 1rem;
  margin-top: 1rem;
  padding: 1rem;
}

.form-grid {
  display: grid;
  gap: 0.85rem;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.form-field {
  display: grid;
  gap: 0.45rem;
}

.form-field span {
  color: #596579;
  font-size: 0.72rem;
  font-weight: 900;
}

.form-field input {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 0.8rem;
  color: #07172f;
  font-size: 0.92rem;
  font-weight: 800;
  outline: none;
  padding: 0.78rem 0.85rem;
}

.form-field input:focus {
  border-color: #0058be;
  box-shadow: 0 0 0 3px rgba(0, 88, 190, 0.12);
}

.membership-status.active {
  background: #dcfce7;
  color: #047857;
}

.membership-status.expired,
.membership-status.inactive {
  background: #fee2e2;
  color: #be123c;
}

.membership-status.no,
.membership-status.no_package {
  background: #f1f5f9;
  color: #475569;
}

.membership-body {
  background: #f8fafc;
  border: 1px solid #dfe6ef;
  border-radius: 1rem;
  display: grid;
  gap: 1rem;
  grid-template-columns: minmax(0, 1fr) minmax(240px, 0.55fr);
  padding: 1rem;
}

.membership-meta {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  margin-top: 1rem;
}

.benefit-list {
  display: grid;
  gap: 0.65rem;
}

.benefit-item {
  align-items: flex-start;
  background: #ffffff;
  border: 1px solid #dfe6ef;
  border-radius: 0.8rem;
  display: flex;
  gap: 0.55rem;
  padding: 0.7rem;
}

.benefit-item span {
  color: #047857;
  font-size: 1rem;
}

.benefit-item p {
  color: #152238;
  font-size: 0.78rem;
  font-weight: 850;
  line-height: 1.45;
  margin: 0;
}

.trainer-card {
  display: grid;
}

.trainer-status {
  background: #f1f5f9;
  color: #475569;
  margin-top: 0;
}

.note,
.alert {
  margin-top: 0.8rem;
}

.action-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: 1rem;
}

.danger-card {
  border-color: rgba(190, 18, 60, 0.18);
}

.danger-button,
.safe-button {
  border: 0;
  border-radius: 999px;
  font-weight: 950;
  padding: 0.8rem 1rem;
}

.danger-button {
  background: #be123c;
  color: #ffffff;
}

.safe-button {
  background: #e2e8f0;
  color: #0f172a;
}

.danger-button:disabled,
.safe-button:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.confirm-box {
  background: #fff1f2;
  border: 1px solid #fecdd3;
  border-radius: 1rem;
  margin-top: 1rem;
  padding: 1rem;
}

.confirm-box p {
  color: #9f1239;
  font-weight: 900;
  margin: 0;
}

@media (max-width: 1020px) {
  .profile-grid,
  .membership-body {
    grid-template-columns: 1fr;
  }

  .info-grid,
  .form-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 640px) {
  .surface-card {
    border-radius: 1rem;
  }

  .info-grid,
  .form-grid,
  .membership-meta {
    grid-template-columns: 1fr;
  }

  .section-head {
    display: grid;
  }
}
</style>
