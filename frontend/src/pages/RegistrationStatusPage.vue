<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { manualRegistrationApi } from '../api/manualRegistrationApi'
import type { ProspectiveRegistration } from '../types/membership'
import StatusBadge from '../components/ui/StatusBadge.vue'
import LandingPublicNav from '../components/landing/LandingPublicNav.vue'
import LandingPublicFooter from '../components/landing/LandingPublicFooter.vue'

const registrationCode = ref(localStorage.getItem('fitnez_last_registration_code') || '')
const email = ref(localStorage.getItem('fitnez_last_registration_email') || '')
const registration = ref<ProspectiveRegistration | null>(null)
const proofFile = ref<File | null>(null)
const loading = ref(false)
const error = ref('')
const successMessage = ref('')

async function checkStatus() {
  error.value = ''
  successMessage.value = ''

  if (!registrationCode.value.trim() || !email.value.trim()) {
    error.value = 'Registration code and email are required.'
    return
  }

  loading.value = true

  try {
    const response = await manualRegistrationApi.status(registrationCode.value.trim(), email.value.trim())
    registration.value = response.data
    localStorage.setItem('fitnez_last_registration_code', registrationCode.value.trim())
    localStorage.setItem('fitnez_last_registration_email', email.value.trim())
  } catch (e: any) {
    if (e?.status === 422) {
      const messages = e?.payload?.errors ? Object.values(e.payload.errors).flat().join('; ') : null
      error.value = messages || 'Invalid registration code or email.'
    } else {
      error.value = e?.message || 'Registration status not found.'
    }
  } finally {
    loading.value = false
  }
}

function onProofChange(event: Event) {
  const input = event.target as HTMLInputElement
  proofFile.value = input.files?.[0] || null
}

async function uploadProof() {
  if (!registration.value || !proofFile.value) {
    error.value = 'Please choose payment proof image.'
    return
  }

  error.value = ''
  successMessage.value = ''
  loading.value = true

  try {
    const response = await manualRegistrationApi.uploadProof({
      registration_code: registration.value.registration_code,
      email: registration.value.email,
      payment_proof: proofFile.value,
    })

    registration.value = response.data
    successMessage.value = 'Payment proof uploaded. Please wait for admin approval.'
  } catch (e: any) {
    error.value = e?.message || 'Failed to upload proof.'
  } finally {
    loading.value = false
  }
}

function copyCode() {
  if (registration.value) {
    navigator.clipboard.writeText(registration.value.registration_code)
    const originalSuccess = successMessage.value
    successMessage.value = 'Registration code copied to clipboard!'
    setTimeout(() => {
      if (successMessage.value === 'Registration code copied to clipboard!') {
        successMessage.value = originalSuccess
      }
    }, 2000)
  }
}

function formatCurrency(value: number) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value)
}

function formatDate(value: string | null) {
  if (!value) return ''
  return new Date(value).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}

onMounted(() => {
  if (registrationCode.value.trim() && email.value.trim()) {
    checkStatus()
  }
})
</script>

