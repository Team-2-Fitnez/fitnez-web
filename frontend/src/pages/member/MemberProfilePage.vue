<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import FitnezButton from '../../components/ui/FitnezButton.vue'
import { useAuthStore } from '../../stores/authStore'
import { trainerApplicationApi, type TrainerApplicationStatusResult } from '../../api/trainerApplicationApi'

const router = useRouter()
const auth = useAuthStore()
const status = ref<TrainerApplicationStatusResult | null>(null)
const showDialog = ref(false)
const cv = ref<File | null>(null)
const certificate = ref<File | null>(null)
const loading = ref(false)
const error = ref('')
const message = ref('')

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

function onFileChange(event: Event, target: 'cv' | 'certificate') {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0] || null

  if (target === 'cv') cv.value = file
  else certificate.value = file
}

async function submitTrainerApplication() {
  error.value = ''
  message.value = ''

  if (!cv.value || !certificate.value) {
    error.value = 'Please upload both CV and certificate as PDF files.'
    return
  }

  loading.value = true

  try {
    await trainerApplicationApi.submit(cv.value, certificate.value, '', 0)
    message.value = 'Trainer application submitted. Please wait for admin review.'
    showDialog.value = false
    cv.value = null
    certificate.value = null
    await loadTrainerStatus()
  } catch (e: any) {
    error.value = e?.message || 'Failed to submit trainer application.'
  } finally {
    loading.value = false
  }
}

async function enterTrainerWorkspace() {
  error.value = ''
  loading.value = true

  try {
    const response = await trainerApplicationApi.enterWorkspace()
    if (auth.user) auth.user = response.data.user
    await router.push(response.data.redirect_to)
  } catch (e: any) {
    error.value = e?.message || 'Trainer workspace is not available yet.'
  } finally {
    loading.value = false
  }
}

function goToTrainerRegistration() {
  router.push('/trainer/daftar')
}

onMounted(loadTrainerStatus)
</script>

<template>
  <WorkspaceLayout
    role="member"
    sidebar-title="Member"
    title="Profile"
    subtitle="Manage member personal information and trainer application status."
    :sidebar-items="memberSidebarItems"
  >
    <div style="display: grid; gap: 1.25rem; grid-template-columns: repeat(auto-fit, minmax(min(100%, 360px), 1fr));">
      <FitnezCard>
        <p class="eyebrow">Member Profile</p>
        <h2 class="title-md">Personal Information</h2>
        <div style="display: grid; gap: 0.75rem; margin-top: 1rem;">
          <div class="panel" style="background: var(--color-cream);">
            <p class="stat-label">Name</p>
            <p class="title-md">{{ auth.user?.full_name || '-' }}</p>
          </div>
          <div class="panel" style="background: var(--color-cream);">
            <p class="stat-label">Email</p>
            <p class="text-muted">{{ auth.user?.email || '-' }}</p>
          </div>
          <div class="panel" style="background: var(--color-cream);">
            <p class="stat-label">Login role</p>
            <p class="title-md">{{ auth.user?.role || 'member' }}</p>
            <p class="text-muted">Trainer access is handled as a profile capability, not a separate login form.</p>
          </div>
        </div>
      </FitnezCard>

      <FitnezCard>
        <p class="eyebrow">Become a Trainer</p>
        <h2 class="title-md">Trainer Application</h2>
        <p class="text-muted" style="margin-top: 0.75rem;">
          Submit your CV and certificate in PDF format. Admin will review both files, then your profile status will change to approved or rejected.
        </p>

        <div class="panel" style="background: var(--color-cream); margin-top: 1rem;">
          <p class="stat-label">Current status</p>
          <p class="title-md" style="text-transform: capitalize;">{{ status?.status || auth.user?.trainer_status || 'not_submitted' }}</p>
          <p v-if="status?.application?.admin_notes" class="text-muted" style="margin-top: 0.5rem;">
            Admin note: {{ status.application.admin_notes }}
          </p>
        </div>

        <p v-if="error" class="alert alert-error" style="margin-top: 1rem;">{{ error }}</p>
        <p v-if="message" class="alert alert-info" style="margin-top: 1rem;">{{ message }}</p>

        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 1rem;">
          <FitnezButton
            v-if="!status?.can_access_trainer_workspace"
            type="button"
            :disabled="loading || status?.status === 'pending'"
            @click="goToTrainerRegistration"
          >
            {{ status?.status === 'pending' ? 'Waiting for Admin Review' : 'Daftar Jadi Trainer' }}
          </FitnezButton>

          <FitnezButton v-if="status?.can_access_trainer_workspace || auth.user?.can_access_trainer_workspace" type="button" :disabled="loading" @click="enterTrainerWorkspace">
            Enter Trainer Workspace
          </FitnezButton>
        </div>
      </FitnezCard>
    </div>

    <div v-if="showDialog" class="modal-backdrop">
      <div class="modal-card">
        <div>
          <p class="eyebrow">Trainer Application</p>
          <h2 class="title-md">Upload CV and certificate</h2>
          <p class="text-muted" style="margin-top: 0.5rem;">
            Both files must be PDF. After sending, admin can open and approve or reject your application.
          </p>
        </div>

        <label class="file-field">
          <span>CV PDF</span>
          <input type="file" accept="application/pdf" @change="onFileChange($event, 'cv')" />
        </label>

        <label class="file-field">
          <span>Certificate PDF</span>
          <input type="file" accept="application/pdf" @change="onFileChange($event, 'certificate')" />
        </label>

        <p v-if="error" class="alert alert-error">{{ error }}</p>

        <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
          <button type="button" class="ghost-button" @click="showDialog = false">Cancel</button>
          <FitnezButton type="button" :disabled="loading" @click="submitTrainerApplication">
            {{ loading ? 'Sending...' : 'Send Application' }}
          </FitnezButton>
        </div>
      </div>
    </div>
  </WorkspaceLayout>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 50;
  display: grid;
  place-items: center;
  background: rgba(0, 0, 0, 0.45);
  padding: 1rem;
}

.modal-card {
  width: min(100%, 560px);
  display: grid;
  gap: 1rem;
  border-radius: 1.5rem;
  background: white;
  padding: 1.5rem;
  box-shadow: 0 24px 80px rgba(0, 0, 0, 0.22);
}

.file-field {
  display: grid;
  gap: 0.5rem;
  font-weight: 900;
}

.file-field input {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 1rem;
  padding: 0.9rem;
}

.ghost-button {
  border: 0;
  border-radius: 1rem;
  padding: 0.75rem 1rem;
  background: var(--color-cream);
  font-weight: 900;
}
</style>
