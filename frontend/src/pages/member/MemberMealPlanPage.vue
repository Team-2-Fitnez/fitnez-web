<script setup lang="ts">
import { ref, onMounted } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import FitnezInput from '../../components/ui/FitnezInput.vue'
import FitnezButton from '../../components/ui/FitnezButton.vue'
import { useMealPlanStore } from '../../stores/mealPlanStore'

const store = useMealPlanStore()

// Form kalkulator
const form = ref({
  berat: '',
  tinggi: '',
  usia: '',
  gender: 'laki',
  aktivitas: 1.55,
  target: 0,
})
const hasil = ref<null | {
  bmr: number
  tdee: number
  targetKal: number
  karbo: number
  protein: number
  lemak: number
}>(null)
const kalkulatorError = ref('')
const saveSuccess = ref('')

// Form catat makanan
const newFood = ref({ nama: '', kalori: '' })
const foodError = ref('')
const showLimitModal = ref(false)

onMounted(() => {
  store.loadAll()
})

function hitung() {
  kalkulatorError.value = ''
  const { berat, tinggi, usia, gender, aktivitas, target } = form.value
  if (!berat || !tinggi || !usia) {
    kalkulatorError.value = '⚠ Isi semua field!'
    return
  }
  const b = Number(berat), t = Number(tinggi), u = Number(usia)
  const bmr = gender === 'laki'
    ? Math.round((10 * b) + (6.25 * t) - (5 * u) + 5)
    : Math.round((10 * b) + (6.25 * t) - (5 * u) - 161)
  const tdee = Math.round(bmr * Number(aktivitas))
  const targetKal = Math.round(tdee + Number(target))
  hasil.value = {
    bmr, tdee, targetKal,
    karbo: Math.round((targetKal * 0.50) / 4),
    protein: Math.round((targetKal * 0.30) / 4),
    lemak: Math.round((targetKal * 0.20) / 9),
  }
}

async function pakaiSebagaiLimit() {
  if (!hasil.value) return
  saveSuccess.value = ''
  try {
    await store.saveMealPlan({
      daily_limit: hasil.value.targetKal,
      bmr: hasil.value.bmr,
      tdee: hasil.value.tdee,
      target_kal: hasil.value.targetKal,
    })
    saveSuccess.value = '✅ Daily limit berhasil diperbarui!'
  } catch {
    kalkulatorError.value = '⚠ Gagal simpan!'
  }
}

async function tambahMakanan() {
  foodError.value = ''
  if (store.dailyLimit <= 0) {
    showLimitModal.value = true
    return
  }
  if (!newFood.value.nama || !newFood.value.kalori || Number(newFood.value.kalori) <= 0) {
    foodError.value = '⚠ Isi semua field dengan benar!'
    return
  }
  try {
    await store.addFood(newFood.value.nama, Number(newFood.value.kalori))
    newFood.value = { nama: '', kalori: '' }
  } catch {
    foodError.value = '⚠ Gagal tambah makanan!'
  }
}

async function hapusMakanan(id: number) {
  try {
    await store.deleteFood(id)
  } catch {
    foodError.value = '⚠ Gagal hapus makanan!'
  }
}
</script>