<template>
  <div class="reg-page">
    <LandingPublicNav variant="static" />

    <!-- Main Content -->
    <main class="reg-main">
      
      <!-- Lookup Form Screen (No registration loaded yet) -->
      <div v-if="!registration">
        <div class="reg-header-center">
          <p class="reg-eyebrow">REGISTRATION STATUS</p>
          <h1 class="reg-title">Track your application.</h1>
          <p class="reg-subtitle">Enter your registration code and email address to check your status.</p>
        </div>

        <div class="lookup-card-wrapper">
          <div class="reg-card">
            <h2 class="card-heading">Check Registration Status</h2>
            <form @submit.prevent="checkStatus" class="fields-stack">
              <!-- Registration Code -->
              <div class="field-group">
                <label class="field-label">Registration Code <span class="required-mark">*</span></label>
                <input
                  v-model="registrationCode"
                  class="field-input"
                  type="text"
                  placeholder="e.g. FITNEZ-MANUAL-..."
                  required
                />
              </div>

              <!-- Email -->
              <div class="field-group">
                <label class="field-label">Email Address <span class="required-mark">*</span></label>
                <input
                  v-model="email"
                  class="field-input"
                  type="email"
                  placeholder="Enter the registered email"
                  required
                />
              </div>

              <!-- Error Alert -->
              <div v-if="error" class="alert-error">
                {{ error }}
              </div>

              <!-- Actions -->
              <div class="lookup-actions">
                <button
                  type="submit"
                  class="btn-primary-full"
                  :disabled="loading"
                >
                  {{ loading ? 'Checking Status...' : 'Check Status' }}
                </button>
                <RouterLink to="/register" class="btn-outline-pill-full">
                  Back to Register
                </RouterLink>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Status Details Screen (Registration loaded) -->
      <div v-else>
        <!-- Header -->
        <div class="reg-header">
          <div>
            <p class="reg-eyebrow">REGISTRATION FOUND</p>
            <h1 class="reg-title">Hello, {{ registration.full_name }}!</h1>
            <p class="reg-subtitle">Here is the status of your registration request.</p>
          </div>
          <button @click="registration = null" class="btn-outline-pill">
            Check Another Code
          </button>
        </div>

        <div class="reg-form-grid">
          <!-- Column 1: Details & Status -->
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

              <!-- Price/Amount info -->
              <div class="price-callout price-callout-lg">
                <div class="price-callout-label">Amount Paid / To Pay</div>
                <div class="price-callout-value">{{ formatCurrency(Number(registration.amount)) }}</div>
                <div class="price-callout-info">
                  Package: {{ registration.package?.name || 'Loading Package...' }}
                </div>
              </div>

              <div class="status-summary-info">
                <div class="status-info-row">
                  <span class="status-info-label">Email Address</span>
                  <span class="status-info-val">{{ registration.email }}</span>
                </div>
                <div v-if="registration.phone" class="status-info-row">
                  <span class="status-info-label">Phone Number</span>
                  <span class="status-info-val">{{ registration.phone }}</span>
                </div>
                <div v-if="registration.birth_date" class="status-info-row">
                  <span class="status-info-label">Date of Birth</span>
                  <span class="status-info-val">{{ formatDate(registration.birth_date) }}</span>
                </div>
                <div class="status-info-row">
                  <span class="status-info-label">Application Status</span>
                  <span class="status-info-val status-text" :class="registration.status">
                    {{ registration.status.replaceAll('_', ' ') }}
                  </span>
                </div>
              </div>

              <!-- Rejection Notice -->
              <div v-if="registration.status === 'rejected'" class="alert-error">
                <p class="notice-bold">Rejection Reason:</p>
                <p>{{ registration.rejection_reason || 'No reason specified by admin.' }}</p>
              </div>

              <!-- Approved Action -->
              <div v-if="registration.status === 'approved'" class="approved-box">
                <p class="approved-text">✓ Your account has been activated! You can now log in using your email and password.</p>
                <RouterLink to="/login/member" class="btn-primary-full text-center-link">
                  Login Now
                </RouterLink>
              </div>
            </div>
          </div>

          <!-- Column 2: Payment info / proof upload -->
          <div class="reg-col-right">
            <!-- If Awaiting Payment -->
            <div v-if="registration.status === 'awaiting_payment'" class="reg-card">
              <h2 class="card-heading">Payment Instructions</h2>
              <p class="card-subtitle">Please transfer payment to the details below, then upload your transaction receipt.</p>

              <div class="payment-details-box">
                <div>
                  <p class="detail-label">Payment Method</p>
                  <h3 class="detail-value">{{ registration.paymentMethod?.display_name || registration.payment_method?.display_name || 'Manual Payment' }}</h3>
                  <p class="detail-desc">{{ registration.paymentMethod?.instructions || registration.payment_method?.instructions }}</p>
                </div>

                <!-- Bank details -->
                <div v-if="(registration.paymentMethod?.type || registration.payment_method?.type) === 'bank_transfer'" class="bank-details">
                  <div class="bank-row">
                    <span>Bank Name</span>
                    <span class="bank-val">{{ registration.paymentMethod?.bank_name || registration.payment_method?.bank_name }}</span>
                  </div>
                  <div class="bank-row">
                    <span>Account Number</span>
                    <span class="bank-val selectable">{{ registration.paymentMethod?.account_number || registration.payment_method?.account_number }}</span>
                  </div>
                  <div class="bank-row bank-row-last">
                    <span>Account Name</span>
                    <span class="bank-val">{{ registration.paymentMethod?.account_name || registration.payment_method?.account_name }}</span>
                  </div>
                </div>

                <!-- QRIS Image -->
                <div v-if="(registration.paymentMethod?.type || registration.payment_method?.type) === 'qris'" class="qris-box">
                  <img
                    :src="registration.paymentMethod?.qris_image_url || registration.payment_method?.qris_image_url || ''"
                    alt="QRIS Code"
                    class="qris-img"
                  />
                  <span class="qris-caption">Scan QRIS Code to Pay</span>
                </div>
              </div>

              <!-- Upload proof section -->
              <div class="upload-section">
                <h3 class="upload-title">Upload Payment Proof</h3>
                <p class="upload-desc">Please upload transaction receipt image in JPG, PNG, or WebP format.</p>

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
                    class="btn-primary-full"
                  >
                    {{ loading ? 'Uploading...' : 'Upload Proof' }}
                  </button>
                </div>
              </div>

              <div v-if="error" class="alert-error">{{ error }}</div>
              <div v-if="successMessage" class="alert-success">{{ successMessage }}</div>
            </div>

            <!-- If Awaiting Admin Review -->
            <div v-else-if="registration.status === 'awaiting_admin_review'" class="reg-card">
              <h2 class="card-heading">Verification Pending</h2>
              <p class="card-subtitle">Your payment proof is under verification by our team.</p>

              <div class="verification-pending-box">
                <span class="material-symbols-outlined pending-icon">hourglass_empty</span>
                <h3>Under Review</h3>
                <p>We are verifying your transaction. Usually this takes less than 24 hours. A confirmation email will be sent to <strong>{{ registration.email }}</strong> once approved.</p>
              </div>

              <div v-if="registration.payment_proof_url" class="proof-preview-box">
                <p class="detail-label">Submitted Payment Proof</p>
                <div class="proof-image-wrapper">
                  <img :src="registration.payment_proof_url" alt="Payment Proof" class="proof-img" />
                </div>
              </div>
            </div>

            <!-- General fallback if approved or rejected -->
            <div v-else class="reg-card">
              <h2 class="card-heading">What's Next?</h2>
              <div v-if="registration.status === 'approved'" class="next-steps-list">
                <div class="step-item">
                  <span class="step-num">1</span>
                  <div>
                    <h4>Log in to your account</h4>
                    <p>Click "Login Now" and use your email and the password you set during registration.</p>
                  </div>
                </div>
                <div class="step-item">
                  <span class="step-num">2</span>
                  <div>
                    <h4>Complete your Profile</h4>
                    <p>Set your fitness goals, select a trainer, or schedule a class access.</p>
                  </div>
                </div>
              </div>
              <div v-else class="next-steps-list">
                <div class="step-item font-bold">
                  <span class="step-num text-red">!</span>
                  <div>
                    <h4>Re-register or contact support</h4>
                    <p>If you believe there was a mistake, please register again with a valid transaction receipt, or contact support.</p>
                  </div>
                </div>
              </div>
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

