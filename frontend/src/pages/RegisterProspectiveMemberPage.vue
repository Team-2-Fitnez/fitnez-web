<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { manualRegistrationApi } from '../api/manualRegistrationApi'
import type { ManualPaymentMethod, MembershipPackage, ProspectiveRegistration } from '../types/membership'
import StatusBadge from '../components/ui/StatusBadge.vue'
import LandingPublicNav from '../components/landing/LandingPublicNav.vue'
import LandingPublicFooter from '../components/landing/LandingPublicFooter.vue'

const route = useRoute()
const router = useRouter()

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

function formatCurrency(value: number) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value)
}

function monthlyCost(pkg: MembershipPackage) {
  return formatCurrency(Math.round(pkg.price / pkg.duration_months))
}

const schema = toTypedSchema(z.object({
  fullName: z.string().min(3, 'Full name must be at least 3 characters.'),
  email: z.string().email('Email format is not valid.'),
  phone: z.string().optional().default(''),
  birthDate: z.string().optional().default(''),
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

const { handleSubmit, errors, values, isSubmitting, setFieldError, defineField } = useForm({
  validationSchema: schema,
  initialValues: {
    fullName: '',
    email: '',
    phone: '',
    birthDate: '',
    password: '',
    passwordConfirmation: '',
  },
})

const [fullName, fullNameProps] = defineField('fullName')
const [email, emailProps] = defineField('email')
const [phone, phoneProps] = defineField('phone')
const [birthDate, birthDateProps] = defineField('birthDate')
const [password, passwordProps] = defineField('password')
const [passwordConfirmation, passwordConfirmationProps] = defineField('passwordConfirmation')

onMounted(async () => {
  const [packageResponse, methodResponse] = await Promise.all([
    manualRegistrationApi.packages(),
    manualRegistrationApi.paymentMethods(),
  ])

  packages.value = packageResponse.data
  methods.value = methodResponse.data

  const targetPackageCode = route.query.package as string | undefined
  if (targetPackageCode) {
    const matched = packageResponse.data.find(p => p.code === targetPackageCode)
    if (matched) {
      selectedPackageId.value = matched.id
    } else {
      const defaultPkg = packageResponse.data.find(p => p.code === 'PKG_1_MONTH') || packageResponse.data[0]
      selectedPackageId.value = defaultPkg?.id || null
    }
  } else {
    const defaultPkg = packageResponse.data.find(p => p.code === 'PKG_1_MONTH') || packageResponse.data[0]
    selectedPackageId.value = defaultPkg?.id || null
  }

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
      birth_date: formValues.birthDate || undefined,
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
    message.value = 'Payment proof uploaded successfully! Redirecting to Check Status page in 3 seconds...'
    setTimeout(() => {
      router.push('/registration-status')
    }, 3000)
  } catch (e: any) {
    setFieldError('fullName', e?.message || 'Failed to upload proof.')
  } finally {
    loading.value = false
  }
}

function copyCode() {
  if (registration.value) {
    navigator.clipboard.writeText(registration.value.registration_code)
    const originalMessage = message.value
    message.value = 'Registration code copied to clipboard!'
    setTimeout(() => {
      if (message.value === 'Registration code copied to clipboard!') {
        message.value = originalMessage
      }
    }, 2000)
  }
}
</script>

<template>
  <div class="reg-page">
    <LandingPublicNav variant="static" />

    <!-- Main Content -->
    <main class="reg-main">

      <!-- Step 1 Form Screen -->
      <div v-if="!registration">
        <!-- Header -->
        <div class="reg-header">
          <div>
            <p class="reg-eyebrow">PROSPECTIVE MEMBER</p>
            <h1 class="reg-title">Create your Fitnez registration.</h1>
            <p class="reg-subtitle">Fill personal data, select a package, choose QRIS or bank transfer, then upload payment proof.</p>
          </div>
          <RouterLink to="/registration-status" class="btn-outline-pill">
            Check Status
          </RouterLink>
        </div>

        <form @submit.prevent="startRegistration" class="reg-form-grid">
          <!-- Column 1: Personal Data -->
          <div class="reg-col-left">
            <div class="reg-card reg-card-full">
              <h2 class="card-heading">Personal Data</h2>
              <div class="fields-stack">
                <!-- Full Name (Required) -->
                <div class="field-group">
                  <label class="field-label">Full Name <span class="required-mark">*</span></label>
                  <input
                    v-model="fullName"
                    v-bind="fullNameProps"
                    class="field-input"
                    :class="{ 'field-error': errors.fullName }"
                    required
                    type="text"
                    placeholder="Enter your full name"
                  />
                  <span v-if="errors.fullName" class="error-text">{{ errors.fullName }}</span>
                </div>
                <!-- Email (Required) -->
                <div class="field-group">
                  <label class="field-label">Email <span class="required-mark">*</span></label>
                  <input
                    v-model="email"
                    v-bind="emailProps"
                    class="field-input"
                    :class="{ 'field-error': errors.email }"
                    required
                    type="email"
                    placeholder="Enter your email address"
                  />
                  <span v-if="errors.email" class="error-text">{{ errors.email }}</span>
                </div>
                <!-- Phone (Optional) -->
                <div class="field-group">
                  <label class="field-label">Phone <span class="optional-mark">(Optional)</span></label>
                  <input
                    v-model="phone"
                    v-bind="phoneProps"
                    class="field-input"
                    :class="{ 'field-error': errors.phone }"
                    type="tel"
                    placeholder="Enter your phone number"
                  />
                  <span v-if="errors.phone" class="error-text">{{ errors.phone }}</span>
                </div>
                <!-- Birth Date (Optional) -->
                <div class="field-group">
                  <label class="field-label">Date of Birth <span class="optional-mark">(Optional)</span></label>
                  <input
                    v-model="birthDate"
                    v-bind="birthDateProps"
                    class="field-input"
                    type="date"
                  />
                </div>
                <!-- Password (Required) -->
                <div class="field-group">
                  <label class="field-label">Password <span class="required-mark">*</span></label>
                  <input
                    v-model="password"
                    v-bind="passwordProps"
                    class="field-input"
                    :class="{ 'field-error': errors.password }"
                    required
                    type="password"
                    placeholder="Min 16 chars, 1 upper, 1 lower, 1 special"
                  />
                  <span v-if="errors.password" class="error-text">{{ errors.password }}</span>
                </div>
                <!-- Confirm Password (Required) -->
                <div class="field-group">
                  <label class="field-label">Confirm Password <span class="required-mark">*</span></label>
                  <input
                    v-model="passwordConfirmation"
                    v-bind="passwordConfirmationProps"
                    class="field-input"
                    :class="{ 'field-error': errors.passwordConfirmation }"
                    required
                    type="password"
                    placeholder="Confirm your password"
                  />
                  <span v-if="errors.passwordConfirmation" class="error-text">{{ errors.passwordConfirmation }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Column 2: Package & Payment -->
          <div class="reg-col-right">
            <!-- Step 2: Package -->
            <div class="reg-card">
              <h2 class="card-heading">Choose Membership Package</h2>
              <div class="pkg-grid">
                <label v-for="item in packages" :key="item.id" class="pkg-label">
                  <div v-if="item.code === 'PKG_12_MONTHS_PREMIUM'" class="pkg-badge">Best Value</div>
                  <input type="radio" :value="item.id" v-model="selectedPackageId" class="sr-only" />
                  <div :class="['pkg-card', selectedPackageId === item.id && 'pkg-card-active']">
                    <div class="pkg-top">
                      <h3 class="pkg-name">{{ item.name }}</h3>
                      <p class="pkg-duration">{{ item.duration_months }} month{{ item.duration_months > 1 ? 's' : '' }}</p>
                    </div>
                    <div class="pkg-bottom">
                      <p class="pkg-price">{{ formatCurrency(item.price) }}</p>
                      <p class="pkg-monthly">{{ monthlyCost(item) }}/month</p>
                      <span v-if="item.free_class_access" class="pkg-perk">✓ Free Class Access</span>
                    </div>
                  </div>
                </label>
              </div>

              <!-- Price Summary Callout -->
              <div v-if="selectedPackage" class="price-callout">
                <div class="price-callout-label">Total amount to pay</div>
                <div class="price-callout-value">{{ formatCurrency(selectedPackage.price) }}</div>
                <div class="price-callout-info">{{ selectedPackage.name }} — {{ selectedPackage.duration_months }} month{{ selectedPackage.duration_months > 1 ? 's' : '' }} membership</div>
              </div>
            </div>

            <!-- Step 3: Payment Method -->
            <div class="reg-card">
              <h2 class="card-heading">Payment Method</h2>
              <div class="method-grid">
                <label v-for="method in methods" :key="method.id" class="method-label">
                  <input type="radio" :value="method.id" v-model="selectedMethodId" class="sr-only" />
                  <div :class="['method-card', selectedMethodId === method.id && 'method-card-active']">
                    <div class="method-icon">
                      <span v-if="method.type === 'qris'" class="material-symbols-outlined">qr_code_2</span>
                      <span v-else class="material-symbols-outlined">account_balance</span>
                    </div>
                    <div>
                      <h3 class="method-name">{{ method.display_name }}</h3>
                      <p class="method-desc">{{ method.instructions }}</p>
                    </div>
                  </div>
                </label>
              </div>

              <!-- General Error -->
              <div v-if="errors.fullName && !values.fullName" class="alert-error">
                {{ errors.fullName }}
              </div>

              <!-- Submit Button -->
              <button
                class="btn-primary-full"
                type="submit"
                :disabled="loading || isSubmitting"
              >
                {{ loading ? 'Creating Registration...' : 'Create Registration & Show Payment Info' }}
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- Step 2 Success & Payment Info Screen -->
      <div v-else>
        <!-- Header -->
        <div class="reg-header">
          <div>
            <p class="reg-eyebrow">REGISTRATION PENDING</p>
            <h1 class="reg-title">Registration Created!</h1>
            <p class="reg-subtitle">Please complete payment and upload your payment proof to activate your account.</p>
          </div>
          <RouterLink to="/registration-status" class="btn-outline-pill">
            Check Status
          </RouterLink>
        </div>

        <div class="reg-form-grid">
          <!-- Column 1: Details & Summary -->
          <div class="reg-col-left">
            <div class="reg-card">
              <div class="code-row">
                <div>
                  <p class="code-label">Registration Code</p>
                  <div class="code-value-row">
                    <h2 class="code-value">{{ registration.registration_code }}</h2>
                    <button @click="copyCode" class="code-copy-btn" title="Copy code">
                      <span class="material-symbols-outlined">content_copy</span>
                    </button>
                  </div>
                </div>
                <StatusBadge :status="registration.status" />
              </div>

              <!-- Price callout -->
              <div class="price-callout price-callout-lg">
                <div class="price-callout-label">Amount to Pay</div>
                <div class="price-callout-value">{{ formatCurrency(Number(registration.amount)) }}</div>
                <div class="price-callout-info">Please enter exactly this amount when transferring.</div>
              </div>

              <div class="summary-card">
                <h3 class="summary-pkg-name">{{ selectedPackage?.name }}</h3>
                <p class="summary-pkg-duration">{{ selectedPackage?.duration_months }} month{{ (selectedPackage?.duration_months ?? 0) > 1 ? 's' : '' }} Membership</p>
                <span v-if="selectedPackage?.free_class_access" class="pkg-perk">✓ Free Class Access</span>
              </div>

              <p class="reg-notice">
                <span class="notice-bold">Important:</span> Save your registration code carefully. You can use it later to check if the admin has approved your membership request.
              </p>
            </div>
          </div>

          <!-- Column 2: Bank details, QRIS & upload form -->
          <div class="reg-col-right">
            <div class="reg-card">
              <h2 class="card-heading">Payment Instructions</h2>
              <p class="card-subtitle">Follow instructions below to transfer your payment.</p>

              <div class="payment-details-box">
                <div>
                  <p class="detail-label">Selected Payment Method</p>
                  <h3 class="detail-value">{{ selectedMethod?.display_name }}</h3>
                  <p class="detail-desc">{{ selectedMethod?.instructions }}</p>
                </div>

                <!-- Bank Transfer details -->
                <div v-if="selectedMethod?.type === 'bank_transfer'" class="bank-details">
                  <div class="bank-row">
                    <span>Bank Name</span>
                    <span class="bank-val">{{ selectedMethod?.bank_name }}</span>
                  </div>
                  <div class="bank-row">
                    <span>Account Number</span>
                    <span class="bank-val selectable">{{ selectedMethod?.account_number }}</span>
                  </div>
                  <div class="bank-row bank-row-last">
                    <span>Account Name</span>
                    <span class="bank-val">{{ selectedMethod?.account_name }}</span>
                  </div>
                </div>

                <!-- QRIS Image -->
                <div v-if="selectedMethod?.type === 'qris'" class="qris-box">
                  <img
                    :src="selectedMethod?.qris_image_url || ''"
                    alt="QRIS Code"
                    class="qris-img"
                  />
                  <span class="qris-caption">Scan QRIS Code to Pay</span>
                </div>
              </div>

              <!-- Upload Section -->
              <div class="upload-section">
                <h3 class="upload-title">Upload Payment Proof</h3>
                <p class="upload-desc">Please upload transaction receipt in JPG, PNG, or WebP format.</p>

                <div class="file-drop-zone">
                  <input
                    type="file"
                    accept="image/*"
                    class="file-input-hidden"
                    @change="onProofChange"
                  />
                  <div class="file-drop-content">
                    <span class="material-symbols-outlined file-icon">upload_file</span>
                    <span class="file-text">{{ proofFile ? proofFile.name : 'Choose payment proof image' }}</span>
                  </div>
                </div>

                <div class="upload-actions">
                  <button
                    @click="uploadProof"
                    :disabled="loading || !proofFile"
                    class="btn-primary-flex"
                  >
                    {{ loading ? 'Uploading...' : 'Upload Proof' }}
                  </button>
                  <RouterLink to="/registration-status" class="btn-outline-inline">
                    Check Status
                  </RouterLink>
                </div>
              </div>

              <!-- Alert notifications -->
              <div v-if="message" class="alert-success">{{ message }}</div>
              <div v-if="errors.fullName" class="alert-error">{{ errors.fullName }}</div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <LandingPublicFooter />
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');

/* ===== Page Root ===== */
.reg-page {
  font-family: 'Outfit', sans-serif;
  background: linear-gradient(135deg, #eef2f6 0%, #fef1eb 100%);
  color: #0b1c30;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  -webkit-font-smoothing: antialiased;
}

.reg-main {
  flex-grow: 1;
  width: 100%;
  max-width: 1280px;
  margin: 0 auto;
  padding: 120px 24px 80px;
}

/* ===== Header ===== */
.reg-header {
  margin-bottom: 3rem;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  flex-wrap: wrap;
  gap: 1rem;
}

.reg-eyebrow {
  font-size: 14px;
  font-weight: 700;
  color: #0058be;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  margin: 0 0 0.5rem;
}

.reg-title {
  font-size: 42px;
  font-weight: 800;
  line-height: 1.15;
  margin: 0 0 0.5rem;
  color: #0b1c30;
}

.reg-subtitle {
  font-size: 18px;
  color: #424754;
  margin: 0;
  line-height: 1.6;
}

/* ===== Buttons ===== */
.btn-outline-pill {
  padding: 0.65rem 1.5rem;
  border: 2px solid #727785;
  border-radius: 999px;
  font-size: 14px;
  font-weight: 700;
  color: #0b1c30;
  text-decoration: none;
  transition: all 0.2s;
  text-align: center;
  white-space: nowrap;
}
.btn-outline-pill:hover {
  background: #d3e4fe;
  border-color: #0058be;
  color: #0058be;
}

.btn-primary-full {
  width: 100%;
  background: #131b2e;
  color: #fff;
  font-size: 14px;
  font-weight: 700;
  padding: 1rem;
  border-radius: 0.75rem;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  margin-top: 0.5rem;
}
.btn-primary-full:hover { background: #1e293b; }
.btn-primary-full:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-primary-flex {
  flex: 1;
  background: #131b2e;
  color: #fff;
  font-size: 14px;
  font-weight: 700;
  padding: 0.875rem 1.25rem;
  border-radius: 0.75rem;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-primary-flex:hover { background: #1e293b; }
.btn-primary-flex:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-outline-inline {
  padding: 0.875rem 1.5rem;
  border: 2px solid #727785;
  border-radius: 0.75rem;
  font-size: 14px;
  font-weight: 700;
  color: #0b1c30;
  text-decoration: none;
  transition: all 0.2s;
  text-align: center;
}
.btn-outline-inline:hover { background: #d3e4fe; border-color: #0058be; }

/* ===== Form Grid ===== */
.reg-form-grid {
  display: grid;
  grid-template-columns: 5fr 7fr;
  gap: 2rem;
}

.reg-col-left {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.reg-col-right {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* ===== Cards ===== */
.reg-card {
  background: #fff;
  border-radius: 1rem;
  padding: 2rem;
  border: 1px solid #c2c6d6;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.reg-card-full {
  flex: 1;
}

.card-heading {
  font-size: 24px;
  font-weight: 800;
  color: #0b1c30;
  margin: 0;
}

.card-subtitle {
  font-size: 16px;
  color: #424754;
  margin: -0.5rem 0 0;
}

/* ===== Fields ===== */
.fields-stack {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.field-label {
  font-size: 14px;
  font-weight: 700;
  color: #0b1c30;
}

.required-mark {
  color: #dc2626;
  font-weight: 800;
}

.optional-mark {
  color: #94a3b8;
  font-weight: 500;
  font-size: 12px;
}

.field-input {
  width: 100%;
  height: 3rem;
  padding: 0 1rem;
  border-radius: 0.75rem;
  border: 1px solid #c2c6d6;
  background: transparent;
  font-family: 'Outfit', sans-serif;
  font-size: 15px;
  color: #0b1c30;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.field-input:focus {
  border-color: #0058be;
  box-shadow: 0 0 0 2px rgba(0, 88, 190, 0.12);
}

.field-input.field-error {
  border-color: #dc2626;
}

.field-input.field-error:focus {
  box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.12);
}

.error-text {
  color: #dc2626;
  font-size: 12px;
  font-weight: 600;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
}

/* ===== Package Cards ===== */
.pkg-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.pkg-label {
  cursor: pointer;
  position: relative;
  display: block;
}

.pkg-badge {
  position: absolute;
  top: -10px;
  right: 12px;
  background: #f97316;
  color: #fff;
  font-size: 11px;
  font-weight: 800;
  padding: 0.25rem 0.75rem;
  border-radius: 999px;
  z-index: 1;
}

.pkg-card {
  padding: 1.25rem;
  border-radius: 0.875rem;
  border: 1px solid #c2c6d6;
  background: #fff;
  transition: all 0.2s;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 1rem;
}

.pkg-card:hover {
  border-color: #0058be;
}

.pkg-card-active {
  border: 2px solid #0058be;
  background: #f0f5ff;
  box-shadow: 0 0 0 3px rgba(0, 88, 190, 0.08);
}

.pkg-top { }

.pkg-name {
  font-size: 18px;
  font-weight: 800;
  color: #0b1c30;
  margin: 0 0 0.25rem;
  line-height: 1.3;
}

.pkg-duration {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

.pkg-bottom { }

.pkg-price {
  font-size: 20px;
  font-weight: 900;
  color: #f97316;
  margin: 0;
}

.pkg-monthly {
  font-size: 12px;
  color: #64748b;
  margin: 0.15rem 0 0;
}

.pkg-perk {
  display: inline-block;
  margin-top: 0.5rem;
  padding: 0.2rem 0.65rem;
  background: #dcfce7;
  color: #166534;
  font-size: 12px;
  font-weight: 700;
  border-radius: 999px;
}

/* ===== Price Callout ===== */
.price-callout {
  background: linear-gradient(135deg, #fef3c7 0%, #fff7ed 100%);
  border: 2px solid #fbbf24;
  border-radius: 1rem;
  padding: 1.25rem 1.5rem;
  text-align: center;
}

.price-callout-lg {
  padding: 1.5rem 2rem;
}

.price-callout-label {
  font-size: 12px;
  font-weight: 800;
  color: #92400e;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-bottom: 0.25rem;
}

.price-callout-value {
  font-size: 32px;
  font-weight: 900;
  color: #b45309;
  line-height: 1.2;
}

.price-callout-info {
  font-size: 13px;
  color: #92400e;
  margin-top: 0.35rem;
}

/* ===== Payment Method ===== */
.method-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.method-label {
  cursor: pointer;
  display: block;
}

.method-card {
  padding: 1.25rem;
  border-radius: 0.875rem;
  border: 1px solid #c2c6d6;
  background: #fff;
  transition: all 0.2s;
  height: 100%;
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.method-card:hover {
  border-color: #0058be;
}

.method-card-active {
  border: 2px solid #0058be;
  background: #f0f5ff;
  box-shadow: 0 0 0 3px rgba(0, 88, 190, 0.08);
}

.method-icon {
  background: #e0e7ff;
  border-radius: 0.75rem;
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: #3730a3;
}

.method-name {
  font-size: 16px;
  font-weight: 800;
  color: #0b1c30;
  margin: 0 0 0.25rem;
}

.method-desc {
  font-size: 13px;
  color: #64748b;
  margin: 0;
  line-height: 1.4;
}

/* ===== Alerts ===== */
.alert-error {
  padding: 1rem;
  border-radius: 0.75rem;
  background: #fef2f2;
  color: #991b1b;
  font-size: 14px;
  font-weight: 700;
  border: 1px solid #fca5a5;
}

.alert-success {
  padding: 1rem;
  border-radius: 0.75rem;
  background: #f0fdf4;
  color: #166534;
  font-size: 14px;
  font-weight: 700;
  border: 1px solid #86efac;
}

/* ===== Post-Submission: Code Row ===== */
.code-row {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  padding-bottom: 1.25rem;
  border-bottom: 1px solid #e2e8f0;
}

.code-label {
  font-size: 11px;
  font-weight: 800;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin: 0;
}

.code-value-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.25rem;
}

.code-value {
  font-size: 20px;
  font-weight: 900;
  color: #0b1c30;
  margin: 0;
  user-select: all;
}

.code-copy-btn {
  background: none;
  border: none;
  color: #0058be;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s;
}
.code-copy-btn:hover { background: #e0e7ff; }

/* ===== Summary Card ===== */
.summary-card {
  padding: 1.25rem;
  border-radius: 0.875rem;
  border: 1px solid #c2c6d6;
  background: #fff;
}

.summary-pkg-name {
  font-size: 18px;
  font-weight: 800;
  color: #0b1c30;
  margin: 0 0 0.25rem;
}

.summary-pkg-duration {
  font-size: 14px;
  color: #64748b;
  margin: 0;
}

.reg-notice {
  font-size: 14px;
  color: #424754;
  line-height: 1.6;
  margin: 0;
}

.notice-bold {
  font-weight: 800;
  color: #0b1c30;
}

/* ===== Payment Details ===== */
.payment-details-box {
  background: #f8fafc;
  border-radius: 0.875rem;
  padding: 1.5rem;
  border: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.detail-label {
  font-size: 11px;
  font-weight: 800;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin: 0 0 0.25rem;
}

.detail-value {
  font-size: 20px;
  font-weight: 800;
  color: #0b1c30;
  margin: 0;
}

.detail-desc {
  font-size: 13px;
  color: #64748b;
  margin: 0.25rem 0 0;
  line-height: 1.4;
}

.bank-details {
  background: #fff;
  border-radius: 0.75rem;
  padding: 0.75rem 1rem;
  border: 1px solid #e2e8f0;
}

.bank-row {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f1f5f9;
  color: #64748b;
}

.bank-row-last {
  border-bottom: none;
}

.bank-val {
  font-weight: 800;
  color: #0b1c30;
}

.selectable {
  user-select: all;
}

.qris-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1rem;
  background: #fff;
  border-radius: 0.75rem;
  border: 1px solid #e2e8f0;
  width: fit-content;
  margin: 0 auto;
}

.qris-img {
  height: 256px;
  width: 256px;
  object-fit: contain;
}

.qris-caption {
  font-size: 10px;
  font-weight: 800;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-top: 0.5rem;
}

/* ===== Upload Section ===== */
.upload-section {
  background: #f8fafc;
  border-radius: 0.875rem;
  padding: 1.5rem;
  border: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.upload-title {
  font-size: 18px;
  font-weight: 800;
  color: #0b1c30;
  margin: 0;
}

.upload-desc {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}

.file-drop-zone {
  position: relative;
  width: 100%;
  height: 4rem;
  border: 2px dashed #c2c6d6;
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  cursor: pointer;
  transition: border-color 0.2s;
}
.file-drop-zone:hover { border-color: #0058be; }

.file-input-hidden {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
  width: 100%;
  height: 100%;
}

.file-drop-content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #64748b;
}

.file-icon { font-size: 22px; }

.file-text {
  font-size: 14px;
  font-weight: 600;
}

.upload-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 0.5rem;
}

/* ===== Responsive ===== */
@media (max-width: 1024px) {
  .reg-form-grid {
    grid-template-columns: 1fr;
  }
  .reg-title {
    font-size: 32px;
  }
}

@media (max-width: 640px) {
  .reg-main {
    padding-top: 100px;
    padding-bottom: 60px;
  }
  .pkg-grid,
  .method-grid {
    grid-template-columns: 1fr;
  }
  .upload-actions {
    flex-direction: column;
  }
  .reg-title {
    font-size: 26px;
  }
  .price-callout-value {
    font-size: 24px;
  }
}
</style>
