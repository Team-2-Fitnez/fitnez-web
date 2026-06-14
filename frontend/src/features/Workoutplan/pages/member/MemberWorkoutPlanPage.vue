<template>
  <WorkspaceLayout
    role="member"
    title="Workout Plan"
    subtitle="🔥 DON'T GIVE UP! Push your limits and achieve your goals! 🔥"
    sidebarTitle="My Training"
    :sidebarItems="memberSidebarItems"
  >
    <div class="workout-content-wrapper member-workout-redesign">
      <form id="workout-form" class="workout-shell-card" @submit.prevent="handleSubmit">
        <div class="section-head">
          <div>
            <p class="eyebrow">Workout Builder</p>
            <h3>Add New Workout</h3>
          </div>
          <span class="soft-badge">{{ editId !== null ? 'Edit Mode' : 'New Plan' }}</span>
        </div>

        <div class="input-grid">
          <div class="form-field">
            <label class="form-label">Date</label>
            <input
              type="date"
              class="form-input"
              v-model="formData.date"
              @change="onDateChange"
              required
            />
          </div>

          <div class="form-field">
            <label class="form-label">Day</label>
            <input
              type="text"
              class="form-input bg-gray-100"
              v-model="formData.day"
              disabled
            />
          </div>

          <div class="form-field">
            <label class="form-label">Category</label>
            <select
              class="form-input"
              v-model="formData.category"
              @change="onCategoryChange"
              required
            >
              <option value="" disabled>Select Category</option>
              <option value="Cardio">Cardio</option>
              <option value="Strength">Strength</option>
              <option value="Flexibility">Flexibility</option>
            </select>
          </div>

          <div class="form-field">
            <label class="form-label">Exercise</label>
            <select
              class="form-input"
              v-model="formData.name"
              required
            >
              <option value="" disabled>-- Select Exercise --</option>
              <option v-for="exercise in exerciseOptions" :key="exercise" :value="exercise">
                {{ exercise }}
              </option>
            </select>
          </div>

          <div class="form-field">
            <label class="form-label">Sets</label>
            <input type="number" class="form-input" v-model="formData.set" required />
          </div>

          <div class="form-field">
            <label class="form-label">Weight (kg)</label>
            <input type="number" class="form-input" v-model="formData.weight" required />
          </div>

          <div class="form-field">
            <label class="form-label">Reps</label>
            <input type="number" class="form-input" v-model="formData.reps" required />
          </div>

          <div class="form-field">
            <label class="form-label">Duration (mins)</label>
            <input type="number" class="form-input" v-model="formData.duration" />
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="button button-primary">
            {{ editId !== null ? 'Save Changes' : 'Add to Workout List' }}
          </button>
        </div>
      </form>

      <section id="dashboard" class="workout-shell-card">
        <div class="section-head">
          <div>
            <p class="eyebrow">Training Timeline</p>
            <h2>Workout Schedule List</h2>
          </div>
          
          <div class="stat-pill-row">
            <div class="stat-pill">
              <span>Sessions: <b>{{ stats.sessions }}</b></span>
            </div>
            <div class="stat-pill">
              <span>Weight: <b>{{ stats.weight }} kg</b></span>
            </div>
            <div class="stat-pill">
              <span>Completed: <b>{{ stats.percent }}%</b></span>
            </div>
          </div>
        </div>

        <div v-if="workouts.length === 0" class="empty-state">
          <p>No workout data yet.</p>
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
                {{ (item.date || '').substring(0, 10) }} - {{ item.set }} Set - {{ item.weight }}kg - {{ item.reps }} Reps
              </p>
            </div>
            
            <div class="workout-actions">
              <button type="button" class="btn-icon" @click="showTutorial(item.name, item.category)" title="Tutorial">Video</button>
              <button
                type="button"
                class="btn-icon"
                @click="toggleWorkout(item.id)"
                :title="item.completed ? 'Mark as incomplete' : 'Mark as completed'"
              >
                {{ item.completed ? '↩' : '✔' }}
              </button>
              <button type="button" class="btn-icon text-blue-600" @click="editWorkout(item.id)">✎</button>
              <button type="button" class="btn-icon text-red-600" @click="deleteWorkout(item.id)">Delete</button>
            </div>
          </div>
        </div>

        <div class="footer-actions">
          <button type="button" class="button button-ghost" @click="exportToCSV">Download CSV</button>
          <button v-if="workouts.length > 0" type="button" class="button button-danger" @click="clearAllWorkouts">Clear All</button>
        </div>
      </section>

      <!-- Tutorial Section -->
      <transition name="fade">
        <section v-if="tutorialVisible" id="tutorial-overlay" class="tutorial-overlay">
          <div class="tutorial-modal workout-shell-card">
            <div class="flex items-center justify-between mb-6">
              <h2 class="title-md">Tutorial: {{ tutorialData.title }}</h2>
              <button type="button" @click="closeTutorial" class="button button-ghost p-2">Close</button>
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
import WorkspaceLayout from '@/shared/components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '@/shared/components/layout/sidebarItems'
import api from '@/shared/api/axios';

