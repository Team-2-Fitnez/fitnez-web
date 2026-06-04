<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { manualRegistrationApi } from '../api/manualRegistrationApi'
import type { ManualPaymentMethod, MembershipPackage, ProspectiveRegistration } from '../types/membership'
import StatusBadge from '../components/ui/StatusBadge.vue'

const route = useRoute()

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

  // Check route query param for pre-selection
  const targetPackageCode = route.query.package as string | undefined
  if (targetPackageCode) {
    const matched = packageResponse.data.find(p => p.code === targetPackageCode)
    if (matched) {
      selectedPackageId.value = matched.id
    } else {
      // Default to "PKG_1_MONTH" if not found
      const defaultPkg = packageResponse.data.find(p => p.code === 'PKG_1_MONTH') || packageResponse.data[0]
      selectedPackageId.value = defaultPkg?.id || null
    }
  } else {
    // Default to "PKG_1_MONTH" if no query parameter
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
  <div class="bg-surface-bright text-on-surface antialiased min-h-screen flex flex-col gradient-bg Outfit pt-20">
    <!-- TopNavBar (Shared Component, identical to Landing Page) -->
    <nav class="fixed top-0 w-full z-[100] transition-all duration-300 nav-scrolled py-2" id="navbar">
      <div class="flex justify-between items-center px-gutter max-w-container-max mx-auto">
        <div class="flex items-center gap-2">
          <span class="font-headline-lg text-headline-lg font-extrabold text-on-background">Fitnez Gym</span>
        </div>
        <div class="hidden md:flex gap-stack-lg items-center">
          <RouterLink class="nav-link text-on-surface-variant hover:text-primary transition-colors font-label-bold text-label-bold" to="/#features">Features</RouterLink>
          <RouterLink class="nav-link text-on-surface-variant hover:text-primary transition-colors font-label-bold text-label-bold" to="/#packages">Packages</RouterLink>
          <RouterLink class="nav-link text-on-surface-variant hover:text-primary transition-colors font-label-bold text-label-bold" to="/#how-it-works">How it Works</RouterLink>
          <RouterLink class="nav-link text-on-surface-variant hover:text-primary transition-colors font-label-bold text-label-bold" to="/faq">FAQ</RouterLink>
        </div>
        <div class="flex gap-4">
          <RouterLink to="/login/member" class="hidden sm:block text-primary font-label-bold text-label-bold hover:bg-primary-container/10 px-4 py-2 rounded-lg transition-all text-center">Login</RouterLink>
          <RouterLink to="/register" class="bg-primary text-on-primary font-label-bold text-label-bold px-6 py-2.5 rounded-full hover:shadow-lg hover:scale-105 active:scale-95 transition-all text-center">Join Now</RouterLink>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow w-full max-w-container-max mx-auto px-gutter py-section-padding-mobile md:py-section-padding-desktop">
      
      <!-- Step 1 Form Screen -->
      <div v-if="!registration">
        <!-- Header -->
        <div class="mb-12 flex justify-between items-end flex-wrap gap-4">
          <div>
            <p class="font-label-bold text-label-bold text-primary uppercase tracking-widest mb-2">PROSPECTIVE MEMBER</p>
            <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Create your Fitnez registration.</h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant">Fill personal data, select a package, choose QRIS or bank transfer, then upload payment proof.</p>
          </div>
          <RouterLink to="/registration-status" class="px-6 py-2 border-2 border-outline rounded-full font-label-bold text-label-bold text-on-surface hover:bg-surface-variant transition-colors text-center block sm:inline-block">
            Check Status
          </RouterLink>
        </div>

        <form @submit.prevent="startRegistration" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
          <!-- Column 1: Personal Data -->
          <div class="lg:col-span-5 flex flex-col gap-8">
            <div class="bg-surface-container-lowest rounded-xl p-8 shadow-sm border border-surface-variant h-full flex flex-col justify-between">
              <div>
                <h2 class="font-headline-lg text-headline-lg text-on-surface mb-8">Personal data</h2>
                <div class="flex flex-col gap-6">
                  <div class="flex flex-col gap-2">
                    <label class="font-label-bold text-label-bold text-on-surface">Full Name</label>
                    <input 
                      v-model="values.fullName"
                      class="w-full h-12 px-4 rounded-lg border bg-transparent focus:border-primary focus:ring-1 focus:ring-primary transition-colors text-body-md"
                      :class="errors.fullName ? 'border-error focus:border-error focus:ring-error' : 'border-outline'"
                      required 
                      type="text"
                      placeholder="Enter your full name"
                    />
                    <span v-if="errors.fullName" class="text-error text-xs font-semibold mt-1">{{ errors.fullName }}</span>
                  </div>
                  <div class="flex flex-col gap-2">
                    <label class="font-label-bold text-label-bold text-on-surface">Email</label>
                    <input 
                      v-model="values.email"
                      class="w-full h-12 px-4 rounded-lg border bg-transparent focus:border-primary focus:ring-1 focus:ring-primary transition-colors text-body-md"
                      :class="errors.email ? 'border-error focus:border-error focus:ring-error' : 'border-outline'"
                      required 
                      type="email"
                      placeholder="Enter your email address"
                    />
                    <span v-if="errors.email" class="text-error text-xs font-semibold mt-1">{{ errors.email }}</span>
                  </div>
                  <div class="flex flex-col gap-2">
                    <label class="font-label-bold text-label-bold text-on-surface">Phone</label>
                    <input 
                      v-model="values.phone"
                      class="w-full h-12 px-4 rounded-lg border bg-transparent focus:border-primary focus:ring-1 focus:ring-primary transition-colors text-body-md"
                      :class="errors.phone ? 'border-error focus:border-error focus:ring-error' : 'border-outline'"
                      required 
                      type="tel"
                      placeholder="Enter your phone number"
                    />
                    <span v-if="errors.phone" class="text-error text-xs font-semibold mt-1">{{ errors.phone }}</span>
                  </div>
                  <div class="flex flex-col gap-2">
                    <label class="font-label-bold text-label-bold text-on-surface">Password</label>
                    <input 
                      v-model="values.password"
                      class="w-full h-12 px-4 rounded-lg border bg-transparent focus:border-primary focus:ring-1 focus:ring-primary transition-colors text-body-md"
                      :class="errors.password ? 'border-error focus:border-error focus:ring-error' : 'border-outline'"
                      required 
                      type="password"
                      placeholder="Create a strong password"
                    />
                    <span v-if="errors.password" class="text-error text-xs font-semibold mt-1">{{ errors.password }}</span>
                  </div>
                  <div class="flex flex-col gap-2">
                    <label class="font-label-bold text-label-bold text-on-surface">Confirm Password</label>
                    <input 
                      v-model="values.passwordConfirmation"
                      class="w-full h-12 px-4 rounded-lg border bg-transparent focus:border-primary focus:ring-1 focus:ring-primary transition-colors text-body-md"
                      :class="errors.passwordConfirmation ? 'border-error focus:border-error focus:ring-error' : 'border-outline'"
                      required 
                      type="password"
                      placeholder="Confirm your password"
                    />
                    <span v-if="errors.passwordConfirmation" class="text-error text-xs font-semibold mt-1">{{ errors.passwordConfirmation }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Column 2: Package & Payment -->
          <div class="lg:col-span-7 flex flex-col gap-8">
            <!-- Step 2: Package -->
            <div class="bg-surface-container-lowest rounded-xl p-8 shadow-sm border border-surface-variant">
              <h2 class="font-headline-lg text-headline-lg text-on-surface mb-8">Choose package</h2>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <label v-for="item in packages" :key="item.id" class="cursor-pointer relative block h-full">
                  <div v-if="item.code === 'PKG_12_MONTHS_PREMIUM'" class="absolute -top-3 right-4 bg-brand-orange text-white text-xs font-bold px-3 py-1 rounded-full z-10">Recommended</div>
                  <input type="radio" :value="item.id" v-model="selectedPackageId" class="sr-only" />
                  <div :class="[
                    'p-6 rounded-xl transition-all duration-200 h-full flex flex-col justify-between',
                    selectedPackageId === item.id 
                      ? 'border-2 border-primary bg-surface-variant shadow-sm' 
                      : 'border border-outline-variant hover:border-primary bg-surface-container-lowest'
                  ]">
                    <div>
                      <div class="flex justify-between items-start mb-2 gap-2">
                        <h3 class="font-headline-lg text-[24px] font-bold text-on-surface leading-tight">
                          {{ item.name.replace(/ Basic| Plus| Premium/, '') }}<br/>{{ item.name.match(/Basic|Plus|Premium/)?.[0] || '' }}
                        </h3>
                        <span class="font-label-bold text-label-bold text-brand-orange whitespace-nowrap">Rp {{ Number(item.price).toLocaleString('en-US') }}</span>
                      </div>
                      <p class="font-body-md text-body-md text-on-surface-variant">{{ item.duration_months }} month(s)</p>
                    </div>
                    <div v-if="item.free_class_access" class="mt-4">
                      <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full">Free Class Access</span>
                    </div>
                  </div>
                </label>
              </div>
            </div>

            <!-- Step 3: Payment Method -->
            <div class="bg-surface-container-lowest rounded-xl p-8 shadow-sm border border-surface-variant">
              <h2 class="font-headline-lg text-headline-lg text-on-surface mb-8">Payment method</h2>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                <label v-for="method in methods" :key="method.id" class="cursor-pointer block">
                  <input type="radio" :value="method.id" v-model="selectedMethodId" class="sr-only" />
                  <div :class="[
                    'p-6 rounded-xl transition-all duration-200 h-full',
                    selectedMethodId === method.id 
                      ? 'border-2 border-primary bg-surface-variant shadow-sm' 
                      : 'border border-outline-variant hover:border-primary bg-surface-container-lowest'
                  ]">
                    <h3 class="font-headline-lg text-[20px] font-bold text-on-surface mb-2">{{ method.display_name }}</h3>
                    <p class="font-body-md text-body-md text-on-surface-variant text-sm">{{ method.instructions }}</p>
                  </div>
                </label>
              </div>

              <!-- General Error -->
              <div v-if="errors.fullName && !values.fullName" class="p-4 rounded-xl bg-error-container text-on-error-container text-sm font-bold mb-4 border border-error">
                {{ errors.fullName }}
              </div>

              <!-- Submit Button -->
              <button 
                class="w-full bg-on-secondary-fixed text-white font-label-bold text-label-bold py-4 rounded-xl hover:bg-opacity-90 transition-all duration-200 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed" 
                type="submit"
                :disabled="loading || isSubmitting"
              >
                {{ loading ? 'Creating...' : 'Create Registration & Show Payment Info' }}
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- Step 2 Success & Payment Info Screen -->
      <div v-else>
        <!-- Header -->
        <div class="mb-12 flex justify-between items-end flex-wrap gap-4">
          <div>
            <p class="font-label-bold text-label-bold text-primary uppercase tracking-widest mb-2">REGISTRATION PENDING</p>
            <h1 class="font-headline-xl text-headline-xl text-on-surface mb-2">Registration Created!</h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant">Please complete payment and upload your payment proof to activate your account.</p>
          </div>
          <RouterLink to="/registration-status" class="px-6 py-2 border-2 border-outline rounded-full font-label-bold text-label-bold text-on-surface hover:bg-surface-variant transition-colors text-center block sm:inline-block">
            Check Status
          </RouterLink>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
          <!-- Column 1: Details & Summary -->
          <div class="lg:col-span-5 flex flex-col gap-6">
            <div class="bg-surface-container-lowest rounded-xl p-8 shadow-sm border border-surface-variant flex flex-col gap-6">
              <div class="flex justify-between items-center gap-4 flex-wrap pb-4 border-b border-surface-variant">
                <div>
                  <p class="font-label-bold text-xs uppercase text-on-surface-variant">Registration Code</p>
                  <div class="flex items-center gap-2 mt-1">
                    <h2 class="font-headline-lg text-[22px] font-black text-on-surface select-all leading-none">{{ registration.registration_code }}</h2>
                    <button 
                      @click="copyCode"
                      class="text-primary hover:text-opacity-80 p-1 flex items-center justify-center rounded-lg hover:bg-surface-variant transition-colors"
                      title="Copy code"
                    >
                      <span class="material-symbols-outlined text-lg">content_copy</span>
                    </button>
                  </div>
                </div>
                <StatusBadge :status="registration.status" />
              </div>

              <div class="bg-surface-container rounded-xl p-6 border border-surface-variant">
                <p class="font-label-bold text-xs uppercase text-on-surface-variant mb-1">Amount to Pay</p>
                <p class="text-3xl font-black text-brand-orange">Rp {{ Number(registration.amount).toLocaleString('en-US') }}</p>
              </div>

              <div class="p-6 rounded-xl border border-outline-variant bg-surface-container-lowest flex flex-col justify-between">
                <div>
                  <h3 class="font-headline-lg text-[20px] font-bold text-on-surface leading-tight mb-2">{{ selectedPackage?.name }}</h3>
                  <p class="font-body-md text-body-md text-on-surface-variant">{{ selectedPackage?.duration_months }} month(s) Membership</p>
                </div>
                <div v-if="selectedPackage?.free_class_access" class="mt-4">
                  <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full">Free Class Access</span>
                </div>
              </div>

              <p class="font-body-md text-body-md text-on-surface-variant text-sm mt-2">
                <span class="font-bold text-on-surface">Important:</span> Save your registration code carefully. You can use it later to check if the admin has approved your membership request.
              </p>
            </div>
          </div>

          <!-- Column 2: Bank details, QRIS & upload form -->
          <div class="lg:col-span-7 flex flex-col gap-8">
            <div class="bg-surface-container-lowest rounded-xl p-8 shadow-sm border border-surface-variant flex flex-col gap-8">
              <div>
                <h2 class="font-headline-lg text-headline-lg text-on-surface mb-2">Payment Instructions</h2>
                <p class="font-body-md text-body-md text-on-surface-variant">Follow instructions below to transfer your payment.</p>
              </div>

              <div class="bg-surface-container rounded-xl p-6 border border-surface-variant flex flex-col gap-4">
                <div>
                  <p class="font-label-bold text-xs uppercase text-on-surface-variant mb-1">Selected Payment Method</p>
                  <h3 class="font-headline-lg text-[20px] font-bold text-on-surface">{{ selectedMethod?.display_name }}</h3>
                  <p class="font-body-md text-body-md text-on-surface-variant text-sm mt-1">{{ selectedMethod?.instructions }}</p>
                </div>

                <!-- Bank Transfer details -->
                <div v-if="selectedMethod?.type === 'bank_transfer'" class="bg-surface-container-lowest rounded-lg p-4 border border-outline-variant flex flex-col gap-2 mt-2">
                  <div class="flex justify-between text-sm py-1 border-b border-surface-container">
                    <span class="text-on-surface-variant font-medium">Bank Name</span>
                    <span class="font-bold text-on-surface">{{ selectedMethod?.bank_name }}</span>
                  </div>
                  <div class="flex justify-between text-sm py-1 border-b border-surface-container">
                    <span class="text-on-surface-variant font-medium">Account Number</span>
                    <span class="font-bold text-on-surface select-all">{{ selectedMethod?.account_number }}</span>
                  </div>
                  <div class="flex justify-between text-sm py-1">
                    <span class="text-on-surface-variant font-medium">Account Name</span>
                    <span class="font-bold text-on-surface">{{ selectedMethod?.account_name }}</span>
                  </div>
                </div>

                <!-- QRIS Image -->
                <div v-if="selectedMethod?.type === 'qris'" class="flex flex-col items-center justify-center p-4 mt-2 bg-white rounded-lg border border-outline-variant w-fit mx-auto">
                  <img 
                    :src="selectedMethod?.qris_image_url || ''" 
                    alt="QRIS Code" 
                    class="h-64 w-64 object-contain"
                  />
                  <span class="font-label-bold text-[10px] text-on-surface-variant mt-2 tracking-widest uppercase">Scan QRIS Code to Pay</span>
                </div>
              </div>

              <!-- Upload Section -->
              <div class="bg-surface-container rounded-xl p-6 border border-surface-variant flex flex-col gap-4">
                <div>
                  <h3 class="font-headline-lg text-[20px] font-bold text-on-surface mb-1">Upload Payment Proof</h3>
                  <p class="font-body-md text-body-md text-on-surface-variant text-sm">Please upload transaction receipt in JPG, PNG, or WebP format.</p>
                </div>

                <div class="flex flex-col gap-3">
                  <!-- File selection UI -->
                  <div class="relative w-full h-16 border-2 border-dashed border-outline-variant rounded-lg hover:border-primary transition-colors flex items-center justify-center bg-surface-container-lowest cursor-pointer group">
                    <input 
                      type="file" 
                      accept="image/*" 
                      class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                      @change="onProofChange"
                    />
                    <div class="flex items-center gap-2 text-on-surface-variant group-hover:text-primary">
                      <span class="material-symbols-outlined text-xl">upload_file</span>
                      <span class="font-label-bold text-sm">
                        {{ proofFile ? proofFile.name : 'Choose payment proof image' }}
                      </span>
                    </div>
                  </div>

                  <div class="flex flex-col sm:flex-row gap-3 mt-2">
                    <button 
                      @click="uploadProof"
                      :disabled="loading || !proofFile"
                      class="flex-1 bg-on-secondary-fixed text-white font-label-bold text-label-bold py-3.5 rounded-lg hover:bg-opacity-90 transition-all duration-200 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      {{ loading ? 'Uploading...' : 'Upload Proof' }}
                    </button>
                    <RouterLink 
                      to="/registration-status" 
                      class="px-6 py-3.5 border-2 border-outline rounded-lg font-label-bold text-label-bold text-on-surface hover:bg-surface-variant transition-colors text-center block sm:inline-block"
                    >
                      Check Status
                    </RouterLink>
                  </div>
                </div>
              </div>

              <!-- Alert notifications -->
              <div v-if="message" class="p-4 rounded-xl bg-green-100 text-green-800 text-sm font-bold border border-green-300">
                {{ message }}
              </div>
              <div v-if="errors.fullName" class="p-4 rounded-xl bg-error-container text-on-error-container text-sm font-bold border border-error">
                {{ errors.fullName }}
              </div>
            </div>
          </div>
        </div>
      </div>

    </main>

    <!-- Footer -->
    <footer class="bg-on-background dark:bg-on-background w-full mt-auto">
      <div class="flex flex-col md:flex-row justify-between items-center w-full px-gutter py-8 max-w-container-max mx-auto font-body-md text-body-md">
        <div class="font-headline-lg text-headline-lg text-surface-lowest mb-4 md:mb-0">
          Fitnez Gym
        </div>
        <div class="flex flex-wrap justify-center gap-6 text-primary-fixed dark:text-primary-fixed-dim">
          <RouterLink class="text-secondary-fixed-dim hover:text-surface-bright transition-colors duration-200 opacity-80 hover:opacity-100 transition-opacity" to="/">Home</RouterLink>
          <RouterLink class="text-secondary-fixed-dim hover:text-surface-bright transition-colors duration-200 opacity-80 hover:opacity-100 transition-opacity" to="/faq">FAQ</RouterLink>
          <RouterLink class="text-secondary-fixed-dim hover:text-surface-bright transition-colors duration-200 opacity-80 hover:opacity-100 transition-opacity" to="/privacy-cookie-policy">Privacy & Cookie Policy</RouterLink>
        </div>
        <div class="text-secondary-fixed-dim mt-4 md:mt-0 text-sm">
          © 2024 Fitnez Gym. All rights reserved.
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;800&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');

