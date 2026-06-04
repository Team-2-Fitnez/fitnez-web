<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import RoleLayout from '../../components/layout/RoleLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import StatCard from '../../components/ui/StatCard.vue'
import { http } from '../../api/http'
import SkeletonStatGrid from '../../components/ui/skeleton/SkeletonStatGrid.vue'
import SkeletonTable from '../../components/ui/SkeletonTable.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'

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
</script>

<template>
  <RoleLayout role="admin" sidebar-title="Admin" title="Classes" subtitle="Monitor active classes, trainers, schedules, capacity, and participants." :sidebar-items="adminSidebarItems">
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

        <div v-else class="mt-4 overflow-x-auto">
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
              <tr v-for="item in classes" :key="item.id" class="border-t border-black/5">
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
      </FitnezCard>
      </template>
    </template>
  </RoleLayout>
</template>