const daftarHari = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

const workoutData = {
  Cardio: {
    "Running (Lari)": {
      desc: "Running is an effective cardio exercise to burn calories and improve heart health.",
      video: "https://www.youtube.com/embed/9L2b2khySLE"
    },
    "Cycling (Sepeda)": {
      desc: "Cycling trains leg muscles, improves stamina, and is good for joint health.",
      video: "https://www.youtube.com/embed/f0T0f_jF8-8"
    },
    "Jump Rope": {
      desc: "Skipping or jumping rope is a high-intensity cardio workout that burns many calories.",
      video: "https://www.youtube.com/embed/u3zgHI899k8"
    },
    "Burpees": {
      desc: "Burpees combine squat, push-up, and jump. A very effective full-body workout.",
      video: "https://www.youtube.com/embed/auQLvF8UORs"
    },
    "Jumping Jacks": {
      desc: "Jumping jacks train coordination and balance.",
      video: "https://www.youtube.com/embed/c4DAnQ6DtF8"
    },
    "Mountain Climbers": {
      desc: "Mountain climbers mimic climbing a mountain in a plank position. Trains core, shoulders, and legs.",
      video: "https://www.youtube.com/embed/nmwgirgXLYM"
    },
    "Swimming": {
      desc: "Swimming trains all muscles without placing stress on joints.",
      video: "https://www.youtube.com/embed/5HLW2aiXWW0"
    }
  },
  Strength: {
    "Bench Press": {
      desc: "Bench press trains chest muscles, triceps, and shoulders.",
      video: "https://www.youtube.com/embed/rT7DgHISCHw"
    },
    "Squat": {
      desc: "Squats train thighs, glutes, and core.",
      video: "https://www.youtube.com/embed/MVMNk0HiTMg"
    },
    "Deadlift": {
      desc: "Deadlifts train lower back, glutes, and hamstrings.",
      video: "https://www.youtube.com/embed/ytGaGIn3SjE"
    },
    "Overhead Press": {
      desc: "Overhead press trains shoulders and triceps.",
      video: "https://www.youtube.com/embed/QAQ64hK4SkI"
    },
    "Pull Up": {
      desc: "Pull-ups train back, shoulders, and arms.",
      video: "https://www.youtube.com/embed/eGo4IYlbE5g"
    },
    "Push Up": {
      desc: "Push-ups train chest, triceps, and core.",
      video: "https://www.youtube.com/embed/IODxDxX7oi4"
    },
    "Bicep Curl": {
      desc: "Bicep curl trains front arm muscles (biceps).",
      video: "https://www.youtube.com/embed/ykJmrZ5v0BA"
    },
    "Tricep Extension": {
      desc: "Tricep extension trains back arm muscles (triceps).",
      video: "https://www.youtube.com/embed/nRiJVZDpdL0"
    },
    "Lunges": {
      desc: "Lunges train thighs and glutes.",
      video: "https://www.youtube.com/embed/QOVaHwm-Q6U"
    },
    "Plank": {
      desc: "Planks train core and body stability.",
      video: "https://www.youtube.com/embed/ASdvN_XEl_c"
    }
  },
  Flexibility: {
    "Cobra Stretch": {
      desc: "Cobra stretch trains spine flexibility.",
      video: "https://www.youtube.com/embed/JDcdpQCzk8U"
    },
    "Child's Pose": {
      desc: "Child's pose is a relaxation pose that stretches the lower back.",
      video: "https://www.youtube.com/embed/2N09E1K8u88"
    },
    "Hamstring Stretch": {
      desc: "Hamstring stretch stretches the back thigh muscles (hamstrings).",
      video: "https://www.youtube.com/embed/6_9_WpY6p00"
    },
    "Butterfly Stretch": {
      desc: "Butterfly stretch opens hips and inner thighs.",
      video: "https://www.youtube.com/embed/MdE_m2f_v_4"
    },
    "Cat-Cow Stretch": {
      desc: "Cat-cow stretch improves spine flexibility.",
      video: "https://www.youtube.com/embed/w_U0S3H_E88"
    },
    "Yoga Sun Salutation": {
      desc: "Sun salutation is a sequence of yoga poses that train flexibility and strength.",
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
        title: "Select Exercise",
        desc: "Search or select an exercise to see the tutorial.",
        video: ""
      },
      searchQuery: "",
      loading: false,
      refreshInterval: null
    };
  },

  computed: {
    workoutInsights() {
      return [
        { label: 'Sessions', value: this.stats.sessions, hint: 'logged workouts' },
        { label: 'Completed', value: `${this.stats.percent}%`, hint: 'completion rate' },
        { label: 'Weight', value: `${this.stats.weight} kg`, hint: 'total volume' },
      ]
    },
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
    this.refreshInterval = setInterval(() => {
      if (document.visibilityState === 'visible') {
        this.fetchWorkoutsSilent();
      }
    }, 10000);
  },

  beforeUnmount() {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
    }
  },

  methods: {
    async fetchWorkoutsSilent() {
      try {
        const response = await api.get('/workout-plans');
        this.workouts = response.data;
      } catch {
        // Ignore background refresh errors
      }
    },
    async fetchWorkouts() {
      this.loading = true;
      try {
        const response = await api.get('/workout-plans');
        this.workouts = response.data;
      } catch (error) {
        window.showFitnezToast('Failed to load workout schedule.', 'error');
      }
    },

    onDateChange() {
      if (this.formData.date) {
        const d = new Date(this.formData.date + 'T00:00:00');
        this.formData.day = daftarHari[d.getDay()];
      }
    },

    onCategoryChange() {
      this.formData.name = '';
    },

    async handleSubmit() {
      if (!this.formData.name || !this.formData.date) {
        alert("Select date and exercise!");
        return;
      }

      this.loading = true;
      const payload = {
        date: this.formData.date,
        day: this.formData.day,
        category: this.formData.category,
        name: this.formData.name,
        set: parseInt(this.formData.set) || 0,
        weight: parseFloat(this.formData.weight) || 0,
        reps: parseInt(this.formData.reps) || 0,
        duration: parseInt(this.formData.duration) || 0,
      };

      try {
        if (this.editId !== null) {
          await api.put(`/workout-plans/${this.editId}`, this.formData);
          window.showFitnezToast('Workout schedule successfully updated!');
          this.editId = null;
        } else {
          await api.post('/workout-plans', this.formData);
          window.showFitnezToast('New workout schedule has been added!');
        }
        await this.fetchWorkouts();
        this.resetForm();
      } catch (error) {
        console.error("Save failed:", error);
        window.showFitnezToast('Failed to save workout schedule.', 'error');
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
      this.editId = null;
    },

    async toggleWorkout(id) {
      const item = this.workouts.find(w => w.id === id);
      if (!item) return;
      try {
        await api.put(`/workout-plans/${id}`, { completed: !item.completed });
        await this.fetchWorkouts();
      } catch (error) {
        window.showFitnezToast('Failed to update workout status.', 'error');
      }
    },

    async deleteWorkout(id) {
      if (!confirm("Delete this schedule?")) return;
      
      try {
        await api.delete(`/workout-plans/${id}`);
        window.showFitnezToast('Workout schedule deleted.', 'success');
        await this.fetchWorkouts();
      } catch (error) {
        console.error("Delete failed:", error);
        window.showFitnezToast('Failed to delete schedule.', 'error');
      }
    },

    async clearAllWorkouts() {
      if (!confirm("Delete your entire workout schedule?")) return;
      try {
        await api.delete('/workout-plans/clear-all');
        window.showFitnezToast('Entire workout schedule has been deleted.');
        await this.fetchWorkouts();
      } catch (error) {
        console.error("Clear all failed:", error);
        window.showFitnezToast('Failed to delete all schedules.', 'error');
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
      let csv = "data:text/csv;charset=utf-8,Date,Day,Category,Exercise,Set,Weight,Reps\n";
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
        alert(`Tutorial for "${name}" is not available yet.`);
      }
    },

    closeTutorial() {
      this.tutorialVisible = false;
      this.tutorialData = {
        title: "Select Exercise",
        desc: "Search or select an exercise to see the tutorial.",
        video: ""
      };
      this.searchQuery = "";
    },

    searchVideo() {
      const query = this.searchQuery.toLowerCase().trim();
      if (query.length < 2) {
        alert("Enter at least 2 characters to search");
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

      alert(`Exercise "${query}" not found.`);
    }
  }
};
</script>

<style scoped>
.member-workout-redesign {
  display: grid;
  gap: 1.25rem;
}

.workout-shell-card {
  background: #ffffff;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 1.25rem;
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
  padding: clamp(1rem, 3vw, 1.5rem);
}

.section-head {
  align-items: flex-start;
  border-bottom: 1px solid rgba(15, 23, 42, 0.08);
  display: flex;
  gap: 1rem;
  justify-content: space-between;
  margin-bottom: 1.25rem;
  padding-bottom: 1rem;
}

.section-head h2,
.section-head h3 {
  color: #0f172a;
  font-size: clamp(1.15rem, 3vw, 1.45rem);
  font-weight: 950;
  margin: 0.2rem 0 0;
}

.eyebrow {
  color: #2563eb;
  font-size: 0.72rem;
  font-weight: 950;
  letter-spacing: 0.16em;
  margin: 0;
  text-transform: uppercase;
}

.soft-badge,
.badge,
.stat-pill {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 999px;
  color: #1d4ed8;
  font-size: 0.75rem;
  font-weight: 900;
  padding: 0.45rem 0.75rem;
  white-space: nowrap;
}

.input-grid {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.form-actions,
.footer-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  margin-top: 1.25rem;
}

.stat-pill-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.workout-list {
  display: grid;
  gap: 0.85rem;
}

.workout-card {
  align-items: center;
  background: #f8fafc;
  border: 1px solid rgba(15, 23, 42, 0.06);
  border-radius: 1rem;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
  padding: 1rem;
}

.completed-card {
  background: #f0fdf4;
  border-color: #bbf7d0;
}

.workout-main-info {
  min-width: 0;
}

.status-dot {
  border-radius: 999px;
  display: inline-flex;
  height: 0.7rem;
  width: 0.7rem;
}

.workout-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  justify-content: flex-end;
}

