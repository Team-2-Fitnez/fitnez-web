<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { trainerSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import FitnezButton from '../../components/ui/FitnezButton.vue'
import StatCard from '../../components/ui/StatCard.vue'
import { trainerApplicationApi } from '../../api/trainerApplicationApi'
import { useAuthStore } from '../../stores/authStore'

const auth = useAuthStore()
const editing = ref(false)
const saving = ref(false)
const profileMessage = ref('')
const profileError = ref('')
const profileForm = ref({
  full_name: '',
  age: '',
  phone: '',
})

const profileAge = computed(() => auth.user?.age ?? calculateAge(auth.user?.birth_date))

function calculateAge(value?: string | null) {
  if (!value) return null
  const birthDate = new Date(value)
  if (Number.isNaN(birthDate.getTime())) return null

  const today = new Date()
  let age = today.getFullYear() - birthDate.getFullYear()
  const monthDiff = today.getMonth() - birthDate.getMonth()

  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) age -= 1
  return age >= 0 ? age : null
}

function syncProfileForm() {
  profileForm.value = {
    full_name: auth.user?.full_name || '',
    age: profileAge.value?.toString() || '',
    phone: auth.user?.phone || '',
  }
}

function startEdit() {
  syncProfileForm()
  profileMessage.value = ''
  profileError.value = ''
  editing.value = true
}

function cancelEdit() {
  syncProfileForm()
  profileError.value = ''
  editing.value = false
}

async function saveProfile() {
  profileMessage.value = ''
  profileError.value = ''

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

  saving.value = true

  try {
    await auth.updateProfile({ full_name: fullName, age, phone: phone || null })
    editing.value = false
    profileMessage.value = 'Profile updated successfully.'
    syncProfileForm()
  } catch (error: any) {
    const errors = error?.payload?.errors
    profileError.value = errors
      ? Object.values(errors).flat().join(' ')
      : error?.message || 'Failed to update profile.'
  } finally {
    saving.value = false
  }
}

async function switchToMember() {
  try {
    const response = await trainerApplicationApi.leaveWorkspace()
    if (auth.user) auth.user = response.data.user
    window.showFitnezToast('Successfully switched to Member Workspace', 'success');
    window.location.href = '/entries/member.html'
  } catch (error) {
    window.showFitnezToast('Failed to switch workspace', 'error');
  }
}

watch(() => auth.user, syncProfileForm, { immediate: true, deep: true })
</script>

<template>
  <WorkspaceLayout role="trainer" sidebar-title="Trainer" title="Profile" subtitle="Manage trainer workspace status and switch back to member mode." :sidebar-items="trainerSidebarItems">
    <div class="feature-grid">
      <StatCard label="Workspace" value="Trainer" hint="Approved capability" />
      <StatCard label="Login Account" value="Member" hint="No separate trainer login" />
      <StatCard label="Application" :value="auth.user?.trainer_status || 'approved'" hint="Admin-reviewed" />
    </div>

    <FitnezCard style="margin-top: 1.25rem;">
      <div class="profile-card-head">
        <div>
          <h2 class="title-md">Trainer Profile</h2>
          <p class="text-muted" style="margin-top: 0.75rem;">
            You are still logged in with the same member account. Trainer mode is only a workspace capability granted after admin approves your CV and certificate.
          </p>
        </div>
        <FitnezButton v-if="!editing" type="button" size="sm" @click="startEdit">Edit Profile</FitnezButton>
      </div>

      <div class="panel" style="background: var(--color-cream); margin-top: 1rem;">
        <p class="stat-label">Current account</p>
        <p class="title-md">{{ auth.user?.full_name }}</p>
        <p class="text-muted">{{ auth.user?.email }}</p>
        <div class="profile-meta-grid">
          <div>
            <span>Age</span>
            <strong>{{ profileAge ?? '-' }}</strong>
          </div>
          <div>
            <span>Phone</span>
            <strong>{{ auth.user?.phone || '-' }}</strong>
          </div>
        </div>
      </div>

      <form v-if="editing" class="profile-form" @submit.prevent="saveProfile">
        <label>
          <span>Full Name</span>
          <input v-model="profileForm.full_name" type="text" autocomplete="name" />
        </label>
        <label>
          <span>Age</span>
          <input v-model="profileForm.age" type="number" min="1" max="120" inputmode="numeric" />
        </label>
        <label>
          <span>Phone Number</span>
          <input v-model="profileForm.phone" type="tel" autocomplete="tel" />
        </label>
        <p v-if="profileError" class="profile-error">{{ profileError }}</p>
        <p v-if="profileMessage" class="profile-success">{{ profileMessage }}</p>
        <div class="profile-actions">
          <FitnezButton type="button" variant="secondary" :disabled="saving" @click="cancelEdit">Cancel</FitnezButton>
          <FitnezButton type="submit" :disabled="saving">{{ saving ? 'Saving...' : 'Save Profile' }}</FitnezButton>
        </div>
      </form>
      <p v-else-if="profileMessage" class="profile-success">{{ profileMessage }}</p>

      <div style="margin-top: 1rem;">
        <FitnezButton type="button" @click="switchToMember">
          Switch to Member Workspace
        </FitnezButton>
      </div>
    </FitnezCard>
  </WorkspaceLayout>
</template>

<style scoped>
.profile-card-head {
  align-items: flex-start;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
}

.profile-meta-grid {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  margin-top: 1rem;
}

.profile-meta-grid div {
  background: #ffffff;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 0.9rem;
  padding: 0.75rem;
}

.profile-meta-grid span,
.profile-form span {
  color: #596579;
  display: block;
  font-size: 0.72rem;
  font-weight: 900;
}

.profile-meta-grid strong {
  color: #07172f;
  display: block;
  font-weight: 950;
  margin-top: 0.2rem;
}

.profile-form {
  background: #f8fafc;
  border: 1px solid #dfe6ef;
  border-radius: 1rem;
  display: grid;
  gap: 0.85rem;
  margin-top: 1rem;
  padding: 1rem;
}

.profile-form input {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 0.8rem;
  color: #07172f;
  font-size: 0.92rem;
  font-weight: 800;
  margin-top: 0.45rem;
  outline: none;
  padding: 0.78rem 0.85rem;
  width: 100%;
}

.profile-form input:focus {
  border-color: #0058be;
  box-shadow: 0 0 0 3px rgba(0, 88, 190, 0.12);
}

.profile-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.profile-error,
.profile-success {
  border-radius: 0.8rem;
  font-weight: 900;
  margin: 0;
  padding: 0.75rem;
}

.profile-error {
  background: #fee2e2;
  color: #be123c;
}

.profile-success {
  background: #dcfce7;
  color: #047857;
  margin-top: 1rem;
}

@media (max-width: 640px) {
  .profile-card-head,
  .profile-actions {
    display: grid;
  }

  .profile-meta-grid {
    grid-template-columns: 1fr;
  }
}
</style>
