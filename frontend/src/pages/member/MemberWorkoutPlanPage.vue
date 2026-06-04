<template>
  <WorkspaceLayout
    role="member"
    title="Workout Plan"
    subtitle="🔥 DON'T GIVE UP! Push your limits and achieve your goals! 🔥"
    sidebarTitle="My Training"
    :sidebarItems="memberSidebarItems"
  >
    <div class="workout-content-wrapper">
      <form id="workout-form" @submit.prevent="handleSubmit" class="card mb-8">
        <h3 class="title-md mb-6">Tambah Latihan Baru</h3>

        <div class="input-grid">
          <div class="form-field">
            <label class="form-label">Tanggal</label>
            <input
              type="date"
              class="form-input"
              v-model="formData.date"
              @change="onDateChange"
              required
            />
          </div>

          <div class="form-field">
            <label class="form-label">Hari</label>
            <input
              type="text"
              class="form-input bg-gray-100"
              v-model="formData.day"
              disabled
            />
          </div>

          <div class="form-field">
            <label class="form-label">Kategori</label>
            <select
              class="form-input"
              v-model="formData.category"
              @change="onCategoryChange"
              required
            >
              <option value="" disabled>Pilih Kategori</option>
              <option value="Cardio">Cardio</option>
              <option value="Strength">Strength</option>
              <option value="Flexibility">Flexibility</option>
            </select>
          </div>

          <div class="form-field">
            <label class="form-label">Latihan</label>
            <select
              class="form-input"
              v-model="formData.name"
              required
            >
              <option value="" disabled>-- Pilih Gerakan --</option>
              <option v-for="exercise in exerciseOptions" :key="exercise" :value="exercise">
                {{ exercise }}
              </option>
            </select>
          </div>

          <div class="form-field">
            <label class="form-label">Set</label>
            <input type="number" class="form-input" v-model="formData.set" required />
          </div>

          <div class="form-field">
            <label class="form-label">Beban (kg)</label>
            <input type="number" class="form-input" v-model="formData.weight" required />
          </div>

          <div class="form-field">
            <label class="form-label">Repetisi</label>
            <input type="number" class="form-input" v-model="formData.reps" required />
          </div>

          <div class="form-field">
            <label class="form-label">Durasi (menit)</label>
            <input type="number" class="form-input" v-model="formData.duration" />
          </div>
        </div>

        <div class="mt-8 flex justify-end">
          <button type="submit" class="button button-primary">
            {{ editId !== null ? 'Simpan Perubahan' : 'Tambah ke Daftar Workout' }}
          </button>
        </div>
      </form>

      <section id="dashboard" class="card">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
          <h2 class="title-md">Daftar Jadwal Latihan</h2>
          
          <div class="flex gap-4">
            <div class="stat-pill">
              <span>Sesi: <b>{{ stats.sessions }}</b></span>
            </div>
            <div class="stat-pill">
              <span>Beban: <b>{{ stats.weight }} kg</b></span>
            </div>
            <div class="stat-pill">
              <span>Selesai: <b>{{ stats.percent }}%</b></span>
            </div>
          </div>
        </div>

        <div v-if="workouts.length === 0" class="empty-state">
          <p>Belum ada data workout.</p>
        </div>

        <div class="workout-list">
          <div
            v-for="item in sortedWorkouts"
            :key="item.id"
            :class="['workout-card', { 'completed-card': item.completed }]"
          >
            <div class="workout-main-info">
              <div class="flex items-center gap-3">
                <span class="status-dot" :class="item.completed ? 'bg-green-500' : 'bg-orange-500'"></span>
                <strong class="text-lg">{{ item.name }}</strong>
                <span class="badge">{{ item.category }}</span>
              </div>
              <p class="text-muted text-sm mt-1">
                {{ (item.date || '').substring(0, 10) }} • {{ item.set }} Set • {{ item.weight }}kg • {{ item.reps }} Reps
              </p>
            </div>
            
            <div class="workout-actions">
              <button type="button" class="btn-icon" @click="showTutorial(item.name, item.category)" title="Tutorial">📺</button>
              <button
                type="button"
                class="btn-icon"
                @click="toggleWorkout(item.id)"
                :title="item.completed ? 'Tandai belum' : 'Tandai selesai'"
              >
                {{ item.completed ? '↩' : '✔' }}
              </button>
              <button type="button" class="btn-icon text-blue-600" @click="editWorkout(item.id)">✎</button>
              <button type="button" class="btn-icon text-red-600" @click="deleteWorkout(item.id)">✖</button>
            </div>
          </div>
        </div>

        <div class="mt-10 flex gap-4 border-t pt-8">
          <button type="button" class="button button-ghost" @click="exportToCSV">📥 Unduh CSV</button>
          <button v-if="workouts.length > 0" type="button" class="button button-danger" @click="clearAllWorkouts">🗑️ Hapus Semua</button>
        </div>
      </section>

      <!-- Tutorial Section -->
      <transition name="fade">
        <section v-if="tutorialVisible" id="tutorial-overlay" class="tutorial-overlay">
          <div class="tutorial-modal card">
            <div class="flex items-center justify-between mb-6">
              <h2 class="title-md">📺 Tutorial: {{ tutorialData.title }}</h2>
              <button type="button" @click="closeTutorial" class="button button-ghost p-2">✖</button>
            </div>

            <div class="tutorial-body">
              <div class="video-wrapper mb-6">
                <iframe :src="tutorialData.video" frameborder="0" allowfullscreen></iframe>
              </div>
              <p class="text-muted leading-relaxed">{{ tutorialData.desc }}</p>
            </div>
          </div>
        </section>
      </transition>
    </div>
  </WorkspaceLayout>
