<script setup lang="ts">
import { onMounted, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import { http } from '../../api/http'

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
const loading = ref(false)

const dayNames: Record<string, string> = {
  '1': 'Senin', '2': 'Selasa', '3': 'Rabu', '4': 'Kamis', '5': 'Jumat', '6': 'Sabtu', '7': 'Minggu',
  Monday: 'Senin', Tuesday: 'Selasa', Wednesday: 'Rabu', Thursday: 'Kamis', Friday: 'Jumat', Saturday: 'Sabtu', Sunday: 'Minggu',
}

async function loadClasses() {
  loading.value = true
  try {
    const res = await http.get('/member/classes')
    classes.value = res.data || []
  } catch {
    window.showFitnezToast('Gagal memuat jadwal kelas.', 'error')
  } finally {
    loading.value = false
  }
}

async function joinClass(classId: number) {
  try {
    await http.post(`/member/classes/${classId}/join`, {})
    window.showFitnezToast('Berhasil mendaftar kelas!', 'success')
    await loadClasses()
  } catch (e: any) {
    window.showFitnezToast(e?.message || 'Gagal mendaftar kelas.', 'error')
  }
}

onMounted(loadClasses)
</script>

<template>
  <WorkspaceLayout
    role="member"
    sidebar-title="Member"
    title="Classes"
    subtitle="Daftar kelas fitness yang tersedia."
    :sidebar-items="memberSidebarItems"
  >
    <div v-if="loading" style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
      <div v-for="n in 4" :key="n" class="skeleton-block" style="height: 160px; border-radius: 12px;"></div>
    </div>

    <div v-else-if="!classes.length" class="panel" style="background: var(--color-cream); text-align: center; padding: 3rem 2rem;">
      <p style="font-weight: 700;">Belum ada kelas tersedia</p>
      <p class="text-muted" style="margin-top: 0.25rem;">Silakan cek kembali nanti atau hubungi admin.</p>
    </div>

    <div v-else style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
      <FitnezCard v-for="cls in classes" :key="cls.id" style="display: flex; flex-direction: column;">
        <div style="flex: 1;">
          <span style="font-size: 0.75rem; font-weight: 700; color: var(--color-primary); text-transform: uppercase;">{{ dayNames[cls.day_of_week] || cls.day_of_week }}</span>
          <h3 style="font-size: 1.1rem; font-weight: 900; margin: 0.25rem 0;">{{ cls.name }}</h3>
          <p class="text-muted" style="font-size: 0.9rem; line-height: 1.5;">{{ cls.description || '-' }}</p>
          <div style="margin-top: 0.75rem; display: grid; gap: 0.25rem; font-size: 0.85rem;">
            <p><strong>Trainer:</strong> {{ cls.trainer_name }}</p>
            <p><strong>Waktu:</strong> {{ cls.start_time }} - {{ cls.end_time }}</p>
            <p><strong>Peserta:</strong> {{ cls.current_participants }} / {{ cls.max_participants }}</p>
          </div>
        </div>
        <button
          class="button button-primary"
          style="margin-top: 1rem; width: 100%;"
          :disabled="cls.current_participants >= cls.max_participants"
          @click="joinClass(cls.id)"
        >
          {{ cls.current_participants >= cls.max_participants ? 'Penuh' : 'Daftar Kelas' }}
        </button>
      </FitnezCard>
    </div>
  </WorkspaceLayout>
</template>
