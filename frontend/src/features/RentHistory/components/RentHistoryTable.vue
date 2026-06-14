<script setup lang="ts">
import { computed } from 'vue'
import SkeletonTable from '@/shared/components/ui/SkeletonTable.vue'
import { useTrainerRentHistoryStore } from '@/features/RentHistory/stores/trainerRentHistoryStore'

const store = useTrainerRentHistoryStore()

let searchDebounce: ReturnType<typeof setTimeout> | null = null

function currency(value?: string | number | null) {
  const numberValue = Number(value || 0)
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(numberValue)
}

function formatDate(value?: string | null) {
  if (!value) return '-'
  return new Date(value).toLocaleDateString('en-US', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

function getStatusLabel(status?: string | null) {
  if (status === 'disbursed' || status === 'paid') return 'Disbursed'
  if (status === 'pending') return 'Pending'
  if (status === 'failed' || status === 'rejected') return 'Failed'
  return status || 'Pending'
}

function getStatusClass(status?: string | null) {
  if (status === 'disbursed' || status === 'paid') {
    return 'bg-green-100 text-green-800 border-green-200'
  }
  if (status === 'pending') {
    return 'bg-amber-100 text-amber-800 border-amber-200'
  }
  return 'bg-red-100 text-red-800 border-red-200'
}

const startBound = computed(() => {
  if (store.total === 0) return 0
  return (store.page - 1) * store.perPage + 1
})

const endBound = computed(() => {
  return Math.min(store.page * store.perPage, store.total)
})

function onSearchInput(value: string) {
  store.search = value
  if (searchDebounce) clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => store.refreshTable(), 300)
}
</script>

<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col mt-6 p-6">
    <!-- CARD HEADER -->
    <div class="table-header-block border-b border-gray-100 pb-4 mb-4">
      <h2 class="table-title">Earnings History Records</h2>
      <div class="filter-controls-row">
        <div class="search-input-field">
          <span class="material-symbols-outlined prefix-search-icon">search</span>
          <input
            v-model="store.search"
            type="text"
            class="form-input search-box"
            style="padding-left: 2.75rem !important;"
            placeholder="Search transactions..."
            @input="onSearchInput(($event.target as HTMLInputElement).value)"
          />
        </div>
        <button class="filter-submit-btn" type="button" @click="store.refresh">
          <span class="material-symbols-outlined">filter_list</span>
        </button>
      </div>
    </div>

    <!-- EXTENDED DATE/STATUS FILTERS -->
    <div class="filter-inputs-grid mb-6">
      <label class="filter-input-label">
        <span>Disbursement Status</span>
        <select v-model="store.status" class="form-input" @change="store.refresh">
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="disbursed">Disbursed</option>
          <option value="failed">Failed</option>
        </select>
      </label>
      <label class="filter-input-label">
        <span>Start Date</span>
        <input v-model="store.startDate" type="date" class="form-input" @change="store.refresh" />
      </label>
      <label class="filter-input-label">
        <span>End Date</span>
        <input v-model="store.endDate" type="date" class="form-input" @change="store.refresh" />
      </label>
    </div>

    <!-- RESPONSIVE TABLE -->
    <div class="table-responsive-wrapper">
      <SkeletonTable v-if="store.loading && store.items.length === 0" :columns="6" :rows="8" />
      <table v-else class="riwayat-table">
        <thead>
          <tr>
            <th>Transaction ID</th>
            <th>Date</th>
            <th>Client</th>
            <th>Program / Session</th>
            <th>Commission %</th>
            <th>Net Amount</th>
            <th class="text-center">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in store.items" :key="row.id">
            <td class="trx-id">{{ row.payment?.invoice_number || `#TRX-${row.id}` }}</td>
            <td>{{ formatDate(row.payment?.payment_date || row.booking?.start_date) }}</td>
            <td>
              <div class="client-cell">
                <div class="client-avatar">
                  {{ (row.booking?.member?.full_name || row.payment?.user?.full_name || 'U').charAt(0).toUpperCase() }}
                </div>
                <div class="client-copy">
                  <strong class="client-name">{{ row.booking?.member?.full_name || row.payment?.user?.full_name || '-' }}</strong>
                  <span class="client-email">{{ row.booking?.member?.email || row.payment?.user?.email || '-' }}</span>
                </div>
              </div>
            </td>
            <td>
              <strong class="session-name">{{ row.booking ? `${row.booking.sessions_per_week || '-'} sessions/week` : 'Personal Training' }}</strong>
              <span class="session-time-sub">{{ row.booking?.session_time ? row.booking.session_time.substring(0, 5) : '' }}</span>
            </td>
            <td class="commission-cell">{{ row.commission_rate }}%</td>
            <td class="amount-cell">{{ currency(row.trainer_amount) }}</td>
            <td class="text-center">
              <span :class="['status-capsule border', getStatusClass(row.status)]">
                {{ getStatusLabel(row.status) }}
              </span>
            </td>
          </tr>
          <tr v-if="!store.items.length && !store.loading">
            <td colspan="7" class="empty-cell">No earnings history records found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- PAGINATION FOOTER -->
    <div class="pagination-footer border-t border-gray-100 pt-4 mt-4">
      <span class="pager-info-text">Showing {{ startBound }}-{{ endBound }} of {{ store.total }} records</span>
      <div class="pager-controls">
        <button class="pager-action-btn" :disabled="store.page <= 1 || store.loading" @click="store.previousPage">
          Previous
        </button>
        <button class="pager-action-btn" :disabled="store.page >= store.lastPage || store.loading" @click="store.nextPage">
          Next
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.table-header-block {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
@media (min-width: 768px) {
  .table-header-block {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }
}
.table-title {
  font-size: 1.15rem;
  font-weight: 900;
  color: #111827;
  margin: 0;
  letter-spacing: -0.02em;
}
.filter-controls-row {
  display: flex;
  gap: 0.5rem;
  width: 100%;
}
@media (min-width: 768px) {
  .filter-controls-row {
    width: auto;
  }
}
.search-input-field {
  position: relative;
  flex: 1;
}
@media (min-width: 768px) {
  .search-input-field {
    width: 16rem;
    flex: none;
  }
}
.prefix-search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  font-size: 1.15rem;
}
.search-box {
  width: 100%;
  padding-left: 2.25rem !important;
}
.filter-submit-btn {
  background-color: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  color: #374151;
  width: 2.5rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
.filter-submit-btn:hover {
  background-color: #f9fafb;
}
.filter-inputs-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 1rem;
  border-bottom: 1px dashed #f3f4f6;
  padding-bottom: 1.25rem;
}
.filter-input-label {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}
.filter-input-label span {
  font-size: 0.75rem;
  font-weight: 800;
  color: #4b5563;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.table-responsive-wrapper {
  overflow-x: auto;
  width: 100%;
}
.riwayat-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
  text-align: left;
}
.riwayat-table th {
  background-color: #f9fafb;
  color: #374151;
  font-weight: 800;
  text-transform: uppercase;
  font-size: 0.7rem;
  letter-spacing: 0.05em;
  padding: 1rem;
  border-bottom: 1px solid #f3f4f6;
}
.riwayat-table td {
  padding: 1rem;
  border-bottom: 1px solid #f9fafb;
  color: #4b5563;
  vertical-align: middle;
}
.riwayat-table tbody tr:hover td {
  background-color: #f9fafb;
}
.trx-id {
  font-family: monospace;
  font-weight: 800;
  color: #111827;
}
.client-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.client-avatar {
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 999px;
  background-color: #e5e7eb;
  color: #374151;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 0.85rem;
  flex-shrink: 0;
}
.client-copy {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.client-name {
  color: #111827;
  font-weight: 800;
  font-size: 0.88rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.client-email {
  font-size: 0.75rem;
  color: #9ca3af;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.session-name {
  display: block;
  color: #111827;
  font-weight: 800;
}
.session-time-sub {
  font-size: 0.75rem;
  color: #9ca3af;
}
.commission-cell {
  font-weight: 800;
  color: #6b7280;
}
.amount-cell {
  font-weight: 900;
  color: #111827;
  font-size: 0.9rem;
}
.status-capsule {
  display: inline-flex;
  align-items: center;
  font-size: 0.72rem;
  font-weight: 900;
  padding: 0.3rem 0.6rem;
  border-radius: 999px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  line-height: 1;
}
.empty-cell {
  text-align: center;
  color: #9ca3af;
  font-weight: 700;
  padding: 3rem 0;
}
.pagination-footer {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  align-items: center;
}
@media (min-width: 768px) {
  .pagination-footer {
    flex-direction: row;
    justify-content: space-between;
  }
}
.pager-info-text {
  font-size: 0.82rem;
  color: #6b7280;
  font-weight: 700;
}
.pager-controls {
  display: flex;
  gap: 0.35rem;
  width: 100%;
}
@media (min-width: 768px) {
  .pager-controls {
    width: auto;
  }
}
.pager-action-btn {
  flex: 1;
  background-color: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  color: #4b5563;
  font-weight: 800;
  font-size: 0.8rem;
  padding: 0.45rem 1rem;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  text-align: center;
}
@media (min-width: 768px) {
  .pager-action-btn {
    flex: none;
  }
}
.pager-action-btn:hover:not(:disabled) {
  background-color: #f9fafb;
  border-color: #d1d5db;
}
.pager-action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.text-center { text-align: center; }
</style>
