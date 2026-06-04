<template>
  <WorkspaceLayout
    :role="isTrainerWorkspace ? 'trainer' : 'member'"
    :title="isTrainerWorkspace ? 'Trainer Notifications' : 'My Notifications'"
    :subtitle="isTrainerWorkspace ? 'Kelola aktivitas dan pendapatan pelatihan Anda' : 'Update terbaru jadwal dan latihan Anda'"
    sidebarTitle="Notifications"
    :sidebarItems="isTrainerWorkspace ? trainerSidebarItems : memberSidebarItems"
  >
    <div class="notification-content-wrapper">
      <!-- TRAINER WORKSPACE VIEW -->
      <template v-if="isTrainerWorkspace">
        <header class="flex justify-between items-center mb-8">
          <h2 class="title-md">Pusat Notifikasi Trainer</h2>
          <button v-if="trainerNotifications.length > 0" @click="markAllReadTrainer" class="button button-ghost">
            Tandai Semua Dibaca
          </button>
        </header>

        <div class="notification-sections">
          <section class="mb-10">
            <h3 class="eyebrow eyebrow-accent mb-4">Hari Ini</h3>
            <div v-if="todayTrainerNotifs.length === 0" class="empty-state card">
              <p>Tidak ada notifikasi baru hari ini.</p>
            </div>
            <div class="grid gap-4">
              <div v-for="item in todayTrainerNotifs" :key="item.id" 
                   :class="['notif-card card', { 'unread-notif': !item.is_read }]">
                <div class="flex gap-4">
                  <div class="notif-icon-circle">
                    <span v-if="item.notification_type === 'rent'">💰</span>
                    <span v-else-if="item.notification_type === 'hire'">🤝</span>
                    <span v-else-if="item.notification_type === 'schedule'">⏰</span>
                    <span v-else-if="item.notification_type === 'classes'">🏋️</span>
                    <span v-else>🔔</span>
                  </div>
                  <div class="flex-1">
                    <div class="flex justify-between">
                      <span class="status-text text-xs">{{ getTrainerLabel(item.notification_type) }}</span>
                      <span class="text-muted text-xs">{{ formatTime(item.created_at) }}</span>
                    </div>
                    <h4 class="font-bold mt-1">{{ item.title }}</h4>
                    <p class="text-muted text-sm">{{ item.body }}</p>
                  </div>
                  <button v-if="!item.is_read" @click="markAsRead(item.id)" class="button button-primary button-small text-[10px] py-1 px-2">TANDAI</button>
                  <span v-else class="text-[10px] font-bold text-blue-400">DIBACA</span>
                </div>
              </div>
            </div>
          </section>

          <section>
            <h3 class="eyebrow mb-4">Sebelumnya</h3>
            <div class="grid gap-4">
              <div v-for="item in earlierTrainerNotifs" :key="item.id" 
                   :class="['notif-card card', { 'unread-notif': !item.is_read }]">
                <div class="flex gap-4 opacity-75">
                  <div class="notif-icon-circle bg-gray-100">
                    <span>🔔</span>
                  </div>
                  <div class="flex-1">
                    <div class="flex justify-between">
                      <span class="status-text text-xs">{{ getTrainerLabel(item.notification_type) }}</span>
                      <span class="text-muted text-xs">{{ formatDate(item.created_at) }}</span>
                    </div>
                    <h4 class="font-bold mt-1">{{ item.title }}</h4>
                    <p class="text-muted text-sm">{{ item.body }}</p>
                  </div>
                </div>
              </div>
            </div>
          </section>
        </div>
      </template>

      <!-- MEMBER WORKSPACE VIEW -->
      <template v-else>
        <header class="flex justify-between items-center mb-8">
          <h2 class="title-md">Notifikasi Saya</h2>
          <button v-if="realNotifications.length > 0 || hariIni.length > 0" @click="markAllReadMember" class="button button-ghost">
            Tandai Semua Dibaca
          </button>
        </header>

        <div class="member-notif-sections grid md:grid-cols-2 gap-8">
          
          <!-- Column 1: Workout Reminders -->
          <div class="workout-reminders-col">
            <h2 class="title-sm border-b pb-2 mb-6">🗓️ Jadwal Latihan Anda</h2>
            
            <section class="mb-8">
              <h3 class="eyebrow eyebrow-accent mb-3">Besok</h3>
              <div v-if="besok.length === 0" class="empty-state-sm card p-4 text-center text-muted text-sm">Tidak ada jadwal untuk besok</div>
              <div class="grid gap-3">
                <div v-for="item in besok" :key="item.id" class="notif-item-premium card p-4">
                  <div class="flex items-center gap-4">
                    <div class="flex-1">
                      <strong class="block text-sm">{{ item.title }}</strong>
                      <p class="text-xs text-muted mt-1">{{ item.body }}</p>
                    </div>
                    <span class="badge badge-accent text-[10px]">Upcoming</span>
                  </div>
                </div>
              </div>
            </section>

            <section class="mb-8">
              <h3 class="eyebrow mb-3">Hari Ini</h3>
              <div v-if="hariIni.length === 0" class="empty-state-sm card p-4 text-center text-muted text-sm">Tidak ada latihan hari ini</div>
              <div class="grid gap-3">
                <div v-for="item in hariIni" :key="item.id" :class="['notif-item-premium card p-4', { 'unread': !item.is_read }]">
                  <div class="flex items-center gap-4">
                    <div class="flex-1">
                      <strong class="block text-sm">{{ item.title }}</strong>
                      <p class="text-xs text-muted mt-1">{{ item.body }}</p>
                    </div>
                    <span v-if="item.is_read" class="text-green-500 text-xs font-bold">✓ Selesai</span>
                    <span v-else class="text-orange-500 text-xs font-bold">Menunggu</span>
                  </div>
                </div>
              </div>
            </section>
            
            <section>
              <h3 class="eyebrow mb-3">Selesai (Riwayat)</h3>
              <div v-if="kemarin.length === 0" class="empty-state-sm card p-4 text-center text-muted text-sm">Tidak ada riwayat</div>
              <div class="grid gap-3">
                <div v-for="item in kemarin" :key="item.id" class="notif-item-premium card p-4 opacity-75">
                  <div class="flex items-center gap-4">
                    <div class="flex-1">
                      <strong class="block text-sm">{{ item.title }}</strong>
                      <p class="text-xs text-muted mt-1">{{ item.body }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </section>
          </div>

          <!-- Column 2: System Notifications -->
          <div class="system-notifs-col">
            <div class="flex justify-between items-center border-b pb-2 mb-6">
              <h2 class="title-sm m-0">🔔 Log Notifikasi</h2>
              <button v-if="realNotifications.length > 0" @click="markAllReadMember" class="text-blue-500 text-xs font-bold hover:underline">Tandai Semua Dibaca</button>
            </div>
            
            <div v-if="realNotifications.length === 0" class="empty-state-sm card p-6 text-center text-muted text-sm">
              Tidak ada notifikasi baru.
            </div>
            
            <div class="grid gap-3">
              <div v-for="item in realNotifications" :key="item.id" 
                   class="notif-item-premium card p-4 flex gap-4 transition-colors"
                   :class="!item.is_read ? 'bg-blue-50/50 border-blue-100' : ''">
                <div class="log-icon w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-sm shrink-0">
                  ℹ️
                </div>
                <div class="flex-1">
                  <div class="flex justify-between items-start mb-1">
                    <strong class="text-sm text-gray-800">{{ item.title }}</strong>
                    <span class="text-[10px] text-muted">{{ formatTime(item.created_at) }}</span>
                  </div>
                  <p class="text-xs text-muted leading-relaxed">{{ item.body }}</p>
                </div>
                <button v-if="!item.is_read" @click="markAsRead(item.id)" class="button button-small button-ghost self-center px-2 py-1 text-[10px]">Tandai</button>
              </div>
            </div>
          </div>

        </div>
      </template>
    </div>
  </WorkspaceLayout>
</template>

<script>
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems, trainerSidebarItems } from '../../components/layout/sidebarItems'
import api from '@/api/axios';