.Outfit {
  font-family: 'Outfit', sans-serif !important;
}

.gradient-bg {
  background: linear-gradient(135deg, #eef2f6 0%, #fef1eb 100%) !important;
}

/* Typography styles matching custom properties in HTML mockup */
.font-headline-xl {
  font-family: 'Outfit', sans-serif !important;
  font-size: 48px !important;
  line-height: 1.2 !important;
  letter-spacing: -0.02em !important;
  font-weight: 700 !important;
}
.font-headline-lg {
  font-family: 'Outfit', sans-serif !important;
  font-size: 32px !important;
  line-height: 1.3 !important;
  font-weight: 700 !important;
}
.font-body-lg {
  font-family: 'Outfit', sans-serif !important;
  font-size: 20px !important;
  line-height: 1.6 !important;
  font-weight: 400 !important;
}
.font-body-md {
  font-family: 'Outfit', sans-serif !important;
  font-size: 16px !important;
  line-height: 1.6 !important;
  font-weight: 400 !important;
}
.font-label-bold {
  font-family: 'Outfit', sans-serif !important;
  font-size: 14px !important;
  line-height: 1.0 !important;
  letter-spacing: 0.05em !important;
  font-weight: 700 !important;
}

/* Custom color utility classes mapped from tailwind config block */
.bg-surface-bright { background-color: #f8f9ff !important; }
.text-on-surface { color: #0b1c30 !important; }
.text-on-surface-variant { color: #424754 !important; }
.bg-surface-variant { background-color: #d3e4fe !important; }
.bg-surface-container-lowest { background-color: #ffffff !important; }
.text-brand-orange { color: #f97316 !important; }
.bg-brand-orange { background-color: #f97316 !important; }
.border-outline-variant { border-color: #c2c6d6 !important; }
.border-outline { border-color: #727785 !important; }
.border-primary { border-color: #005ac2 !important; }
.bg-primary { background-color: #0058be !important; }
.text-primary { color: #0058be !important; }
.bg-on-secondary-fixed { background-color: #131b2e !important; }
.bg-on-background { background-color: #0b1c30 !important; }
.text-surface-lowest { color: #ffffff !important; }
.text-secondary-fixed-dim { color: #bec6e0 !important; }
.text-error { color: #ba1a1a !important; }
.border-error { border-color: #ba1a1a !important; }
.bg-error-container { background-color: #ffdad6 !important; }
.text-on-error-container { color: #93000a !important; }

/* Custom margins / spacing properties */
.px-gutter { padding-left: 24px !important; padding-right: 24px !important; }
.max-w-container-max { max-width: 1280px !important; }
.py-section-padding-mobile { padding-top: 64px !important; padding-bottom: 64px !important; }
.py-section-padding-desktop { padding-top: 120px !important; padding-bottom: 120px !important; }

.nav-scrolled {
  background-color: rgba(248, 249, 255, 0.9) !important;
  backdrop-filter: blur(12px) !important;
  border-bottom: 1px solid rgba(194, 198, 214, 0.3) !important;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
}

.nav-link {
  position: relative;
}

.nav-link::after {
  content: '';
  position: absolute;
  width: 0;
  height: 2px;
  bottom: -4px;
  left: 0;
  background-color: #0058be;
  transition: width 0.3s ease;
}

.nav-link:hover::after {
  width: 100%;
}
</style>
