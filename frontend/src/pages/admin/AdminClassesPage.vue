<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import StatCard from '../../components/ui/StatCard.vue'
import { http } from '../../api/http'
import SkeletonStatGrid from '../../components/ui/skeleton/SkeletonStatGrid.vue'
import SkeletonTable from '../../components/ui/SkeletonTable.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'
import { useAutoRefresh } from '../../composables/useAutoRefresh'

type ClassItem = {
  id: number
  name: string
  description: string | null
  trainer_name: string
  day_of_week: string
  start_time: string
  end_time: string
  max_participants: number
  current_participants: number
  status: string
}

const classes = ref<ClassItem[]>([])
const { loading, run, shimmerStyle } = useDeferredLoading()
const error = ref('')

const currentPage = ref(1)
const perPage = 15

const paginatedClasses = computed(() => {
  const start = (currentPage.value - 1) * perPage
  const end = start + perPage
  return classes.value.slice(start, end)
})

const lastPage = computed(() => Math.ceil(classes.value.length / perPage) || 1)

const visiblePages = computed(() => {
  const last = Number(lastPage.value)
  const current = Number(currentPage.value)
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
  if (p < 1 || p > lastPage.value || p === currentPage.value) return
  currentPage.value = p
}

const activeClasses = computed(() => classes.value.filter(item => item.status === 'active').length)
const totalCapacity = computed(() => classes.value.reduce((sum, item) => sum + Number(item.max_participants || 0), 0))
const totalParticipants = computed(() => classes.value.reduce((sum, item) => sum + Number(item.current_participants || 0), 0))

async function loadClasses() {
  error.value = ''
  try {
    const response = await http.get<ClassItem[]>('/member/classes')
    classes.value = response.data || []
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load classes.'
  }
}

onMounted(() => run(loadClasses))
useAutoRefresh(loadClasses, 10000)
</script>

<template>
  <WorkspaceLayout role="admin" sidebar-title="Admin" title="Classes" subtitle="Monitor active classes, trainers, schedules, capacity, and participants." :sidebar-items="adminSidebarItems">
    <template #default>
      <div v-if="loading && !classes.length" :style="shimmerStyle">
        <SkeletonStatGrid :count="3" />
        <div class="mt-5"><SkeletonTable :columns="6" :rows="6" /></div>
      </div>
      <template v-else>
      <div class="grid gap-4 md:grid-cols-3">
        <StatCard label="Active Classes" :value="activeClasses" hint="schedules available" />
        <StatCard label="Registered Participants" :value="totalParticipants" hint="from all classes" />
        <StatCard label="Total Capacity" :value="totalCapacity" hint="class slots" />
      </div>

      <FitnezCard class="mt-5">
        <div class="flex flex-col gap-3 border-b border-black/10 pb-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-2xl font-black">Class List</h2>
            <p class="mt-1 text-sm font-semibold text-black/50">Active class data is retrieved from the class and participant registration database.</p>
          </div>
          <button class="rounded-xl border border-black/10 px-4 py-2 text-sm font-black text-black/70 hover:bg-black/5" type="button" @click="loadClasses">
            Reload
          </button>
        </div>

        <p v-if="error" class="mt-4 rounded-2xl bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ error }}</p>
        <p v-else-if="!classes.length" class="py-10 text-center text-sm font-bold text-black/45">No active classes in the database.</p>

        <div v-else class="fitnez-desktop-only mt-4 overflow-x-auto">
          <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th class="px-4 py-3">Class</th>
                <th class="px-4 py-3">Trainer</th>
                <th class="px-4 py-3">Day</th>
                <th class="px-4 py-3">Time</th>
                <th class="px-4 py-3">Participants</th>
                <th class="px-4 py-3">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in paginatedClasses" :key="item.id" class="border-t border-black/5">
                <td class="px-4 py-4">
                  <p class="font-black text-black">{{ item.name }}</p>
                  <p class="mt-1 max-w-md text-xs font-semibold text-black/45">{{ item.description || 'No class description.' }}</p>
                </td>
                <td class="px-4 py-4 font-bold">{{ item.trainer_name || '-' }}</td>
                <td class="px-4 py-4 font-bold">{{ item.day_of_week }}</td>
                <td class="px-4 py-4 font-bold">{{ item.start_time }} - {{ item.end_time }}</td>
                <td class="px-4 py-4 font-black">{{ item.current_participants }} / {{ item.max_participants }}</td>
                <td class="px-4 py-4">
                  <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700">{{ item.status }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="classes.length" class="mobile-record-list mt-4">
          <article v-for="item in paginatedClasses" :key="`class-card-${item.id}`" class="mobile-record-card">
            <div class="mobile-record-head">
              <div>
                <strong>{{ item.name }}</strong>
                <p>{{ item.description || 'No class description.' }}</p>
              </div>
              <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700">{{ item.status }}</span>
            </div>
            <dl class="mobile-detail-grid">
              <div>
                <dt>Trainer</dt>
                <dd>{{ item.trainer_name || '-' }}</dd>
              </div>
              <div>
                <dt>Day</dt>
                <dd>{{ item.day_of_week }}</dd>
              </div>
              <div>
                <dt>Time</dt>
                <dd>{{ item.start_time }} - {{ item.end_time }}</dd>
              </div>
              <div>
                <dt>Participants</dt>
                <dd>{{ item.current_participants }} / {{ item.max_participants }}</dd>
              </div>
            </dl>
          </article>
        </div>
        <div class="pager-bar">
          <p>Page {{ currentPage }} of {{ lastPage }}</p>
          <div class="pagination">
            <button type="button" class="pagination-arrow" :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)">‹</button>
            <button
              v-for="p in visiblePages"
              :key="p"
              :class="{ active: Number(p) === Number(currentPage), disabled: p === '...' }"
              :disabled="p === '...'"
              type="button"
              @click="goToPage(p)"
            >
              {{ p }}
            </button>
            <button type="button" class="pagination-arrow" :disabled="currentPage >= lastPage" @click="goToPage(currentPage + 1)">›</button>
          </div>
        </div>
      </FitnezCard>
      </template>
    </template>
  </WorkspaceLayout>
</template>

<style scoped>
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
