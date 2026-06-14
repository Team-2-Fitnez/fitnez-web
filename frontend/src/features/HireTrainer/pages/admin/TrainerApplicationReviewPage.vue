<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import WorkspaceLayout from '@/shared/components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '@/shared/components/layout/sidebarItems'
import FitnezCard from '@/shared/components/ui/FitnezCard.vue'
import { trainerApplicationApi, type TrainerApplication } from '@/features/HireTrainer/api/trainerApplicationApi'
import SkeletonTable from '@/shared/components/ui/SkeletonTable.vue'
import { useDeferredLoading } from '@/shared/composables/useDeferredLoading'
import { useAutoRefresh } from '@/shared/composables/useAutoRefresh'

const items = ref<TrainerApplication[]>([])
const { loading: initialLoading, run, shimmerStyle } = useDeferredLoading()
const loading = ref(false)
const search = ref('')
const status = ref('')
const error = ref('')
const message = ref('')

const showModal = ref(false)
const modalType = ref<'approve' | 'reject'>('approve')
const selectedRow = ref<TrainerApplication | null>(null)
const modalSpecialization = ref('')
const modalBiography = ref('')
const modalExperienceYears = ref(0)
const modalHourlyRate = ref(0)
const modalNotes = ref('')

function openApproveModal(row: TrainerApplication) {
  selectedRow.value = row
  modalType.value = 'approve'
  modalSpecialization.value = ''
  modalBiography.value = ''
  modalExperienceYears.value = 0
  modalHourlyRate.value = 0
  modalNotes.value = ''
  showModal.value = true
}

function openRejectModal(row: TrainerApplication) {
  selectedRow.value = row
  modalType.value = 'reject'
  modalNotes.value = ''
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  selectedRow.value = null
}

async function submitModal() {
  if (!selectedRow.value) return
  error.value = ''
  message.value = ''

  try {
    if (modalType.value === 'approve') {
      await trainerApplicationApi.approve(selectedRow.value.id, {
        specialization: modalSpecialization.value || 'General Fitness',
        biography: modalBiography.value || 'Approved Fitnez trainer.',
        experience_years: modalExperienceYears.value,
        hourly_rate: modalHourlyRate.value,
        admin_notes: modalNotes.value || 'Approved by admin.',
      })
      message.value = 'Trainer application approved.'
    } else {
      if (!modalNotes.value.trim()) return
      await trainerApplicationApi.reject(selectedRow.value.id, modalNotes.value)
      message.value = 'Trainer application rejected.'
    }
    closeModal()
    await load()
  } catch (e: any) {
    error.value = e?.message || 'Failed to process application.'
  }
}

const page = ref(1)
const lastPage = ref(1)
const total = ref(0)

const visiblePages = computed(() => {
  const last = Number(lastPage.value)
  const current = Number(page.value)
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
  if (p < 1 || p > lastPage.value || p === page.value) return
  page.value = p
  load()
}

async function load() {
  loading.value = true
  error.value = ''

  try {
    const response = await trainerApplicationApi.adminList({
      search: search.value,
      status: status.value,
      per_page: 15,
      page: page.value,
    })
    items.value = response.data.data
    page.value = response.data.current_page || 1
    lastPage.value = response.data.last_page || 1
    total.value = response.data.total || 0
  } catch (e: any) {
    error.value = e?.message || 'Failed to load trainer applications.'
  } finally {
    loading.value = false
  }
}

async function openDocument(row: TrainerApplication, type: 'cv' | 'certificate') {
  try {
    await trainerApplicationApi.downloadDocument(row.id, type)
  } catch {
    // Fallback to URL-based download
    window.open(trainerApplicationApi.documentUrl(row.id, type), '_blank')
  }
}

onMounted(() => run(load))
useAutoRefresh(load, 8000)
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
                    <button v-if="row.status !== 'approved'" class="btn-mini" type="button" @click="openApproveModal(row)">Approve</button>
                    <button v-if="row.status !== 'rejected'" class="btn-mini danger" type="button" @click="openRejectModal(row)">Reject</button>
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
        <div class="pager-bar">
          <p>Page {{ page }} of {{ lastPage }}</p>
          <div class="pagination">
            <button type="button" class="pagination-arrow" :disabled="page <= 1" @click="goToPage(page - 1)">‹</button>
            <button
              v-for="p in visiblePages"
              :key="p"
              :class="{ active: Number(p) === Number(page), disabled: p === '...' }"
              :disabled="p === '...'"
              type="button"
              @click="goToPage(p)"
            >
              {{ p }}
            </button>
            <button type="button" class="pagination-arrow" :disabled="page >= lastPage" @click="goToPage(page + 1)">›</button>
          </div>
        </div>
      </div>
    </FitnezCard>

    <!-- Approve/Reject Modal -->
    <div v-if="showModal && selectedRow" class="modal-overlay" @click.self="closeModal">
      <div class="modal-card">
        <h3 class="title-md">{{ modalType === 'approve' ? 'Approve Trainer' : 'Reject Trainer' }}</h3>
        <p class="text-muted text-sm mb-4">{{ selectedRow.user?.full_name || 'Unknown' }}</p>

        <template v-if="modalType === 'approve'">
          <div class="form-field">
            <label class="form-label">Specialization</label>
            <input v-model="modalSpecialization" class="form-input" placeholder="Yoga, Strength Training, etc." />
          </div>
          <div class="form-field">
            <label class="form-label">Biography</label>
            <textarea v-model="modalBiography" class="form-input" placeholder="Short trainer description" rows="2" />
          </div>
          <div class="form-row">
            <div class="form-field flex-1">
              <label class="form-label">Years of Experience</label>
              <input v-model.number="modalExperienceYears" type="number" min="0" class="form-input" />
            </div>
            <div class="form-field flex-1">
              <label class="form-label">Hourly Rate (Rp)</label>
              <input v-model.number="modalHourlyRate" type="number" min="0" class="form-input" />
            </div>
          </div>
          <div class="form-field">
            <label class="form-label">Admin Notes</label>
            <textarea v-model="modalNotes" class="form-input" placeholder="Notes (optional)" rows="2" />
          </div>
        </template>

        <template v-else>
          <div class="form-field">
            <label class="form-label">Rejection Reason</label>
            <textarea v-model="modalNotes" class="form-input" placeholder="Write the rejection reason..." rows="3" required />
          </div>
        </template>

        <div class="form-actions">
          <button class="button button-ghost" type="button" @click="closeModal">Cancel</button>
          <button class="button button-primary" type="button" @click="submitModal" :disabled="modalType === 'reject' && !modalNotes.trim()">
            {{ modalType === 'approve' ? 'Approve' : 'Reject' }}
          </button>
        </div>
      </div>
    </div>
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

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  width: 100%;
  max-width: 480px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.15);
}

.form-field { margin-bottom: 1rem; }
.form-label { display: block; font-weight: 500; margin-bottom: 0.25rem; font-size: 0.85rem; }
.form-input { width: 100%; padding: 0.6rem; border: 1px solid #ddd; border-radius: 8px; font-size: 0.9rem; }
.form-row { display: flex; gap: 1rem; }
.form-actions { display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem; }

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