</template>

<script>
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '../../components/layout/sidebarItems'
import api from '@/api/axios';

const daftarHari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];

const workoutData = {
  Cardio: {
    "Running (Lari)": {
      desc: "Lari adalah olahraga kardio yang efektif untuk membakar kalori dan meningkatkan kesehatan jantung.",
      video: "https://www.youtube.com/embed/9L2b2khySLE"
    },
    "Cycling (Sepeda)": {
      desc: "Bersepeda melatih otot kaki, meningkatkan stamina, dan baik untuk kesehatan sendi.",
      video: "https://www.youtube.com/embed/f0T0f_jF8-8"
    },
    "Jump Rope": {
      desc: "Skipping atau lompat tali adalah latihan kardio intensitas tinggi yang membakar banyak kalori.",
      video: "https://www.youtube.com/embed/u3zgHI899k8"
    },
    "Burpees": {
      desc: "Burpees menggabungkan squat, push-up, dan lompatan. Latihan full-body yang sangat efektif.",
      video: "https://www.youtube.com/embed/auQLvF8UORs"
    },
    "Jumping Jacks": {
      desc: "Jumping jacks melatih koordinasi dan keseimbangan.",
      video: "https://www.youtube.com/embed/c4DAnQ6DtF8"
    },
    "Mountain Climbers": {
      desc: "Gerakan menyerupai memanjat gunung dalam posisi plank. Melatih core, bahu, dan kaki.",
      video: "https://www.youtube.com/embed/nmwgirgXLYM"
    },
    "Swimming": {
      desc: "Berenang melatih seluruh otot tanpa membebani sendi.",
      video: "https://www.youtube.com/embed/5HLW2aiXWW0"
    }
  },
  Strength: {
    "Bench Press": {
      desc: "Bench press melatih otot dada, trisep, dan bahu.",
      video: "https://www.youtube.com/embed/rT7DgHISCHw"
    },
    "Squat": {
      desc: "Squat melatih paha, glutes, dan core.",
      video: "https://www.youtube.com/embed/MVMNk0HiTMg"
    },
    "Deadlift": {
      desc: "Deadlift melatih punggung bawah, glutes, and hamstring.",
      video: "https://www.youtube.com/embed/ytGaGIn3SjE"
    },
    "Overhead Press": {
      desc: "Overhead press melatih bahu dan trisep.",
      video: "https://www.youtube.com/embed/QAQ64hK4SkI"
    },
    "Pull Up": {
      desc: "Pull-up melatih punggung, bahu, dan lengan.",
      video: "https://www.youtube.com/embed/eGo4IYlbE5g"
    },
    "Push Up": {
      desc: "Push-up melatih dada, trisep, dan core.",
      video: "https://www.youtube.com/embed/IODxDxX7oi4"
    },
    "Bicep Curl": {
      desc: "Bicep curl melatih otot lengan depan.",
      video: "https://www.youtube.com/embed/ykJmrZ5v0BA"
    },
    "Tricep Extension": {
      desc: "Tricep extension melatih otot lengan belakang.",
      video: "https://www.youtube.com/embed/nRiJVZDpdL0"
    },
    "Lunges": {
      desc: "Lunges melatih paha dan glutes.",
      video: "https://www.youtube.com/embed/QOVaHwm-Q6U"
    },
    "Plank": {
      desc: "Plank melatih core dan stabilitas tubuh.",
      video: "https://www.youtube.com/embed/ASdvN_XEl_c"
    }
  },
  Flexibility: {
    "Cobra Stretch": {
      desc: "Cobra stretch melatih fleksibilitas tulang belakang.",
      video: "https://www.youtube.com/embed/JDcdpQCzk8U"
    },
    "Child's Pose": {
      desc: "Child's pose adalah gerakan relaksasi yang meregangkan punggung bawah.",
      video: "https://www.youtube.com/embed/2N09E1K8u88"
    },
    "Hamstring Stretch": {
      desc: "Hamstring stretch meregangkan otot paha belakang.",
      video: "https://www.youtube.com/embed/6_9_WpY6p00"
    },
    "Butterfly Stretch": {
      desc: "Butterfly stretch membuka pinggul dan paha dalam.",
      video: "https://www.youtube.com/embed/MdE_m2f_v_4"
    },
    "Cat-Cow Stretch": {
      desc: "Cat-cow stretch meningkatkan fleksibilitas tulang belakang.",
      video: "https://www.youtube.com/embed/w_U0S3H_E88"
    },
    "Yoga Sun Salutation": {
      desc: "Sun salutation adalah rangkaian gerakan yoga yang melatih fleksibilitas dan kekuatan.",
      video: "https://www.youtube.com/embed/8v_GvE66hnc"
    }
  }
};

