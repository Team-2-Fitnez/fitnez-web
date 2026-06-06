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

async function openDocument(row: TrainerApplication, type: 'cv' | 'certificate') {
  try {
    await trainerApplicationApi.downloadDocument(row.id, type)
  } catch {
    // Fallback to URL-based download
    window.open(trainerApplicationApi.documentUrl(row.id, type), '_blank')
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
      </div>
    </FitnezCard>

    <!-- Approve/Reject Modal -->
    <div v-if="showModal && selectedRow" class="modal-overlay" @click.self="closeModal">
      <div class="modal-card">
        <h3 class="title-md">{{ modalType === 'approve' ? 'Setujui Trainer' : 'Tolak Trainer' }}</h3>
        <p class="text-muted text-sm mb-4">{{ selectedRow.user?.full_name || 'Unknown' }}</p>

        <template v-if="modalType === 'approve'">
          <div class="form-field">
            <label class="form-label">Spesialisasi</label>
            <input v-model="modalSpecialization" class="form-input" placeholder="Yoga, Strength Training, dll" />
          </div>
          <div class="form-field">
            <label class="form-label">Biografi</label>
            <textarea v-model="modalBiography" class="form-input" placeholder="Deskripsi singkat tentang trainer" rows="2" />
          </div>
          <div class="form-row">
            <div class="form-field flex-1">
              <label class="form-label">Tahun Pengalaman</label>
              <input v-model.number="modalExperienceYears" type="number" min="0" class="form-input" />
            </div>
            <div class="form-field flex-1">
              <label class="form-label">Tarif per Jam (Rp)</label>
              <input v-model.number="modalHourlyRate" type="number" min="0" class="form-input" />
            </div>
          </div>
          <div class="form-field">
            <label class="form-label">Catatan Admin</label>
            <textarea v-model="modalNotes" class="form-input" placeholder="Catatan (opsional)" rows="2" />
          </div>
        </template>

        <template v-else>
          <div class="form-field">
            <label class="form-label">Alasan Penolakan</label>
            <textarea v-model="modalNotes" class="form-input" placeholder="Tulis alasan penolakan..." rows="3" required />
          </div>
        </template>

        <div class="form-actions">
          <button class="button button-ghost" type="button" @click="closeModal">Batal</button>
          <button class="button button-primary" type="button" @click="submitModal" :disabled="modalType === 'reject' && !modalNotes.trim()">
            {{ modalType === 'approve' ? 'Setujui' : 'Tolak' }}
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
</style>
