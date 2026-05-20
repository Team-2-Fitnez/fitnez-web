<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '../../components/layout/sidebarItems'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import { useBookingStore } from '../../stores/bookingStore'

const store = useBookingStore()

type BookingFilter = 'all' | 'pending' | 'confirmed' | 'completed' | 'cancelled'

const filters: BookingFilter[] = ['all', 'pending', 'confirmed', 'completed', 'cancelled']
const filter = ref<BookingFilter>('all')

const filteredBookings = computed(() => {
  if (filter.value === 'all') return store.bookings
  return store.bookings.filter(b => b.status === filter.value)
})

const pendingCount = computed(() => store.bookings.filter(b => b.status === 'pending').length)

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('id-ID', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

function formatPrice(n: number | string | null | undefined) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(Number(n || 0))
}

onMounted(() => store.loadBookings())
</script>

<template>
  <WorkspaceLayout
    role="member"
    sidebar-title="Member"
    title="Jadwal Latihan"
    subtitle="Lihat dan kelola jadwal sesi latihan dengan trainer Anda"
    :sidebar-items="memberSidebarItems"
  >
    <!-- Info Banner jika ada pending -->
    <div v-if="pendingCount > 0" class="alert alert-info" style="margin-bottom: 1.5rem;">
      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <span style="font-size: 1.5rem;">⏳</span>
        <div>
          <p style="font-weight: 600; margin-bottom: 0.25rem;">
            {{ pendingCount }} Booking Menunggu Konfirmasi
          </p>
          <p class="text-muted" style="font-size: 0.875rem;">
            Trainer akan mengkonfirmasi jadwal Anda segera. Anda akan menerima notifikasi setelah dikonfirmasi.
          </p>
        </div>
      </div>
    </div>

    <!-- Filter -->
    <div class="card" style="margin-bottom: 1.25rem; padding: 1rem;">
      <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
        <button
          v-for="f in filters"
          :key="f"
          :class="['filter-btn', filter === f && 'filter-btn-active']"
          @click="filter = f"
        >
          {{ f === 'all' ? 'Semua' : f === 'pending' ? 'Menunggu' : f === 'confirmed' ? 'Dikonfirmasi' : f === 'completed' ? 'Selesai' : 'Dibatalkan' }}
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="store.loading" class="card" style="padding: 3rem; text-align: center;">
      <p class="text-muted">Memuat jadwal...</p>
    </div>

    <!-- Empty -->
    <div v-else-if="filteredBookings.length === 0" class="card" style="padding: 3rem; text-align: center;">
      <p style="font-size: 3rem; margin-bottom: 1rem;">📅</p>
      <p class="text-muted" style="margin-bottom: 1rem;">
        {{ filter === 'all' ? 'Belum ada jadwal latihan.' : `Tidak ada jadwal dengan status "${filter}".` }}
      </p>
      <router-link to="/member/hire-trainer" class="button button-primary">
        Sewa Trainer Sekarang
      </router-link>
    </div>

    <!-- Booking List -->
    <div v-else style="display: grid; gap: 1rem;">
      <div v-for="booking in filteredBookings" :key="booking.id" class="card">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
          <div style="flex: 1;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
              <div
                style="width: 2.5rem; height: 2.5rem; border-radius: 0.75rem; background: var(--color-blue); display: flex; align-items: center; justify-content: center; color: white; font-weight: 900; font-size: 1rem; flex-shrink: 0;"
              >
                {{ booking.trainer?.full_name?.[0]?.toUpperCase() ?? 'T' }}
              </div>
              <div>
                <h3 class="title-md" style="font-size: 1rem;">{{ booking.trainer?.full_name ?? 'Trainer' }}</h3>
                <p class="text-muted" style="font-size: 0.75rem;">{{ formatDate(booking.booking_date) }}</p>
              </div>
            </div>
          </div>
          <StatusBadge :status="booking.status || 'pending'" />
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem; margin-bottom: 1rem;">
          <div class="panel" style="background: var(--color-cream); padding: 0.75rem;">
            <p class="stat-label" style="font-size: 0.7rem;">⏰ Waktu</p>
            <p style="font-weight: 600; font-size: 0.9rem;">{{ booking.start_time }} - {{ booking.end_time }}</p>
          </div>
          <div class="panel" style="background: var(--color-cream); padding: 0.75rem;">
            <p class="stat-label" style="font-size: 0.7rem;">📍 Tipe</p>
            <p style="font-weight: 600; font-size: 0.9rem; text-transform: capitalize;">{{ booking.session_type }}</p>
          </div>
          <div class="panel" style="background: var(--color-cream); padding: 0.75rem;">
            <p class="stat-label" style="font-size: 0.7rem;">💰 Biaya</p>
            <p style="font-weight: 600; font-size: 0.9rem; color: var(--color-orange);">{{ formatPrice(booking.total_price) }}</p>
          </div>
        </div>

        <div v-if="booking.location" style="margin-bottom: 1rem;">
          <p class="stat-label" style="font-size: 0.75rem; margin-bottom: 0.25rem;">📍 Lokasi</p>
          <p style="font-size: 0.875rem;">{{ booking.location }}</p>
        </div>

        <div v-if="booking.member_notes" style="margin-bottom: 1rem;">
          <p class="stat-label" style="font-size: 0.75rem; margin-bottom: 0.25rem;">📝 Catatan</p>
          <p style="font-size: 0.875rem; color: #666;">{{ booking.member_notes }}</p>
        </div>

        <!-- Status Info -->
        <div v-if="booking.status === 'pending'" class="panel" style="background: #fff3cd; border-left: 3px solid #ffc107; padding: 0.75rem;">
          <p style="font-size: 0.875rem; color: #856404;">
            ⏳ Menunggu konfirmasi dari trainer. Anda akan menerima notifikasi setelah trainer mengkonfirmasi jadwal ini.
          </p>
        </div>

        <div v-else-if="booking.status === 'confirmed'" class="panel" style="background: #d4edda; border-left: 3px solid #28a745; padding: 0.75rem;">
          <p style="font-size: 0.875rem; color: #155724;">
            ✅ Jadwal dikonfirmasi! Silakan hubungi trainer via Chat untuk koordinasi lebih lanjut.
          </p>
        </div>

        <div v-else-if="booking.status === 'rejected'" class="panel" style="background: #f8d7da; border-left: 3px solid #dc3545; padding: 0.75rem;">
          <p style="font-size: 0.875rem; color: #721c24;">
            ❌ Booking ditolak oleh trainer. Silakan pilih waktu lain atau trainer lain.
          </p>
        </div>

        <!-- Actions -->
        <div v-if="booking.status === 'confirmed'" style="display: flex; gap: 0.5rem; margin-top: 1rem;">
          <router-link :to="`/member/chat?contact=${booking.trainer_id}`" class="button button-primary button-small">
            💬 Chat Trainer
          </router-link>
        </div>
      </div>
    </div>
  </WorkspaceLayout>
</template>

<style scoped>
.alert {
  padding: 1rem;
  border-radius: 0.75rem;
  border: 1px solid;
}

.alert-info {
  background: #d1ecf1;
  border-color: #bee5eb;
  color: #0c5460;
}

.filter-btn {
  padding: 0.5rem 1rem;
  border: 1px solid #ddd;
  background: white;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.filter-btn:hover {
  background: #f5f5f5;
}

.filter-btn-active {
  background: var(--color-blue);
  color: white;
  border-color: var(--color-blue);
}
</style>
