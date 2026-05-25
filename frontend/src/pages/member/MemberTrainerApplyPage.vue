<template>
  <WorkspaceLayout
    role="member"
    sidebar-title="Member"
    title="Daftar Sebagai Trainer"
    subtitle="Lengkapi formulir untuk mengajukan diri sebagai trainer"
    :sidebar-items="memberSidebarItems"
  >
    <div class="content-card">
      <form @submit.prevent="onSubmit" class="form-grid">
        <div class="form-field">
          <label class="form-label">Bidang Keahlian / Spesialisasi</label>
          <select v-model="values.specialization" class="form-input" :class="{ 'input-error': errors.specialization }">
            <option value="">Pilih bidang keahlian...</option>
            <option value="Yoga">Yoga</option>
            <option value="Aerobics">Aerobics</option>
            <option value="Strength Training">Strength Training</option>
            <option value="Mobility">Mobility</option>
            <option value="HIIT">HIIT</option>
            <option value="Functional Fitness">Functional Fitness</option>
            <option value="General Fitness">General Fitness</option>
          </select>
          <p v-if="errors.specialization" class="field-error">{{ errors.specialization }}</p>
          <p v-else class="text-muted" style="font-size: 0.875rem; margin-top: 0.5rem;">
            Pilih bidang keahlian utama Anda
          </p>
        </div>

        <div class="form-field">
          <label class="form-label">Pengalaman (Tahun)</label>
          <input
            v-model.number="values.experience_years"
            type="number"
            min="0"
            max="50"
            placeholder="Contoh: 5"
            class="form-input"
            :class="{ 'input-error': errors.experience_years }"
          />
          <p v-if="errors.experience_years" class="field-error">{{ errors.experience_years }}</p>
          <p v-else class="text-muted" style="font-size: 0.875rem; margin-top: 0.5rem;">
            Berapa tahun pengalaman Anda sebagai trainer?
          </p>
        </div>

        <div class="form-field">
          <label class="form-label">Upload CV (PDF)</label>
          <input
            type="file"
            accept=".pdf"
            @change="onCvChange"
            class="form-input"
          />
          <p v-if="cvFile" class="text-muted" style="font-size: 0.875rem; margin-top: 0.5rem;">
            ✓ {{ cvFile.name }}
          </p>
        </div>

        <div class="form-field">
          <label class="form-label">Upload Sertifikat (PDF)</label>
          <input
            type="file"
            accept=".pdf"
            @change="onCertificateChange"
            class="form-input"
          />
          <p v-if="certificateFile" class="text-muted" style="font-size: 0.875rem; margin-top: 0.5rem;">
            ✓ {{ certificateFile.name }}
          </p>
        </div>

        <p v-if="success" class="alert alert-success">
          Pendaftaran berhasil dikirim! Admin akan meninjau aplikasi Anda.
        </p>

        <div class="form-actions">
          <button type="button" class="button button-ghost" @click="$router.push('/member/hire-trainer')">
            Kembali
          </button>
          <button type="submit" class="button button-primary" :disabled="isSubmitting">
            {{ isSubmitting ? 'Mengirim...' : 'Kirim Pendaftaran' }}
          </button>
        </div>
      </form>
    </div>
  </WorkspaceLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { trainerApplicationApi } from '../../api/trainerApplicationApi'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '../../components/layout/sidebarItems'

const success = ref(false)
const cvFile = ref<File | null>(null)
const certificateFile = ref<File | null>(null)

const schema = toTypedSchema(z.object({
  specialization: z.string().min(1, 'Pilih bidang keahlian Anda.'),
  experience_years: z.number({ invalid_type_error: 'Isi tahun pengalaman.' }).min(0, 'Tidak boleh negatif.'),
}))

const { handleSubmit, errors, values, isSubmitting, setFieldError } = useForm({
  validationSchema: schema,
  initialValues: {
    specialization: '',
    experience_years: 0,
  },
})

function onCvChange(event: Event) {
  const target = event.target as HTMLInputElement
  cvFile.value = target.files?.[0] || null
}

function onCertificateChange(event: Event) {
  const target = event.target as HTMLInputElement
  certificateFile.value = target.files?.[0] || null
}

const onSubmit = handleSubmit(async () => {
  if (!cvFile.value || !certificateFile.value) {
    setFieldError('specialization', 'Mohon upload CV dan Sertifikat.')
    return
  }

  try {
    await trainerApplicationApi.submit(cvFile.value, certificateFile.value)
    success.value = true
    cvFile.value = null
    certificateFile.value = null
    values.specialization = ''
    values.experience_years = 0
  } catch (e: any) {
    setFieldError('specialization', e?.message || 'Gagal mengirim pendaftaran.')
  }
})
</script>

<style scoped>
.content-card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.form-grid {
  display: grid;
  gap: 1.5rem;
}

.form-field {
  display: flex;
  flex-direction: column;
}

.form-label {
  font-weight: 500;
  margin-bottom: 0.5rem;
  color: #333;
}

.form-input {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 1rem;
}

.form-input:focus {
  outline: none;
  border-color: #4a90e2;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.button {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.button-primary {
  background: #4a90e2;
  color: white;
  border: none;
}

.button-primary:hover:not(:disabled) {
  background: #357abd;
}

.button-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.button-ghost {
  background: transparent;
  color: #666;
  border: 1px solid #ddd;
}

.button-ghost:hover {
  background: #f5f5f5;
}

.alert {
  padding: 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
}

.alert-error {
  background: #fee;
  color: #c33;
  border: 1px solid #fcc;
}

.alert-success {
  background: #efe;
  color: #3c3;
  border: 1px solid #cfc;
}

.text-muted {
  color: #666;
}
.input-error { border-color: #e53e3e !important; }
.field-error { color: #e53e3e; font-size: 0.75rem; margin-top: 0.25rem; }
</style>
