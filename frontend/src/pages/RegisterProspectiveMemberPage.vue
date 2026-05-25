<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { manualRegistrationApi } from '../api/manualRegistrationApi'
import type { ManualPaymentMethod, MembershipPackage, ProspectiveRegistration } from '../types/membership'
import FitnezButton from '../components/ui/FitnezButton.vue'
import FitnezCard from '../components/ui/FitnezCard.vue'
import FitnezInput from '../components/ui/FitnezInput.vue'
import StatusBadge from '../components/ui/StatusBadge.vue'

const packages = ref<MembershipPackage[]>([])
const methods = ref<ManualPaymentMethod[]>([])
const selectedPackageId = ref<number | null>(null)
const selectedMethodId = ref<number | null>(null)
const registration = ref<ProspectiveRegistration | null>(null)
const proofFile = ref<File | null>(null)

const loading = ref(false)
const message = ref('')

const selectedPackage = computed(() => packages.value.find((item) => item.id === selectedPackageId.value))
const selectedMethod = computed(() => methods.value.find((item) => item.id === selectedMethodId.value))

const schema = toTypedSchema(z.object({
  fullName: z.string().min(3, 'Full name must be at least 3 characters.'),
  email: z.string().email('Email format is not valid.'),
  phone: z.string().optional().default(''),
  password: z.string()
    .min(16, 'Password must be at least 16 characters.')
    .regex(/[A-Z]/, 'Must contain one uppercase letter.')
    .regex(/[a-z]/, 'Must contain one lowercase letter.')
    .regex(/[^A-Za-z0-9]/, 'Must contain one special character.'),
  passwordConfirmation: z.string(),
}).refine(data => data.password === data.passwordConfirmation, {
  message: 'Password confirmation does not match.',
  path: ['passwordConfirmation'],
}))

const { handleSubmit, errors, values, isSubmitting, setFieldError } = useForm({
  validationSchema: schema,
  initialValues: {
    fullName: '',
    email: '',
    phone: '',
    password: '',
    passwordConfirmation: '',
  },
})

onMounted(async () => {
  const [packageResponse, methodResponse] = await Promise.all([
    manualRegistrationApi.packages(),
    manualRegistrationApi.paymentMethods(),
  ])

  packages.value = packageResponse.data
  methods.value = methodResponse.data
  selectedPackageId.value = packageResponse.data[0]?.id || null
  selectedMethodId.value = methodResponse.data[0]?.id || null
})

const startRegistration = handleSubmit(async (formValues) => {
  if (!selectedPackageId.value) {
    setFieldError('fullName', 'Please choose a package.')
    return
  }
  if (!selectedMethodId.value) {
    setFieldError('fullName', 'Please choose a payment method.')
    return
  }

  loading.value = true
  message.value = ''

  try {
    const response = await manualRegistrationApi.start({
      full_name: formValues.fullName.trim(),
      email: formValues.email.trim().toLowerCase(),
      phone: formValues.phone.trim(),
      password: formValues.password,
      password_confirmation: formValues.passwordConfirmation,
      membership_package_id: Number(selectedPackageId.value),
      manual_payment_method_id: Number(selectedMethodId.value),
    })

    registration.value = response.data
    localStorage.setItem('fitnez_last_registration_code', response.data.registration_code)
    localStorage.setItem('fitnez_last_registration_email', response.data.email)
    message.value = 'Registration created. Complete payment and upload proof.'
  } catch (e: any) {
    setFieldError('fullName', e?.message || 'Failed to create registration.')
  } finally {
    loading.value = false
  }
})

function onProofChange(event: Event) {
  const input = event.target as HTMLInputElement
  proofFile.value = input.files?.[0] || null
}