export default {
  name: "NotificationView",
  components: { WorkspaceLayout },
  data() {
    return {
      memberSidebarItems,
      trainerSidebarItems,
      realNotifications: [],
      trainerNotifications: [],
      kemarin: [],
      hariIni: [],
      besok: [],
      readDummyIds: JSON.parse(localStorage.getItem('fitnez_read_notifs') || '[]')
    };
  },
  computed: {
    isTrainerWorkspace() {
      return this.$route.path.includes('/trainer');
    },
    todayTrainerNotifs() {
      const today = new Date().toDateString();
      return this.trainerNotifications.map(n => ({
        ...n,
        is_read: n.is_read || this.readDummyIds.includes(n.id)
      })).filter(n => new Date(n.created_at).toDateString() === today);
    },
    earlierTrainerNotifs() {
      const today = new Date().toDateString();
      return this.trainerNotifications.map(n => ({
        ...n,
        is_read: n.is_read || this.readDummyIds.includes(n.id)
      })).filter(n => new Date(n.created_at).toDateString() !== today);
    }
  },
  async mounted() {
    await this.fetchNotifications();
  },
  watch: {
    '$route.path': async function() {
      await this.fetchNotifications();
    }
  },
  methods: {
    async fetchNotifications() {
      try {
        if (this.isTrainerWorkspace) {
          const { data } = await api.get('/trainer/notifications');
          if (!data || data.length === 0) {
            this.trainerNotifications = [
              { id: 't1', notification_type: 'rent', title: 'Uang Sewa Masuk', body: 'Pembayaran sewa sebesar Rp 250.000 telah diterima.', created_at: new Date().toISOString(), is_read: false },
              { id: 't2', notification_type: 'hire', title: 'Disewa Member Baru', body: 'Member "Budi Santoso" telah memilih Anda sebagai trainer.', created_at: new Date().toISOString(), is_read: false },
              { id: 't3', notification_type: 'schedule', title: 'Update Jadwal', body: 'Latihan besok pukul 08.00 dengan Member "Siska".', created_at: new Date().toISOString(), is_read: true }
            ];
          } else {
            this.trainerNotifications = data;
          }
        } else {

          const respNotif = await api.get('/notifications');
          const rawRealNotifs = Array.isArray(respNotif.data) ? respNotif.data : (respNotif.data?.data || []);
          
          this.realNotifications = rawRealNotifs.map(n => ({
            ...n,
            is_read: n.is_read || this.readDummyIds.includes(n.id)
          }));
          

          const respWorkout = await api.get('/workout-plans');
          const workoutPlans = Array.isArray(respWorkout.data) ? respWorkout.data : (respWorkout.data?.data || []);
          
          const reminders = workoutPlans.map(w => ({
            id: 'workout-' + w.id,
            title: w.name,
            body: `${w.category} • ${w.reps} reps x ${w.set} set • ${w.weight}kg`,
            created_at: w.created_at,
            group_date: w.date,
            is_read: !!w.completed,
            type: 'workout'
          }));

          this.groupWorkoutReminders(reminders);
        }
      } catch (error) {
        console.error("Gagal load notifikasi:", error);
      }
    },

    groupWorkoutReminders(reminders) {
      this.kemarin = [];
      this.hariIni = [];
      this.besok = [];

      const today = new Date();
      today.setHours(0, 0, 0, 0);

      const yesterday = new Date(today);
      yesterday.setDate(yesterday.getDate() - 1);

      const tomorrow = new Date(today);
      tomorrow.setDate(tomorrow.getDate() + 1);

      reminders.forEach(n => {
        const d = new Date(n.group_date);
        d.setHours(0, 0, 0, 0);

        if (d.getTime() === today.getTime()) {
          this.hariIni.push(n);
        } else if (d.getTime() === yesterday.getTime() || d.getTime() < yesterday.getTime()) {
          this.kemarin.push(n); 
        } else if (d.getTime() === tomorrow.getTime() || d.getTime() > tomorrow.getTime()) {
          this.besok.push(n);
        }
      });
    },

    getTrainerLabel(type) {
      const labels = { 
        rent: 'Uang Sewa Masuk', 
        hire: 'Disewa Member', 
        schedule: 'Jadwal Latihan',
        classes: 'Reminder Kelas'
      };
      return labels[type] || 'Notifikasi';
    },

    formatTime(dateStr) {
      if (!dateStr) return '--:--';
      const date = new Date(dateStr);
      

      if (isNaN(date.getTime())) return '--:--';
      
      return date.toLocaleTimeString('id-ID', { 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit',
        hour12: false 
      });
    },

    formatDate(dateStr) {
      return new Date(dateStr).toLocaleDateString();
    },

    async markAsRead(id) {

      if (typeof id === 'string' && (id.startsWith('t') || id.startsWith('workout-'))) {
        if (!this.readDummyIds.includes(id)) {
          this.readDummyIds.push(id);
          localStorage.setItem('fitnez_read_notifs', JSON.stringify(this.readDummyIds));
        }
        return;
      }

      try {
        await api.patch(`/notifications/${id}/read`);
        await this.fetchNotifications();
      } catch (error) {
        console.error("Gagal update status baca:", error);
      }
    },

    async markAllReadTrainer() {

      this.trainerNotifications.forEach(n => {
        if (!this.readDummyIds.includes(n.id)) {
          this.readDummyIds.push(n.id);
        }
      });
      localStorage.setItem('fitnez_read_notifs', JSON.stringify(this.readDummyIds));
      
      try {
        await api.patch('/notifications/read-all');
        await this.fetchNotifications();
      } catch (error) {
        console.error("Gagal update status baca trainer:", error);
      }
    },

    async markAllReadMember() {

      try {
        await api.patch('/notifications/read-all');
        await this.fetchNotifications();
      } catch (e) {
        console.error(e);
      }
    }
  }
};
</script>