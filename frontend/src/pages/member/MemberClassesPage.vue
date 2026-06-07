<script setup lang="ts">
import { onMounted, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import { http } from '../../api/http'
import SkeletonCard from '../../components/ui/SkeletonCard.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'
import { useAutoRefresh } from '../../composables/useAutoRefresh'

interface ClassItem {
  id: number
  name: string
  description: string
  trainer_name: string
  schedule: string
  day_of_week: string
  start_time: string
  end_time: string
  max_participants: number
  current_participants: number
  status: string
}

const classes = ref<ClassItem[]>([])
const { loading, run, shimmerStyle } = useDeferredLoading()

const dayNames: Record<string, string> = {
  '1': 'Monday', '2': 'Tuesday', '3': 'Wednesday', '4': 'Thursday', '5': 'Friday', '6': 'Saturday', '7': 'Sunday',
  Monday: 'Monday', Tuesday: 'Tuesday', Wednesday: 'Wednesday', Thursday: 'Thursday', Friday: 'Friday', Saturday: 'Saturday', Sunday: 'Sunday',
}

async function loadClasses() {
  try {
    const res = await http.get<ClassItem[]>('/member/classes')
    classes.value = res.data || []
  } catch {
    window.showFitnezToast('Failed to load class schedules.', 'error')
  }
}

async function joinClass(classId: number) {
  try {
    await http.post(`/member/classes/${classId}/join`, {})
    window.showFitnezToast('Successfully joined class!', 'success')
    await loadClasses()
  } catch (e: any) {
    window.showFitnezToast(e?.message || 'Failed to join class.', 'error')
  }
}

onMounted(() => run(loadClasses))
useAutoRefresh(loadClasses, 10000)
</script>

<template>
  <WorkspaceLayout
    role="member"
    sidebar-title="Member"
    title="Classes"
    subtitle="List of available fitness classes."
    :sidebar-items="memberSidebarItems"
  >
    <div v-if="loading && !classes.length" :style="shimmerStyle" style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
      <SkeletonCard v-for="n in 4" :key="n" heading :lines="2" actions />
    </div>

    <div v-else-if="!classes.length" class="panel" style="background: var(--color-cream); text-align: center; padding: 3rem 2rem;">
      <p style="font-weight: 700;">No classes available yet</p>
      <p class="text-muted" style="margin-top: 0.25rem;">Please check back later or contact admin.</p>
    </div>

    <div v-else style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
      <FitnezCard v-for="cls in classes" :key="cls.id" style="display: flex; flex-direction: column;">
        <div style="flex: 1;">
          <span style="font-size: 0.75rem; font-weight: 700; color: var(--color-primary); text-transform: uppercase;">{{ dayNames[cls.day_of_week] || cls.day_of_week }}</span>
          <h3 style="font-size: 1.1rem; font-weight: 900; margin: 0.25rem 0;">{{ cls.name }}</h3>
          <p class="text-muted" style="font-size: 0.9rem; line-height: 1.5;">{{ cls.description || '-' }}</p>
          <div style="margin-top: 0.75rem; display: grid; gap: 0.25rem; font-size: 0.85rem;">
            <p><strong>Trainer:</strong> {{ cls.trainer_name }}</p>
            <p><strong>Time:</strong> {{ cls.start_time }} - {{ cls.end_time }}</p>
            <p><strong>Participants:</strong> {{ cls.current_participants }} / {{ cls.max_participants }}</p>
          </div>
        </div>
        <button
          class="button button-primary"
          style="margin-top: 1rem; width: 100%;"
          :disabled="cls.current_participants >= cls.max_participants"
          @click="joinClass(cls.id)"
        >
          {{ cls.current_participants >= cls.max_participants ? 'Full' : 'Join Class' }}
        </button>
      </FitnezCard>
    </div>
  </WorkspaceLayout>
</template>