async function uploadProof() {
  if (!registration.value || !proofFile.value) {
    message.value = ''
    setFieldError('fullName', 'Please choose payment proof image.')
    return
  }

  message.value = ''
  loading.value = true

  try {
    const response = await manualRegistrationApi.uploadProof({
      registration_code: registration.value.registration_code,
      email: registration.value.email,
      payment_proof: proofFile.value,
    })

    registration.value = response.data
    localStorage.setItem('fitnez_last_registration_code', response.data.registration_code)
    localStorage.setItem('fitnez_last_registration_email', response.data.email)
    message.value = 'Payment proof uploaded. Please wait for admin approval.'
  } catch (e: any) {
    setFieldError('fullName', e?.message || 'Failed to upload proof.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="page section">
    <div class="page-container">
      <header style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem; align-items: end; margin-bottom: 1.5rem;">
        <div>
          <p class="eyebrow">Prospective Member</p>
          <h1 class="title-lg">Create your Fitnez registration.</h1>
          <p class="text-muted">
            Fill personal data, select a package, choose QRIS or bank transfer, then upload payment proof.
          </p>
        </div>

        <RouterLink to="/registration-status" class="button button-ghost">Check Status</RouterLink>
      </header>

      <div v-if="!registration" style="display: grid; gap: 1.25rem; grid-template-columns: repeat(auto-fit, minmax(min(100%, 430px), 1fr));">
        <FitnezCard>
          <div style="display: grid; gap: 1rem;">
            <div>
              <p class="eyebrow-accent">STEP 1</p>
              <h2 class="title-md">Personal data</h2>
            </div>

            <FitnezInput v-model="values.fullName" label="Full Name" :error="errors.fullName" />
            <FitnezInput v-model="values.email" label="Email" type="email" :error="errors.email" />
            <FitnezInput v-model="values.phone" label="Phone" />
            <FitnezInput v-model="values.password" label="Password" type="password" :error="errors.password" />
            <FitnezInput v-model="values.passwordConfirmation" label="Confirm Password" type="password" :error="errors.passwordConfirmation" />
          </div>
        </FitnezCard>

        <div style="display: grid; gap: 1.25rem;">
          <FitnezCard>
            <div>
              <p class="eyebrow-accent">STEP 2</p>
              <h2 class="title-md">Choose package</h2>
            </div>

            <div class="choice-grid" style="margin-top: 1rem;">
              <label
                v-for="item in packages"
                :key="item.id"
                :class="['choice-card', selectedPackageId === item.id && 'choice-card-active']"
              >
                <input v-model="selectedPackageId" type="radio" :value="item.id" style="display: none;" />
                <div style="display: flex; justify-content: space-between; gap: 0.75rem; align-items: start;">
                  <div>
                    <p class="title-md">{{ item.name }}</p>
                    <p class="text-muted">{{ item.duration_months }} month(s)</p>
                    <p v-if="item.free_class_access" class="status status-success">Free class access</p>
                  </div>
                  <p style="font-weight: 900; color: var(--color-orange-dark);">Rp {{ Number(item.price).toLocaleString('id-ID') }}</p>
                </div>
              </label>
            </div>
          </FitnezCard>

          <FitnezCard>
            <div>
              <p class="eyebrow-accent">STEP 3</p>
              <h2 class="title-md">Payment method</h2>
            </div>

            <div class="choice-grid" style="margin-top: 1rem;">
              <label
                v-for="method in methods"
                :key="method.id"
                :class="['choice-card', selectedMethodId === method.id && 'choice-card-active']"
              >
                <input v-model="selectedMethodId" type="radio" :value="method.id" style="display: none;" />
                <p class="title-md">{{ method.display_name }}</p>
                <p class="text-muted">{{ method.instructions }}</p>
              </label>
            </div>
          </FitnezCard>

          <p v-if="errors.fullName" class="alert alert-error">{{ errors.fullName }}</p>

          <FitnezButton size="lg" :disabled="loading || isSubmitting" style="width: 100%;" @click="startRegistration">
            {{ loading ? 'Creating...' : 'Create Registration & Show Payment Info' }}
          </FitnezButton>
        </div>
      </div>

      <div v-else style="display: grid; gap: 1.25rem; grid-template-columns: repeat(auto-fit, minmax(min(100%, 430px), 1fr));">
        <FitnezCard>
          <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 0.75rem; align-items: start;">
            <div>
              <p class="stat-label">Registration Code</p>
              <h2 class="title-md" style="word-break: break-word;">{{ registration.registration_code }}</h2>
            </div>
            <StatusBadge :status="registration.status" />
          </div>

          <div class="panel panel-blue" style="margin-top: 1.5rem;">
            <p style="color: rgba(255,255,255,0.9); font-weight: 800;">Amount to Pay</p>
            <p class="stat-value" style="color: white;">Rp {{ Number(registration.amount).toLocaleString('id-ID') }}</p>
          </div>

          <div class="panel" style="background: var(--color-cream); margin-top: 1rem;">
            <p class="title-md">{{ selectedPackage?.name }}</p>
            <p class="text-muted">{{ selectedPackage?.duration_months }} month(s)</p>
          </div>

          <p class="text-muted">
            Save this registration code. Use it to check whether admin has approved your account.
          </p>
        </FitnezCard>

        <FitnezCard>
          <h2 class="title-md">Payment instruction</h2>
          <p class="text-muted">Pay manually using the selected method, then upload payment proof.</p>

          <div class="panel" style="margin-top: 1rem;">
            <p class="title-md">{{ selectedMethod?.display_name }}</p>
            <p class="text-muted">{{ selectedMethod?.instructions }}</p>

            <div v-if="selectedMethod?.type === 'bank_transfer'" class="panel" style="background: var(--color-cream); margin-top: 1rem;">
              <p>Bank: {{ selectedMethod.bank_name }}</p>
              <p>Account Number: {{ selectedMethod.account_number }}</p>
              <p>Account Name: {{ selectedMethod.account_name }}</p>
            </div>

            <img
              v-if="selectedMethod?.type === 'qris'"
              :src="selectedMethod.qris_image_url || ''"
              alt="QRIS"
              style="background: white; border: 1px solid var(--color-border); border-radius: var(--radius-md); height: 16rem; margin-top: 1rem; max-width: 100%; object-fit: contain; padding: 1rem; width: 16rem;"
            />
          </div>

          <div class="panel" style="background: var(--color-cream); margin-top: 1rem;">
            <p class="title-md">Upload payment proof</p>
            <p class="text-muted">Use JPG, PNG, or WebP transaction screenshot.</p>
            <input style="margin-top: 1rem; width: 100%;" type="file" accept="image/*" @change="onProofChange" />

            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 1rem;">
              <FitnezButton :disabled="loading" @click="uploadProof">
                {{ loading ? 'Uploading...' : 'Upload Proof' }}
              </FitnezButton>
              <RouterLink to="/registration-status" class="button button-black">Check Status</RouterLink>
            </div>
          </div>

          <p v-if="message" class="alert alert-success">{{ message }}</p>
          <p v-if="errors.fullName" class="alert alert-error">{{ errors.fullName }}</p>
        </FitnezCard>
      </div>
    </div>
  </main>
</template>