<template>
  <WorkspaceLayout
    role="member"
    sidebar-title="Member"
    title="Meal Plan"
    subtitle="Kalkulator gizi dan pencatatan makanan harian."
    :sidebar-items="memberSidebarItems"
  >
    <!-- Info Cards -->
    <div class="feature-grid">
      <FitnezCard>
        <p class="eyebrow">Kalkulator</p>
        <h2 class="title-md">Kalkulator Nilai Gizi</h2>
        <p class="text-muted">Hitung BMR, TDEE, dan kebutuhan makronutrien harianmu.</p>
      </FitnezCard>
      <FitnezCard>
        <p class="eyebrow">Rencana Makan</p>
        <h2 class="title-md">Perencanaan Makanan Harian</h2>
        <p class="text-muted">Catat makanan dan pantau kalori harianmu.</p>
      </FitnezCard>
      <FitnezCard>
        <p class="eyebrow">Makro</p>
        <h2 class="title-md">Protein, Karbo, Lemak</h2>
        <p class="text-muted">Seimbangkan makronutrien untuk cutting, bulking, atau maintenance.</p>
      </FitnezCard>
    </div>

    <!-- Progress Harian -->
    <FitnezCard style="margin-top: 1.25rem;">
      <h2 class="title-md">Progress Kalori Harian</h2>
      <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-top: 1rem;">
        <div class="panel panel-orange">
          <p class="eyebrow-accent">Daily Limit</p>
          <p class="stat-value">{{ store.dailyLimit.toLocaleString() }}</p>
          <p class="text-muted">kkal</p>
        </div>
        <div class="panel panel-orange">
          <p class="eyebrow-accent">Dikonsumsi</p>
          <p class="stat-value">{{ store.totalCalories.toLocaleString() }}</p>
          <p class="text-muted">kkal</p>
        </div>
        <div class="panel panel-orange">
          <p class="eyebrow-accent">Sisa</p>
          <p class="stat-value">{{ store.remaining.toLocaleString() }}</p>
          <p class="text-muted">kkal</p>
        </div>
        <div class="panel panel-orange">
          <p class="eyebrow-accent">Progress</p>
          <p class="stat-value">{{ store.percentage }}%</p>
          <p class="text-muted">dari target</p>
        </div>
      </div>
      <!-- Progress Bar -->
      <div style="margin-top: 1rem; background: #eee; border-radius: 999px; height: 12px;">
        <div
          :style="{
            width: store.percentage + '%',
            background: store.percentage >= 100 ? '#e74c3c' : 'var(--color-primary, #f97316)',
            height: '12px',
            borderRadius: '999px',
            transition: 'width 0.4s ease'
          }"
        ></div>
      </div>
      <p style="font-size: 0.82rem; color: var(--muted); margin-top: 6px;">
        {{ store.dailyLimit === 0 ? 'Set daily limit di Kalkulator Gizi dulu.' : store.percentage >= 100 ? '⚠ Kamu sudah melebihi batas kalori!' : 'Sisa ' + Math.max(0, store.remaining).toLocaleString() + ' kkal lagi.' }}
      </p>
    </FitnezCard>

    <!-- Kalkulator Gizi -->
    <FitnezCard style="margin-top: 1.25rem;">
      <h2 class="title-md">🧮 Kalkulator Kalori Harian</h2>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
        <FitnezInput v-model="form.berat" label="Berat Badan (kg)" type="number" placeholder="70" />
        <FitnezInput v-model="form.tinggi" label="Tinggi Badan (cm)" type="number" placeholder="170" />
        <FitnezInput v-model="form.usia" label="Usia" type="number" placeholder="25" />
        <div>
          <label style="font-size: 0.85rem; font-weight: 600;">Jenis Kelamin</label>
          <select v-model="form.gender" style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid #ddd; margin-top: 4px;">
            <option value="laki">Laki-laki</option>
            <option value="perempuan">Perempuan</option>
          </select>
        </div>
      </div>
      <div style="margin-top: 1rem;">
        <label style="font-size: 0.85rem; font-weight: 600;">Tingkat Aktivitas</label>
        <select v-model.number="form.aktivitas" style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid #ddd; margin-top: 4px;">
          <option value="1.2">Tidak aktif (jarang olahraga)</option>
          <option value="1.375">Aktif ringan (1-3x/minggu)</option>
          <option value="1.55">Aktif sedang (3-5x/minggu)</option>
          <option value="1.725">Aktif tinggi (6-7x/minggu)</option>
          <option value="1.9">Sangat aktif (2x sehari)</option>
        </select>
      </div>
      <div style="margin-top: 1rem;">
        <label style="font-size: 0.85rem; font-weight: 600;">Target</label>
        <select v-model.number="form.target" style="width: 100%; padding: 0.6rem; border-radius: 8px; border: 1px solid #ddd; margin-top: 4px;">
          <option value="-500">Turun Berat Badan (-500 kkal)</option>
          <option value="0">Jaga Berat Badan</option>
          <option value="300">Naik Berat Badan (+300 kkal)</option>
        </select>
      </div>
      <div v-if="kalkulatorError" style="color: red; margin-top: 0.5rem;">{{ kalkulatorError }}</div>
      <div style="margin-top: 1rem;">
        <FitnezButton @click="hitung">🧮 Hitung Kebutuhan Gizi</FitnezButton>
      </div>

      <!-- Hasil Kalkulator -->
      <div v-if="hasil" style="margin-top: 1.5rem;">
        <h3 class="title-md">Hasil Perhitungan</h3>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 1rem;">
          <div class="panel panel-orange">
            <p class="eyebrow-accent">🔥 BMR</p>
            <p class="stat-value">{{ hasil.bmr.toLocaleString() }}</p>
            <p class="text-muted">kkal/hari</p>
          </div>
          <div class="panel panel-orange">
            <p class="eyebrow-accent">⚡ TDEE</p>
            <p class="stat-value">{{ hasil.tdee.toLocaleString() }}</p>
            <p class="text-muted">kkal/hari</p>
          </div>
          <div class="panel panel-orange">
            <p class="eyebrow-accent">🎯 Target Kalori</p>
            <p class="stat-value">{{ hasil.targetKal.toLocaleString() }}</p>
            <p class="text-muted">kkal/hari</p>
          </div>
        </div>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 1rem;">
          <div class="panel panel-orange">
            <p class="eyebrow-accent">🍚 Karbohidrat</p>
            <p class="stat-value">{{ hasil.karbo }}g</p>
            <p class="text-muted">50% dari kalori</p>
          </div>
          <div class="panel panel-orange">
            <p class="eyebrow-accent">🥩 Protein</p>
            <p class="stat-value">{{ hasil.protein }}g</p>
            <p class="text-muted">30% dari kalori</p>
          </div>
          <div class="panel panel-orange">
            <p class="eyebrow-accent">🥑 Lemak</p>
            <p class="stat-value">{{ hasil.lemak }}g</p>
            <p class="text-muted">20% dari kalori</p>
          </div>
        </div>
        <div v-if="saveSuccess" style="color: green; margin-top: 0.5rem;">{{ saveSuccess }}</div>
        <div style="margin-top: 1rem;">
          <FitnezButton @click="pakaiSebagaiLimit">✅ Pakai Sebagai Daily Limit</FitnezButton>
        </div>
      </div>
    </FitnezCard>

    <!-- Catat Makanan -->
    <FitnezCard style="margin-top: 1.25rem;">
      <h2 class="title-md">🍽️ Catat Makanan</h2>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
        <FitnezInput v-model="newFood.nama" label="Nama Makanan" placeholder="cth: Nasi Goreng" />
        <FitnezInput v-model="newFood.kalori" label="Kalori (kkal)" type="number" placeholder="cth: 350" />
      </div>
      <div v-if="foodError" style="color: red; margin-top: 0.5rem;">{{ foodError }}</div>
      <div style="margin-top: 1rem;">
        <FitnezButton @click="tambahMakanan">+ Tambah Makanan</FitnezButton>
      </div>
    </FitnezCard>

    <!-- Log Makanan -->
    <FitnezCard style="margin-top: 1.25rem;">
      <h2 class="title-md">🗒️ Log Makanan Hari Ini</h2>
      <p v-if="store.foods.length === 0" class="text-muted" style="margin-top: 1rem;">Belum ada makanan yang dicatat.</p>
      <ul style="list-style: none; padding: 0; margin-top: 1rem;">
        <li
          v-for="f in [...store.foods].reverse()"
          :key="f.id"
          style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #eee;"
        >
          <div>
            <p style="font-weight: 600;">{{ f.food_name }}</p>
            <p class="text-muted">{{ Number(f.calories).toLocaleString() }} kkal</p>
          </div>
          <FitnezButton @click="hapusMakanan(f.id)">✕ Hapus</FitnezButton>
        </li>
      </ul>
    </FitnezCard>

    <!-- Modal Limit Kosong -->
    <Transition name="fade">
      <div v-if="showLimitModal" style="position: fixed; top:0; left:0; right:0; bottom:0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 2000;" @click.self="showLimitModal = false">
        <div style="background: white; padding: 2.5rem; border-radius: 20px; max-width: 450px; width: 100%; text-align: center;">
          <div style="font-size: 4rem;">🥗</div>
          <h2 style="margin-bottom: 0.75rem;">Ups, Tunggu Sebentar!</h2>
          <p style="color: var(--muted); margin-bottom: 2rem;">Hitung dulu kebutuhan kalori tubuhmu di Kalkulator Gizi sebelum mencatat makanan!</p>
          <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            <FitnezButton @click="showLimitModal = false">Tutup & Hitung Sekarang</FitnezButton>
          </div>
        </div>
      </div>
    </Transition>

  </WorkspaceLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>