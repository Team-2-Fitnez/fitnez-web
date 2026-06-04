<script setup lang="ts">
import { onMounted } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import { useProspectiveMemberStore } from '../../stores/prospectiveMemberStore'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import FitnezButton from '../../components/ui/FitnezButton.vue'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import SkeletonTable from '../../components/ui/SkeletonTable.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'

const store = useProspectiveMemberStore()
const { loading, run, shimmerStyle } = useDeferredLoading()

onMounted(() => run(() => store.load()))

function reject(id: number) {
  const reason = window.prompt('Reason for rejection?')
  if (reason) store.reject(id, reason)
}
</script>

<template>
  <WorkspaceLayout role="admin" sidebar-title="Admin" title="Payment Review" subtitle="Approve or reject prospective member manual payment proofs." :sidebar-items="adminSidebarItems">
    <div v-if="loading && !store.items.length" :style="shimmerStyle">
      <SkeletonTable :columns="6" :rows="8" />
    </div>
    <FitnezCard v-else>
      <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem; align-items: center; margin-bottom: 1.25rem;">
        <div>
          <h2 class="title-md">Review queue</h2>
          <p class="text-muted">Verify amount, payment method, and proof screenshot before approval.</p>
        </div>

        <select v-model="store.status" class="form-input" style="width: auto; min-width: 220px;" @change="store.load()">
          <option value="awaiting_admin_review">Awaiting Review</option>
          <option value="awaiting_payment">Awaiting Payment</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
          <option value="">All Statuses</option>
        </select>
      </div>

      <div v-if="!store.items.length && store.status === 'awaiting_admin_review'" class="p-8 text-center bg-blue-50 rounded-xl mb-6">
        <p class="text-blue-600 font-medium">No new payments awaiting review.</p>
        <p class="text-xs text-blue-400 mt-1">Use the filters above to see registrations that are approved or awaiting payment.</p>
      </div>

      <div class="data-table-wrapper">
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
                  <FitnezButton size="sm" variant="danger" @click="reject(row.id)">Reject</FitnezButton>
                </div>
              </td>
            </tr>

            <tr v-if="!store.items.length">
              <td colspan="6" style="padding-block: 4rem; text-align: center; font-weight: 800; color: var(--color-muted);">
                <div class="text-4xl mb-4">📂</div>
                No registration data found for this filter.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </FitnezCard>
  </WorkspaceLayout>
</template>
