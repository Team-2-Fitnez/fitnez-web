<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import WorkspaceLayout from '@/shared/components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '@/shared/components/layout/sidebarItems'
import { useLandingVisitStore } from '@/features/Landing/stores/landingVisitStore'
import FitnezCard from '@/shared/components/ui/FitnezCard.vue'
import StatCard from '@/shared/components/ui/StatCard.vue'
import SkeletonStatGrid from '@/shared/components/ui/skeleton/SkeletonStatGrid.vue'
import SkeletonTable from '@/shared/components/ui/SkeletonTable.vue'
import { useDeferredLoading } from '@/shared/composables/useDeferredLoading'

const store = useLandingVisitStore()
const pollReady = ref(false)
const { loading, run, shimmerStyle } = useDeferredLoading()
let pollTimer: ReturnType<typeof window.setInterval> | null = null

onMounted(() => {
  run(async () => {
    await store.loadSummary()
    await store.load()
    pollReady.value = true
  })

  pollTimer = window.setInterval(() => {
    if (!pollReady.value) return
    store.loadSummary()
    store.load()
  }, 30000)
})

onBeforeUnmount(() => {
  if (pollTimer) {
    window.clearInterval(pollTimer)
    pollTimer = null
  }
})

const visiblePages = computed(() => {
  const last = Number(store.lastPage)
  const current = Number(store.page)
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
  if (p < 1 || p > store.lastPage || p === store.page) return
  store.page = p
  store.load()
}

function formatNumber(value?: number | null) {
  return Number(value || 0).toLocaleString('id-ID')
}

function formatLastSeen(value?: string | null) {
  if (!value) return '-'

  const normalized = value.replace(/\.(\d{3})\d+(Z|[+-]\d{2}:?\d{2})$/, '.$1$2')
  const date = new Date(normalized)

  if (Number.isNaN(date.getTime())) {
    return '-'
  }

  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}
</script>

<template>
  <WorkspaceLayout role="admin" sidebar-title="Admin" title="Landing Visitors" subtitle="Monitor visitors opening the landing page." :sidebar-items="adminSidebarItems">
    <div v-if="loading && !store.items.length" :style="shimmerStyle">
      <SkeletonStatGrid :count="4" />
      <div style="margin-top: 1.25rem"><SkeletonTable :columns="7" :rows="8" /></div>
    </div>
    <template v-else>
    <div class="landing-visitors-page">
      <div class="stat-grid">
        <StatCard label="Active Now" :value="formatNumber(store.summary?.active_visitors_now)" hint="Last 60 seconds" />
        <StatCard label="Unique Today" :value="formatNumber(store.summary?.unique_visitors_today)" hint="One visitor/day" />
        <StatCard label="Page Views" :value="formatNumber(store.summary?.total_page_views)" hint="Refresh included" />
        <StatCard label="Records" :value="formatNumber(store.summary?.total_visit_rows)" hint="Database rows" />
      </div>

      <FitnezCard>
        <div class="table-header">
          <div>
            <h2 class="title-md">Visitor Records</h2>
            <p class="text-muted">
              Private/incognito detection is best-effort. Browser privacy rules can limit exact detection.
            </p>
          </div>
        </div>

        <div class="data-table-wrapper">
          <table class="data-table visitor-table">
            <thead>
              <tr>
                <th>Visitor</th>
                <th>Browser</th>
                <th>Context</th>
                <th>Device</th>
                <th>IP</th>
                <th>Views</th>
                <th>Last Seen</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="row in store.items" :key="row.id">
                <td class="visitor-id">{{ row.visitor_uuid }}</td>
                <td>
                  <p class="cell-primary">{{ row.browser_name || 'Unknown browser' }}</p>
                  <p class="cell-secondary">{{ row.client_browser_engine || 'Unknown engine' }}</p>
                </td>
                <td>
                  <p class="cell-primary">{{ row.browser_context_label || 'Detection limited' }}</p>
                  <p class="cell-secondary">{{ row.private_mode_confidence || 'unknown confidence' }}</p>
                </td>
                <td>{{ row.device_type || '-' }}</td>
                <td>{{ row.ip_address || '-' }}</td>
                <td class="views-cell">{{ formatNumber(row.page_view_count) }}</td>
                <td class="last-seen-cell">{{ formatLastSeen(row.last_seen_at) }}</td>
              </tr>

              <tr v-if="!store.items.length">
                <td colspan="7" class="empty-cell">No visitor data yet. Open the landing page to generate tracking data.</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="pager-bar">
          <p>Page {{ store.page }} of {{ store.lastPage }}</p>
          <div class="pagination">
            <button type="button" class="pagination-arrow" :disabled="store.page <= 1" @click="goToPage(store.page - 1)">‹</button>
            <button
              v-for="p in visiblePages"
              :key="p"
              :class="{ active: Number(p) === Number(store.page), disabled: p === '...' }"
              :disabled="p === '...'"
              type="button"
              @click="goToPage(p)"
            >
              {{ p }}
            </button>
            <button type="button" class="pagination-arrow" :disabled="store.page >= store.lastPage" @click="goToPage(store.page + 1)">›</button>
          </div>
        </div>
      </FitnezCard>
    </div>
    </template>
  </WorkspaceLayout>
</template>

<style scoped>
.landing-visitors-page {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.stat-grid {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.table-header {
  align-items: flex-start;
  display: flex;
  justify-content: space-between;
  margin-bottom: 1.25rem;
}

.visitor-table {
  min-width: 980px;
}

.visitor-id,
.cell-primary,
.views-cell {
  font-weight: 900;
}

.cell-secondary {
  color: var(--color-muted);
  font-size: 0.75rem;
  font-weight: 700;
  margin: 0.15rem 0 0;
}

.last-seen-cell {
  font-weight: 800;
  white-space: nowrap;
}

.empty-cell {
  color: var(--color-muted);
  font-weight: 800;
  padding-block: 2.5rem;
  text-align: center;
}

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

@media (max-width: 1024px) {
  .stat-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 640px) {
  .stat-grid {
    grid-template-columns: 1fr;
  }

  .pager-bar {
    align-items: stretch;
    flex-direction: column;
    gap: 0.75rem;
  }

  .pagination {
    flex-wrap: wrap;
  }
}
</style>