.btn-icon {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  color: #334155;
  cursor: pointer;
  font-size: 0.78rem;
  font-weight: 900;
  padding: 0.45rem 0.65rem;
}

.empty-state {
  background: #f8fafc;
  border: 2px dashed #cbd5e1;
  border-radius: 1rem;
  color: #64748b;
  font-weight: 800;
  margin-bottom: 1rem;
  padding: 2rem;
  text-align: center;
}

.tutorial-overlay {
  align-items: center;
  background: rgba(15, 23, 42, 0.62);
  display: flex;
  inset: 0;
  justify-content: center;
  padding: 1rem;
  position: fixed;
  z-index: 100;
}

.tutorial-modal {
  max-height: 92vh;
  max-width: 840px;
  overflow: auto;
  width: 100%;
}

.video-wrapper {
  aspect-ratio: 16 / 9;
  background: #0f172a;
  border-radius: 1rem;
  overflow: hidden;
}

.video-wrapper iframe {
  height: 100%;
  width: 100%;
}

@media (max-width: 1024px) {
  .input-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 700px) {
  .section-head,
  .workout-card,
  .form-actions,
  .footer-actions {
    align-items: stretch;
    flex-direction: column;
  }

  .input-grid {
    grid-template-columns: 1fr;
  }

  .workout-actions {
    justify-content: flex-start;
  }
}
</style>
