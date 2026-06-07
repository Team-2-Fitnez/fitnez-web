<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import SkeletonCard from '../../components/ui/SkeletonCard.vue'
import { useBookingStore } from '../../stores/bookingStore'
import { useDeferredLoading } from '../../composables/useDeferredLoading'
import type { PublicTrainer } from '../../api/bookingsApi'

const store = useBookingStore()
const router = useRouter()
const search = ref('')
const selectedTrainer = ref<PublicTrainer | null>(null)

function onBooked() {
  selectedTrainer.value = null
  router.push('/member/schedule?booking=success')
}

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
  
  // If no category matches, show original specialization
  return categories.length > 0 ? categories : [spec]
}

function formatPrice(n: number) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(n)
}

const { run } = useDeferredLoading()

onMounted(() => run(() => store.loadTrainers()))
</script>

<template>
  <WorkspaceLayout
    role="member"
    sidebar-title="Member"
    title="Hire a Trainer"
    subtitle="Find professional coaching support for your fitness goals."
    :sidebar-items="memberSidebarItems"
  >
    <!-- Search -->
    <div class="card" style="margin-bottom: 1.25rem; padding: 1rem;">
      <input
        v-model="search"
        class="form-input"
        placeholder="Search trainer by name or specialization..."
      />
    </div>

    <!-- Loading -->
    <div v-if="store.trainersLoading && !store.trainers.length" class="feature-grid">
      <SkeletonCard v-for="n in 3" :key="n" heading :lines="2" actions />
    </div>

    <!-- Empty -->
    <div v-else-if="filteredTrainers.length === 0" class="card" style="padding: 3rem; text-align: center;">
      <p class="text-muted">No trainers registered yet.</p>
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

        <!-- Skills Categories -->
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
              <span></span> Experience
            </p>
            <p style="font-weight: 900; font-size: 1.1rem;">{{ trainer.exp }} years</p>
          </div>
          <div class="panel" style="background: var(--color-cream); padding: 0.75rem;">
            <p class="stat-label" style="font-size: 0.7rem; display: flex; align-items: center; gap: 0.25rem;">
              <span>⭐</span> Rating
            </p>
            <p style="font-weight: 900; font-size: 1.1rem;">{{ trainer.rating.toFixed(1) }}/5.0</p>
          </div>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
          <p style="font-weight: 900; color: var(--color-orange);">{{ formatPrice(trainer.price) }}/hour</p>
          <button class="button button-primary button-small" @click="selectedTrainer = trainer">
            Book Session
          </button>
        </div>
      </FitnezCard>
    </div>

    <!-- Booking Modal -->
    <Teleport to="body">
      <div v-if="selectedTrainer" class="modal-backdrop" @click.self="selectedTrainer = null">
        <BookingModal :trainer="selectedTrainer" @close="selectedTrainer = null" @booked="onBooked" />
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
  background: rgba(11, 28, 48, 0.45);
  backdrop-filter: blur(8px);
  padding: 1rem;
  animation: fadeIn 0.25s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
</style>
