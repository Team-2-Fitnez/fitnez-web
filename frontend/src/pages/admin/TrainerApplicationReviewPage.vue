<script setup lang="ts">
import { onMounted, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import { trainerApplicationApi, type TrainerApplication } from '../../api/trainerApplicationApi'
import SkeletonTable from '../../components/ui/SkeletonTable.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'

const items = ref<TrainerApplication[]>([])
const { loading: initialLoading, run, shimmerStyle } = useDeferredLoading()
const loading = ref(false)
const search = ref('')
const status = ref('')
const error = ref('')
const message = ref('')

async function load() {
  loading.value = true
  error.value = ''

  try {
    const response = await trainerApplicationApi.adminList({
      search: search.value,
      status: status.value,
      per_page: 20,
    })
    items.value = response.data.data
  } catch (e: any) {
    error.value = e?.message || 'Failed to load trainer applications.'
  } finally {
    loading.value = false
  }
}

async function approve(row: TrainerApplication) {
  error.value = ''
  message.value = ''

  try {
    await trainerApplicationApi.approve(row.id, {
      specialization: 'General Fitness',
      biography: 'Approved Fitnez trainer.',
      experience_years: 0,
      hourly_rate: 0,
      admin_notes: 'Approved by admin.',
    })
    message.value = 'Trainer application approved.'
    await load()
  } catch (e: any) {
    error.value = e?.message || 'Failed to approve application.'
  }
}

async function reject(row: TrainerApplication) {
  const reason = window.prompt('Write rejection reason for this trainer application:')
  if (!reason) return

  error.value = ''
  message.value = ''

  try {
    await trainerApplicationApi.reject(row.id, reason)
    message.value = 'Trainer application rejected.'
    await load()
  } catch (e: any) {
    error.value = e?.message || 'Failed to reject application.'
  }
}

function openDocument(row: TrainerApplication, type: 'cv' | 'certificate') {
  window.open(trainerApplicationApi.documentUrl(row.id, type), '_blank')
}

onMounted(() => run(load))
</script>

<template>
  <WorkspaceLayout
    role="admin"
    sidebar-title="Admin"
    title="Trainer Applications"
    subtitle="Review member CV and certificate PDF files before granting trainer workspace access."
    :sidebar-items="adminSidebarItems"
  >
    <FitnezCard>
      <div style="display: grid; gap: 1rem;">
        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: end; justify-content: space-between;">
          <div>
            <p class="eyebrow">Review Queue</p>
            <h2 class="title-md">Member trainer submissions</h2>
          </div>

          <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
            <input v-model="search" class="input-like" placeholder="Search name or email" @keyup.enter="load" />
            <select v-model="status" class="input-like" @change="load">
              <option value="">All status</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
            <button class="btn-mini" type="button" @click="load">Refresh</button>
          </div>
        </div>

        <p v-if="error" class="alert alert-error">{{ error }}</p>
        <p v-if="message" class="alert alert-info">{{ message }}</p>

        <SkeletonTable v-if="initialLoading && !items.length" :columns="6" :rows="8" :style="shimmerStyle" />

        <div v-else class="data-table-wrapper">
          <table class="data-table" style="min-width: 980px;">
            <thead>
              <tr>
                <th>Member</th>
                <th>Status</th>
                <th>Submitted</th>
                <th>Documents</th>
                <th>Admin Notes</th>
                <th>Action</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="row in items" :key="row.id">
                <td>
                  <p style="font-weight: 900;">{{ row.user?.full_name || 'Unknown member' }}</p>
                  <p class="text-muted" style="font-size: 0.8rem;">{{ row.user?.email }}</p>
                </td>
                <td>
                  <span class="status-pill">{{ row.status }}</span>
                </td>
                <td>{{ row.submitted_at || '-' }}</td>
                <td>
                  <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    <button class="btn-mini" type="button" @click="openDocument(row, 'cv')">Open CV</button>
                    <button class="btn-mini" type="button" @click="openDocument(row, 'certificate')">Open Certificate</button>
                  </div>
                </td>
                <td>{{ row.admin_notes || '-' }}</td>
                <td>
                  <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    <button v-if="row.status !== 'approved'" class="btn-mini" type="button" @click="approve(row)">Approve</button>
                    <button v-if="row.status !== 'rejected'" class="btn-mini danger" type="button" @click="reject(row)">Reject</button>
                  </div>
                </td>
              </tr>

              <tr v-if="!items.length && !loading">
                <td colspan="6" style="padding-block: 2.5rem; text-align: center; font-weight: 800; color: var(--color-muted);">
                  No trainer applications yet.
                </td>
              </tr>

            </tbody>
          </table>
        </div>
      </div>
    </FitnezCard>
  </WorkspaceLayout>
</template>

<style scoped>
.input-like {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 0.9rem;
  padding: 0.75rem 1rem;
  font-weight: 700;
  background: white;
}

.btn-mini {
  border: 0;
  border-radius: 0.9rem;
  padding: 0.7rem 1rem;
  font-weight: 900;
  background: var(--color-blue-dark);
  color: white;
}

.btn-mini.danger {
  background: #991b1b;
}

.status-pill {
  display: inline-flex;
  border-radius: 999px;
  padding: 0.35rem 0.75rem;
  background: var(--color-cream);
  font-size: 0.8rem;
  font-weight: 900;
  text-transform: capitalize;
}
</style>