.reg-header-center {
  margin-bottom: 3rem;
  text-align: center;
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

/* ===== Lookup Wrapper ===== */
.lookup-card-wrapper {
  max-width: 500px;
  margin: 0 auto;
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
  background: transparent;
  transition: all 0.2s;
  text-align: center;
  white-space: nowrap;
  cursor: pointer;
}
.btn-outline-pill:hover {
  background: #d3e4fe;
  border-color: #0058be;
  color: #0058be;
}

.btn-outline-pill-full {
  display: block;
  width: 100%;
  padding: 0.875rem;
  border: 2px solid #727785;
  border-radius: 0.75rem;
  font-size: 14px;
  font-weight: 700;
  color: #0b1c30;
  text-decoration: none;
  background: transparent;
  transition: all 0.2s;
  text-align: center;
  white-space: nowrap;
  cursor: pointer;
  margin-top: 0.5rem;
}
.btn-outline-pill-full:hover {
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
  text-align: center;
}
.btn-primary-full:hover { background: #1e293b; }
.btn-primary-full:disabled { opacity: 0.5; cursor: not-allowed; }

.text-center-link {
  display: block;
  text-decoration: none;
  margin-top: 1rem;
}

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

/* ===== Alerts ===== */
.alert-error {
  padding: 1rem;
  border-radius: 0.75rem;
  background: #fef2f2;
  color: #991b1b;
  font-size: 14px;
  font-weight: 700;
  border: 1px solid #fca5a5;
  line-height: 1.5;
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

/* ===== Code Row ===== */
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

/* ===== Status Summary Info ===== */
.status-summary-info {
  display: flex;
  flex-direction: column;
  gap: 0.875rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.875rem;
  padding: 1.25rem;
}

.status-info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 14px;
}

.status-info-label {
  color: #64748b;
  font-weight: 500;
}

.status-info-val {
  color: #0b1c30;
  font-weight: 700;
}

.status-text {
  text-transform: uppercase;
  font-size: 12px;
  letter-spacing: 0.05em;
  padding: 0.25rem 0.5rem;
  border-radius: 0.375rem;
}
.status-text.awaiting_payment { background: #fef3c7; color: #d97706; }
.status-text.awaiting_admin_review { background: #e0f2fe; color: #0284c7; }
.status-text.approved { background: #dcfce7; color: #16a34a; }
.status-text.rejected { background: #fee2e2; color: #dc2626; }

/* ===== Approved Box ===== */
.approved-box {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 0.875rem;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.approved-text {
  color: #166534;
  font-size: 14px;
  font-weight: 700;
  margin: 0;
  line-height: 1.5;
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

/* ===== Verification Pending Box ===== */
.verification-pending-box {
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 0.875rem;
  padding: 1.5rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.pending-icon {
  font-size: 48px;
  color: #0284c7;
}

.verification-pending-box h3 {
  font-size: 20px;
  font-weight: 800;
  color: #0b1c30;
  margin: 0;
}

.verification-pending-box p {
  font-size: 14px;
  color: #424754;
  margin: 0;
  line-height: 1.5;
}

.proof-preview-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.875rem;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.proof-image-wrapper {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  padding: 0.5rem;
  max-height: 300px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.proof-img {
  max-width: 100%;
  max-height: 280px;
  object-fit: contain;
}

/* ===== Next Steps List ===== */
.next-steps-list {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.step-item {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.step-num {
  background: #e0e7ff;
  color: #3730a3;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 14px;
  flex-shrink: 0;
}

.step-item h4 {
  font-size: 16px;
  font-weight: 800;
  color: #0b1c30;
  margin: 0 0 0.15rem;
}

.step-item p {
  font-size: 13px;
  color: #64748b;
  margin: 0;
  line-height: 1.4;
}

.font-bold {
  font-weight: bold;
}

.text-red {
  color: #dc2626;
  background: #fee2e2;
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
  .reg-title {
    font-size: 26px;
  }
  .price-callout-value {
    font-size: 24px;
  }
}
</style>