export default {
  name: "WorkoutPlanView",
  components: { WorkspaceLayout },
  data() {
    return {
      memberSidebarItems,
      formData: {
        date: "",
        day: "",
        category: "",
        name: "",
        set: "",
        weight: "",
        reps: "",
        duration: ""
      },
      workouts: [],
      editId: null,
      tutorialVisible: false,
      tutorialData: {
        title: "Pilih Gerakan",
        desc: "Cari atau pilih latihan untuk melihat tutorial.",
        video: ""
      },
      searchQuery: "",
      loading: false
    };
  },

  computed: {
    exerciseOptions() {
      if (!this.formData.category || !workoutData[this.formData.category]) {
        return [];
      }
      return Object.keys(workoutData[this.formData.category]);
    },

    stats() {
      const total = this.workouts.length;
      const beban = this.workouts.reduce((s, w) => s + (parseFloat(w.weight) || 0), 0);
      const done = this.workouts.filter(w => w.completed).length;
      const persen = total === 0 ? 0 : Math.round((done / total) * 100);
      return {
        sessions: total,
        weight: beban,
        percent: persen
      };
    },

    sortedWorkouts() {
      return [...this.workouts].sort((a, b) => {
        if (a.completed !== b.completed) return a.completed ? 1 : -1;
        return (a.date || "").localeCompare(b.date || "");
      });
    }
  },

  async mounted() {
    await this.fetchWorkouts();
  },

  methods: {
    async fetchWorkouts() {
      try {
        const response = await api.get('/workout-plans');
        this.workouts = response.data;
      } catch (error) {
        console.error("Failed to load workouts:", error);
      }
    },

    onDateChange() {
      if (!this.formData.date) return;
      const parts = this.formData.date.split("-");
      const dateObj = new Date(parts[0], parts[1] - 1, parts[2]);
      this.formData.day = daftarHari[dateObj.getDay()];
    },

    onCategoryChange() {

      this.formData.name = "";
    },

    async handleSubmit() {
      if (!this.formData.name || !this.formData.date) {
        alert("Pilih tanggal dan gerakan latihan!");
        return;
      }

      this.loading = true;
      try {
        if (this.editId !== null) {
          await api.put(`/workout-plans/${this.editId}`, this.formData);
          window.showFitnezToast('Jadwal latihan berhasil diperbarui!');
          this.editId = null;
        } else {
          await api.post('/workout-plans', this.formData);
          window.showFitnezToast('Jadwal latihan baru telah ditambahkan!');
        }
        await this.fetchWorkouts();
        this.resetForm();
      } catch (error) {
        console.error("Save failed:", error);
        window.showFitnezToast('Gagal menyimpan jadwal latihan.', 'error');
      } finally {
        this.loading = false;
      }
    },

    resetForm() {
      this.formData = {
        date: "",
        day: "",
        category: "",
        name: "",
        set: "",
        weight: "",
        reps: "",
        duration: ""
      };
    },

    async toggleWorkout(id) {
      const item = this.workouts.find(w => w.id === id);
      if (!item) return;
      
      const newCompletedState = !item.completed;
      
      try {
        await api.put(`/workout-plans/${id}`, {
          completed: newCompletedState
        });
        await this.fetchWorkouts();
      } catch (error) {
        console.error("Toggle failed:", error);
      }
    },

    async deleteWorkout(id) {
      if (!confirm("Hapus jadwal ini?")) return;
      
      try {
        await api.delete(`/workout-plans/${id}`);
        window.showFitnezToast('Jadwal latihan telah dihapus.', 'success');
        await this.fetchWorkouts();
      } catch (error) {
        console.error("Delete failed:", error);
        window.showFitnezToast('Gagal menghapus jadwal.', 'error');
      }
    },
    
    async clearAllWorkouts() {
      if (!confirm("Hapus seluruh jadwal latihan Anda?")) return;
      try {
        await api.delete('/workout-plans/clear-all');
        window.showFitnezToast('Seluruh jadwal latihan telah dihapus.');
        await this.fetchWorkouts();
      } catch (error) {
        console.error("Clear all failed:", error);
        window.showFitnezToast('Gagal menghapus semua jadwal.', 'error');
      }
    },

    editWorkout(id) {
      const itm = this.workouts.find(w => w.id === id);
      if (!itm) return;

      this.formData = {
        date: (itm.date || "").substring(0, 10),
        day: itm.day || "",
        category: itm.category || "",
        name: itm.name || "",
        set: itm.set || "",
        weight: itm.weight || "",
        reps: itm.reps || "",
        duration: itm.duration || ""
      };

      this.editId = id;
      window.scrollTo({ top: 0, behavior: "smooth" });
    },

    exportToCSV() {
      let csv = "data:text/csv;charset=utf-8,Tanggal,Hari,Kategori,Latihan,Set,Beban,Reps\n";
      this.workouts.forEach(w => {
        csv += `${(w.date || "").substring(0, 10)},${w.day},${w.category},${w.name},${w.set},${w.weight},${w.reps}\n`;
      });
      const a = document.createElement("a");
      a.href = encodeURI(csv);
      a.download = "workout_data.csv";
      a.click();
    },

    showTutorial(name, category) {
      if (workoutData[category] && workoutData[category][name]) {
        const data = workoutData[category][name];
        this.tutorialData = {
          title: name,
          desc: data.desc,
          video: data.video
        };
        this.tutorialVisible = true;
        this.$nextTick(() => {
          const el = document.getElementById("tutorial-section");
          if (el) el.scrollIntoView({ behavior: "smooth" });
        });
      } else {
        alert(`Tutorial untuk "${name}" belum tersedia.`);
      }
    },

    closeTutorial() {
      this.tutorialVisible = false;
      this.tutorialData = {
        title: "Pilih Gerakan",
        desc: "Cari atau pilih latihan untuk melihat tutorial.",
        video: ""
      };
      this.searchQuery = "";
    },

    searchVideo() {
      const query = this.searchQuery.toLowerCase().trim();
      if (query.length < 2) {
        alert("Masukkan minimal 2 karakter untuk mencari");
        return;
      }

      for (const category in workoutData) {
        for (const exerciseName in workoutData[category]) {
          if (exerciseName.toLowerCase().includes(query)) {
            const data = workoutData[category][exerciseName];
            this.tutorialData = {
              title: exerciseName,
              desc: data.desc,
              video: data.video
            };
            this.tutorialVisible = true;
            this.$nextTick(() => {
              const el = document.getElementById("tutorial-section");
              if (el) el.scrollIntoView({ behavior: "smooth" });
            });
            return;
          }
        }
      }

      alert(`Gerakan "${query}" tidak ditemukan.`);
    },

    async logout() {
      const authStore = useAuthStore();
      await authStore.logout();
      this.$router.push("/login");
    }
  }
};
</script>