<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import { useBookingStore } from '../../stores/bookingStore'
import type { PublicTrainer } from '../../api/bookingsApi'

const store = useBookingStore()
const search = ref('')
const selectedTrainer = ref<PublicTrainer | null>(null)

const filteredTrainers = computed(() => {
  const q = search.value.toLowerCase()
  if (!q) return store.trainers
  return store.trainers.filter(
    (t) =>
      t.name.toLowerCase().includes(q) ||
      t.spec.toLowerCase().includes(q),
  )
})

function getCategories(spec: string): string[] {
  if (!spec) return ['General Fitness']
  
  const categories: string[] = []
  const specLower = spec.toLowerCase()
  
  // Mapping berdasarkan keyword di spesialisasi
  const categoryMap = {
    'Yoga': ['yoga', 'meditation', 'flexibility'],
    'Aerobics': ['aerobic', 'cardio', 'zumba', 'dance'],
    'Strength Training': ['strength', 'weight', 'bodybuilding', 'powerlifting', 'resistance'],
    'Mobility': ['mobility', 'stretching', 'recovery'],
    'HIIT': ['hiit', 'high intensity', 'interval'],
    'Functional Fitness': ['functional', 'crossfit', 'athletic', 'sports']
  }
  
  for (const [category, keywords] of Object.entries(categoryMap)) {
    if (keywords.some(keyword => specLower.includes(keyword))) {
      categories.push(category)
    }
  }
  
  // Jika tidak ada kategori yang cocok, tampilkan spesialisasi asli
  return categories.length > 0 ? categories : [spec]
}

function formatPrice(n: number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(n)
}

onMounted(() => store.loadTrainers())
</script>

<template>
  <WorkspaceLayout
    role="member"
    sidebar-title="Member"
    title="Hire a Trainer"
    subtitle="Cari dukungan pelatih profesional untuk target kebugaran Anda."
    :sidebar-items="memberSidebarItems"
  >
    <!-- Search -->
    <div class="card" style="margin-bottom: 1.25rem; padding: 1rem;">
      <input
        v-model="search"
        class="form-input"
        placeholder="Cari trainer berdasarkan nama atau spesialisasi..."
      />
    </div>

    <!-- Loading -->
    <div v-if="store.trainersLoading" class="card" style="padding: 3rem; text-align: center;">
      <p class="text-muted">Memuat daftar trainer...</p>
    </div>

    <!-- Empty -->
    <div v-else-if="filteredTrainers.length === 0" class="card" style="padding: 3rem; text-align: center;">
      <p class="text-muted">Belum ada trainer terdaftar.</p>
    </div>

    <!-- Trainer Grid -->
    <div v-else class="feature-grid">
      <FitnezCard v-for="trainer in filteredTrainers" :key="trainer.id">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
          <div
            style="width: 3rem; height: 3rem; border-radius: 1rem; background: var(--color-blue); display: flex; align-items: center; justify-content: center; color: white; font-weight: 900; font-size: 1.25rem; flex-shrink: 0;"
          >
            {{ trainer.name?.[0]?.toUpperCase() ?? '?' }}
          </div>
          <div style="flex: 1; min-width: 0;">
            <h3 class="title-md" style="font-size: 1.1rem;">{{ trainer.name }}</h3>
            <p class="text-muted" style="font-size: 0.8rem; margin-top: 0.25rem;">
              <span style="display: inline-flex; align-items: center; gap: 0.25rem;">
                <span style="font-weight: 600;"></span>
                {{ trainer.spec || 'General Fitness' }}
              </span>
            </p>
          </div>
        </div>

        <!-- Kategori Keahlian -->
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
          <span
            v-for="category in getCategories(trainer.spec)"
            :key="category"
            style="display: inline-block; padding: 0.25rem 0.75rem; background: var(--color-blue); color: white; border-radius: 1rem; font-size: 0.75rem; font-weight: 600;"
          >
            {{ category }}
          </span>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 1rem;">
          <div class="panel" style="background: var(--color-cream); padding: 0.75rem;">
            <p class="stat-label" style="font-size: 0.7rem; display: flex; align-items: center; gap: 0.25rem;">
              <span></span> Pengalaman
            </p>
            <p style="font-weight: 900; font-size: 1.1rem;">{{ trainer.exp }} tahun</p>
          </div>
          <div class="panel" style="background: var(--color-cream); padding: 0.75rem;">
            <p class="stat-label" style="font-size: 0.7rem; display: flex; align-items: center; gap: 0.25rem;">
              <span>⭐</span> Rating
            </p>
            <p style="font-weight: 900; font-size: 1.1rem;">{{ trainer.rating.toFixed(1) }}/5.0</p>
          </div>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
          <p style="font-weight: 900; color: var(--color-orange);">{{ formatPrice(trainer.price) }}/jam</p>
          <button class="button button-primary button-small" @click="selectedTrainer = trainer">
            Book Sesi
          </button>
        </div>
      </FitnezCard>
    </div>

    <!-- Booking Modal -->
    <Teleport to="body">
      <div v-if="selectedTrainer" class="modal-backdrop" @click.self="selectedTrainer = null">
        <BookingModal :trainer="selectedTrainer" @close="selectedTrainer = null" @booked="selectedTrainer = null" />
      </div>
    </Teleport>
  </WorkspaceLayout>
</template>

<script lang="ts">
import BookingModal from '../../components/bookings/BookingModal.vue'
export default { components: { BookingModal } }
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 50;
  display: grid;
  place-items: center;
  background: rgba(0, 0, 0, 0.45);
  padding: 1rem;
}
</style>
