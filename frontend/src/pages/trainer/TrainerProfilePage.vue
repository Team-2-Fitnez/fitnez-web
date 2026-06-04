<script setup lang="ts">
import { useRouter } from 'vue-router'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { trainerSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import FitnezButton from '../../components/ui/FitnezButton.vue'
import StatCard from '../../components/ui/StatCard.vue'
import { trainerApplicationApi } from '../../api/trainerApplicationApi'
import { useAuthStore } from '../../stores/authStore'

const router = useRouter()
const auth = useAuthStore()

async function switchToMember() {
  try {
    const response = await trainerApplicationApi.leaveWorkspace()
    if (auth.user) auth.user = response.data.user
    window.showFitnezToast('Berhasil beralih ke Workspace Member', 'success');
    await router.push(response.data.redirect_to)
  } catch (error) {
    window.showFitnezToast('Gagal beralih workspace', 'error');
  }
}
</script>

<template>
  <WorkspaceLayout role="trainer" sidebar-title="Trainer" title="Profile" subtitle="Manage trainer workspace status and switch back to member mode." :sidebar-items="trainerSidebarItems">
    <div class="feature-grid">
      <StatCard label="Workspace" value="Trainer" hint="Approved capability" />
      <StatCard label="Login Account" value="Member" hint="No separate trainer login" />
      <StatCard label="Application" :value="auth.user?.trainer_status || 'approved'" hint="Admin-reviewed" />
    </div>

    <FitnezCard style="margin-top: 1.25rem;">
      <h2 class="title-md">Trainer Profile</h2>
      <p class="text-muted" style="margin-top: 0.75rem;">
        You are still logged in with the same member account. Trainer mode is only a workspace capability granted after admin approves your CV and certificate.
      </p>

      <div class="panel" style="background: var(--color-cream); margin-top: 1rem;">
        <p class="stat-label">Current account</p>
        <p class="title-md">{{ auth.user?.full_name }}</p>
        <p class="text-muted">{{ auth.user?.email }}</p>
      </div>

      <div style="margin-top: 1rem;">
        <FitnezButton type="button" @click="switchToMember">
          Switch to Member Workspace
        </FitnezButton>
      </div>
    </FitnezCard>
  </WorkspaceLayout>
</template>
