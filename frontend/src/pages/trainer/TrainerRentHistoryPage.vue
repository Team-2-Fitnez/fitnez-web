<script setup lang="ts">
import { onMounted, onUnmounted, computed } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { trainerSidebarItems } from '../../components/layout/sidebarItems'
import RentMetricCard from '../../components/trainer/RentMetricCard.vue'
import RentBreakdown from '../../components/trainer/RentBreakdown.vue'
import RentHistoryTable from '../../components/trainer/RentHistoryTable.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'
import { useTrainerRentHistoryStore } from '../../stores/trainerRentHistoryStore'
import { useAutoRefresh } from '../../composables/useAutoRefresh'

const store = useTrainerRentHistoryStore()

function currency(value?: string | number | null) {
  const numberValue = Number(value || 0)
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(numberValue)
}

const { loading: initialLoading, run, shimmerStyle } = useDeferredLoading()

async function refreshSummaryOnly() {
  await Promise.all([store.loadSummary(), store.loadBreakdown()])
}

onMounted(() => {
  run(async () => {
    await Promise.all([store.loadSummary(), store.loadBreakdown(), store.load()])
  })
})
useAutoRefresh(refreshSummaryOnly, 8000)

onUnmounted(() => {
  // cleanup handled by useAutoRefresh
})
</script>

<template>
  <WorkspaceLayout
    role="trainer"
    sidebar-title="Trainer"
    title="Earnings History"
    subtitle="Track commission history, disbursement status, and client transaction records."
    :sidebar-items="trainerSidebarItems"
  >
    <div class="trainer-report-page">
      <!-- TOP METRIC CARDS ROW -->
      <section class="metrics-row">
        <RentMetricCard
          label="Total Transactions"
          :value="String(store.summary?.total_records || 0)"
          icon="receipt_long"
          icon-bg-class="bg-sky"
          icon-text-class="text-sky-dark"
        />
        <RentMetricCard
          label="Total Earnings"
          :value="currency(store.summary?.total_trainer_amount || 0)"
          icon="payments"
          icon-bg-class="bg-purple"
          icon-text-class="text-purple-dark"
        />
        <RentMetricCard
          label="Pending Disbursement"
          :value="currency(store.summary?.pending_amount || 0)"
          icon="history"
          icon-bg-class="bg-orange"
          icon-text-class="text-orange-dark"
        />
        <RentMetricCard
          label="Disbursed"
          :value="currency(store.summary?.disbursed_amount || 0)"
          icon="done_all"
          icon-bg-class="bg-green"
          icon-text-class="text-green-dark"
        />
      </section>

      <RentBreakdown v-if="store.breakdown" :breakdown="store.breakdown" />
      <RentHistoryTable />
    </div>
  </WorkspaceLayout>
</template>

<style scoped>
.material-symbols-outlined {
  direction: ltr;
  display: inline-block;
  flex: 0 0 auto;
  font-family: 'Material Symbols Outlined';
  font-feature-settings: 'liga';
  font-size: 1.25rem;
  font-style: normal;
  font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
  font-weight: normal;
  letter-spacing: normal;
  line-height: 1;
  text-transform: none;
  white-space: nowrap;
  width: 1em;
  word-wrap: normal;
  -webkit-font-feature-settings: 'liga';
  -webkit-font-smoothing: antialiased;
}
.trainer-report-page {
  font-family: 'Outfit', sans-serif !important;
  color: #1f2937;
  display: flex;
  flex-direction: column;
}
.metrics-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
}
.bg-sky { background-color: #e0f2fe; }
.text-sky-dark { color: #0284c7; }
.bg-purple { background-color: #f3e8ff; }
.text-purple-dark { color: #9333ea; }
.bg-orange { background-color: #ffedd5; }
.text-orange-dark { color: #ea580c; }
.bg-green { background-color: #dcfce7; }
.text-green-dark { color: #16a34a; }
</style>
