<template>
  <WorkspaceLayout
    role="admin"
    title="Notifikasi"
    subtitle="Monitoring pendaftaran baru dan statistik pengguna aktif"
    sidebarTitle="Admin"
    :sidebarItems="adminSidebarItems"
  >
    <div class="admin-content-wrapper">
      <div class="content-wrapper p-8">
      <!-- Activity Log Only -->
      <div class="notifications-log-section card">
        <div class="flex items-center justify-between mb-6">
          <h2 class="title-sm m-0">Log Aktivitas Terbaru</h2>
          <button v-if="notifications.length > 0" @click="markAllRead" class="button button-ghost text-blue-500 text-sm p-0 hover:bg-transparent">
            Tandai Semua Dibaca
          </button>
        </div>
        
        <div v-if="notifications.length === 0" class="p-12 text-center">
          <p class="text-muted">Tidak ada aktivitas terbaru saat ini.</p>
        </div>

        <div v-else class="space-y-4">
          <div v-for="item in notifications" :key="item.id" 
               class="notif-log-item p-4 border border-gray-100 rounded-xl flex items-center justify-between transition-colors"
               :class="readAdminNotifIds.includes(item.id) ? 'bg-white' : 'bg-green-50/50 border-green-100'">
            <div class="flex items-center gap-4">
              <div class="log-icon w-10 h-10 rounded-full flex items-center justify-center text-xl"
                   :class="{
                     'bg-blue-100 text-blue-500': item.type === 'member',
                     'bg-orange-100 text-orange-500': item.type === 'trainer',
                     'bg-green-100 text-green-500': item.type === 'system'
                   }">
                <span v-if="item.type === 'member'">👤</span>
                <span v-else-if="item.type === 'trainer'">🎖️</span>
                <span v-else>✅</span>
              </div>
              <div>
                <p class="font-medium">
                  <span v-if="item.type === 'member'">Pendaftaran Member Baru: </span>
                  <span v-else-if="item.type === 'trainer'">Pengajuan Trainer Baru: </span>
                  <span v-else>Update Sistem: </span>
                  <span :class="item.type === 'system' ? 'text-green-600' : 'text-blue-600'">{{ item.name }}</span>
                </p>
                <p class="text-xs text-muted">{{ item.email }} • <span class="font-bold">{{ item.plan }}</span></p>
              </div>
            </div>
            <div class="text-right flex flex-col items-end gap-2">
              <span class="text-muted text-xs block">{{ formatTime(item.created_at) }}</span>
              <div class="flex items-center gap-3">
                <span v-if="item.type !== 'system'" class="text-[10px] uppercase font-bold tracking-wider" 
                      :class="item.status === 'awaiting_admin_review' || item.status === 'pending' ? 'text-blue-400' : 'text-green-500'">
                  {{ item.status === 'awaiting_admin_review' || item.status === 'pending' ? 'Menunggu Review' : 'Selesai' }}
                </span>
                <span v-else class="text-[10px] uppercase font-bold tracking-wider text-green-400">Aktif</span>
                
                <button v-if="!readAdminNotifIds.includes(item.id)" @click="markAsRead(item.id)" class="text-[10px] bg-white border border-gray-200 px-2 py-1 rounded hover:bg-gray-50 text-gray-600 font-medium">
                  Tandai Dibaca
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    </div>
  </WorkspaceLayout>
</template>

<script>
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import api from '@/api/axios';

export default {
  name: "AdminNotificationView",
  components: { WorkspaceLayout },
  data() {
    return {
      adminSidebarItems,
      activeUsers: 0,
      pendingMemberCount: 0,
      pendingTrainerCount: 0,
      notifications: [],
      readAdminNotifIds: JSON.parse(localStorage.getItem('fitnez_admin_read_notifs') || '[]')
    };
  },
  computed: {

  },
  async mounted() {
    await this.fetchAdminData();
  },
  methods: {
    markAsRead(id) {
      if (!this.readAdminNotifIds.includes(id)) {
        this.readAdminNotifIds.push(id);
        localStorage.setItem('fitnez_admin_read_notifs', JSON.stringify(this.readAdminNotifIds));
      }
    },
    markAllRead() {
      this.notifications.forEach(n => {
        if (!this.readAdminNotifIds.includes(n.id)) {
          this.readAdminNotifIds.push(n.id);
        }
      });
      localStorage.setItem('fitnez_admin_read_notifs', JSON.stringify(this.readAdminNotifIds));
    },
    async fetchAdminData() {
      try {
        const { data } = await api.get('/admin/notifications');
        this.notifications = data.notifications || [];
        this.activeUsers = data.activeUsers || 0;
        this.pendingMemberCount = data.pendingMemberCount || 0;
        this.pendingTrainerCount = data.pendingTrainerCount || 0;
      } catch (error) {
        window.showFitnezToast('Gagal mengambil data admin.', 'error');
      }
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
    async approve(id) {
      try {
        await api.post(`/admin/approve/${id}`);
        this.notifications = this.notifications.filter(n => n.id !== id);
        this.activeUsers++;
        window.showFitnezToast('Pendaftaran berhasil disetujui', 'success');
      } catch (error) {
        window.showFitnezToast('Gagal menyetujui pendaftaran', 'error');
      }
    },
    async reject(id) {
      try {
        await api.post(`/admin/reject/${id}`);
        this.notifications = this.notifications.filter(n => n.id !== id);
        window.showFitnezToast('Pendaftaran telah ditolak', 'success');
      } catch (error) {
        window.showFitnezToast('Gagal menolak pendaftaran', 'error');
      }
    },
  }
};
</script>
