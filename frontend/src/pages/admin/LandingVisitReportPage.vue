<script setup lang="ts">
import { onMounted, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import { useLandingVisitStore } from '../../stores/landingVisitStore'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import StatCard from '../../components/ui/StatCard.vue'
import ExcelImportModal from '../../components/ExcelImportModal.vue'
import { http } from '../../api/http'
import SkeletonStatGrid from '../../components/ui/skeleton/SkeletonStatGrid.vue'
import SkeletonTable from '../../components/ui/SkeletonTable.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'

const store = useLandingVisitStore()
const pollReady = ref(false)
const { loading, run, shimmerStyle } = useDeferredLoading()

onMounted(() => {
  run(async () => {
    await store.loadSummary()
    await store.load()
    pollReady.value = true
  })

  window.setInterval(() => {
    if (!pollReady.value) return
    store.loadSummary()
    store.load()
  }, 30000)
})
</script>

<template>
  <WorkspaceLayout role="admin" sidebar-title="Admin" title="Landing Visitors" subtitle="Monitor visitors opening the landing page." :sidebar-items="adminSidebarItems">
    <div v-if="loading && !store.items.length" :style="shimmerStyle">
      <SkeletonStatGrid :count="4" />
      <div style="margin-top: 1.25rem"><SkeletonTable :columns="7" :rows="8" /></div>
    </div>
    <template v-else>
    <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-bottom: 0.75rem;">
      <a :href="http.url('/admin/export/landing-visits')" class="button button-ghost" style="font-size: 0.85rem; text-decoration: none;">Export Excel</a>
      <ExcelImportModal import-type="schedules" label="Import Excel" />
    </div>
    <div class="feature-grid">
      <StatCard label="Active Now" :value="store.summary?.active_visitors_now || 0" hint="Last 60 seconds" />
      <StatCard label="Unique Today" :value="store.summary?.unique_visitors_today || 0" hint="One visitor/day" />
      <StatCard label="Page Views" :value="store.summary?.total_page_views || 0" hint="Refresh included" />
      <StatCard label="Records" :value="store.summary?.total_visit_rows || 0" hint="Database rows" />
    </div>

    <FitnezCard style="margin-top: 1.25rem;">
      <div style="margin-bottom: 1.25rem;">
        <h2 class="title-md">Visitor records</h2>
        <p class="text-muted">
          Private/incognito detection is best-effort. Browser privacy rules can limit exact detection.
        </p>
      </div>

      <div class="data-table-wrapper">
        <table class="data-table" style="min-width: 1100px;">
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
              <td style="font-weight: 900;">{{ row.visitor_uuid }}</td>
              <td>
                <p style="font-weight: 900;">{{ row.browser_name }}</p>
                <p class="text-muted" style="font-size: 0.75rem;">{{ row.client_browser_engine || 'Unknown engine' }}</p>
              </td>
              <td>
                <p style="font-weight: 800;">{{ row.browser_context_label || 'Detection limited' }}</p>
                <p class="text-muted" style="font-size: 0.75rem;">{{ row.private_mode_confidence || 'unknown confidence' }}</p>
              </td>
              <td>{{ row.device_type }}</td>
              <td>{{ row.ip_address }}</td>
              <td style="font-weight: 900;">{{ row.page_view_count }}</td>
              <td>{{ row.last_seen_at }}</td>
            </tr>

            <tr v-if="!store.items.length">
              <td colspan="7" style="padding-block: 2.5rem; text-align: center; font-weight: 800; color: var(--color-muted);">No visitor data yet. Open the landing page to generate tracking data.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </FitnezCard>
    </template>
  </WorkspaceLayout>
</template>
