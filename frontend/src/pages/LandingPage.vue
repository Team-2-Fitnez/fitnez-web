<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { landingVisitService } from '../services/landingVisitService'

let stopTracking: null | (() => void) = null

const packages = [
  {
    name: '1 Month Basic',
    duration: '1 month',
    price: 'Rp250.000',
    bestFor: 'Trial and short-term training',
    benefits: ['Gym access', 'Manual payment verification', 'Member account after approval'],
    highlight: false,
  },
  {
    name: '3 Months Basic',
    duration: '3 months',
    price: 'Rp675.000',
    bestFor: 'Building a consistent habit',
    benefits: ['Gym access', 'Better monthly value', 'Member account after approval'],
    highlight: false,
  },
  {
    name: '6 Months Plus',
    duration: '6 months',
    price: 'Rp1.200.000',
    bestFor: 'Members who want classes',
    benefits: ['Gym access', 'Free yoga class', 'Free aerobics class', 'Group class access'],
    highlight: true,
  },
  {
    name: '12 Months Premium',
    duration: '12 months',
    price: 'Rp2.200.000',
    bestFor: 'Best long-term value',
    benefits: ['Full-year gym access', 'Free class access', 'Best value package', 'Priority renewal'],
    highlight: true,
  },
]

const steps = [
  { title: 'Fill personal data', text: 'Create your application with email, phone, and secure password.' },
  { title: 'Choose package', text: 'Select 1, 3, 6, or 12 months. Higher packages include class access.' },
  { title: 'Pay manually', text: 'Use QRIS or bank transfer. Enter the exact amount shown by the system.' },
  { title: 'Upload proof', text: 'Upload transaction screenshot so admin can verify your payment.' },
  { title: 'Wait for approval', text: 'Admin reviews the proof and activates your account if valid.' },
  { title: 'Login directly', text: 'After approval, login using your email and password. Use OTP only when resetting a forgotten password.' },
]

const classes = ['Yoga', 'Aerobics', 'Strength Training', 'Mobility', 'HIIT', 'Functional Fitness']

onMounted(() => {
  stopTracking = landingVisitService.startLandingTrackingHeartbeat()
})

onBeforeUnmount(() => {
  stopTracking?.()
})
</script>

<template>
  <main class="page">
    <section class="hero">
      <div class="page-container">
        <div class="hero-layout">
          <div>
            <div class="eyebrow">Fitnez Gym Membership</div>

            <h1 class="title-xl">
              Train stronger.
              <span class="hero-title-accent">Join with clarity.</span>
            </h1>

            <p class="text-muted" style="margin-top: 20px; max-width: 42rem; line-height: 1.8;">
              Register, choose your membership package, upload payment proof, and access Fitnez features after admin approval.
            </p>

            <div class="hero-actions">
              <RouterLink to="/register" class="button button-landing-orange">Register Today</RouterLink>
              <a href="#packages" class="button button-landing-orange">View Packages</a>
              <RouterLink to="/registration-status" class="button button-landing-orange">Check Status</RouterLink>
              <RouterLink to="/login/member" class="button button-landing-orange">Login</RouterLink>
            </div>
          </div>

          <aside class="hero-visual">
            <div class="hero-visual-card">
              <p class="eyebrow">Recommended</p>
              <h2 class="title-lg">12 Months Premium</h2>
              <p class="text-muted">
                The best long-term plan for members who want full-year access, class benefits, and better membership value.
              </p>

              <div class="panel">
                <p class="stat-label">Package Price</p>
                <p class="stat-value">Rp2.200.000</p>
              </div>

              <RouterLink to="/register" class="button button-landing-orange" style="width: 100%; margin-top: 1rem;">
                Start Registration
              </RouterLink>
            </div>

            <div class="feature-grid" style="margin-top: 1rem;">
              <div class="panel landing-orange-card">
                <p class="title-md" style="color: white;">Full-year access</p>
                <p style="color: rgba(255,255,255,0.86); font-weight: 600;">Train consistently for 12 months.</p>
              </div>
              <div class="panel landing-orange-card">
                <p class="title-md" style="color: white;">Best value</p>
                <p style="color: rgba(255,255,255,0.86); font-weight: 600;">Lower effective monthly cost.</p>
              </div>
              <div class="panel landing-orange-card">
                <p class="title-md" style="color: white;">Free classes</p>
                <p style="color: rgba(255,255,255,0.86); font-weight: 600;">Access selected group classes.</p>
              </div>
              <div class="panel landing-orange-card">
                <p class="title-md" style="color: white;">Priority renewal</p>
                <p style="color: rgba(255,255,255,0.86); font-weight: 600;">Easier long-term membership control.</p>
              </div>
            </div>
          </aside>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="page-container feature-layout">
        <div>
          <p class="eyebrow">Why Fitnez</p>
          <h2 class="title-lg">Your digital fitness companion.</h2>
          <p class="text-muted">
            Fitnez helps members manage their training journey with exercise tracking, video tutorials, meal plans, and nutrition calculation features for protein, carbs, and fat.
          </p>
          <RouterLink to="/register" class="button button-primary" style="margin-top: 1.5rem;">
            Register Today
          </RouterLink>
        </div>

        <div class="feature-grid">
          <div class="panel panel-blue landing-blue-card">
            <p class="eyebrow eyebrow-light">Training</p>
            <h3 class="title-md">Exercise Tracking</h3>
            <p>Record workout progress, completed sessions, and training consistency.</p>
          </div>

          <div class="panel panel-blue landing-blue-card">
            <p class="eyebrow eyebrow-light">Nutrition</p>
            <h3 class="title-md">Meal Plan</h3>
            <p>Organize daily meals to support bulking, cutting, or maintenance goals.</p>
          </div>

          <div class="panel panel-blue landing-blue-card">
            <p class="eyebrow eyebrow-light">Calculator</p>
            <h3 class="title-md">Nutrition Calculator</h3>
            <p>Estimate protein, carbs, and fat needs for daily planning.</p>
          </div>
        </div>
      </div>
    </section>

    <section id="packages" class="section section-white">
      <div class="page-container">
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem; align-items: end; margin-bottom: 2rem;">
          <div>
            <p class="eyebrow">Packages</p>
            <h2 class="title-lg">Choose your training plan.</h2>
          </div>
          <p class="text-muted" style="max-width: 36rem;">
            6-month and 12-month packages include free access to classes such as yoga and aerobics.
          </p>
        </div>

        <div class="package-grid">
          <article v-for="item in packages" :key="item.name" class="panel landing-package-card">
            <div style="display: flex; justify-content: space-between; align-items: start; gap: 0.75rem;">
              <span class="status status-info">{{ item.duration }}</span>
              <span v-if="item.highlight" class="status status-warning">Free Class</span>
            </div>

            <h3 class="title-md">{{ item.name }}</h3>
            <p>{{ item.bestFor }}</p>
            <p class="stat-value">{{ item.price }}</p>

            <ul style="padding-left: 0; list-style: none; display: grid; gap: 0.5rem;">
              <li v-for="benefit in item.benefits" :key="benefit">
                ✓ {{ benefit }}
              </li>
            </ul>

            <RouterLink to="/register" class="button button-white-orange" style="width: 100%; margin-top: 1rem;">
              Select Package
            </RouterLink>
          </article>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="page-container flow-layout">
        <div class="step-grid">
          <div v-for="(step, index) in steps" :key="step.title" class="panel landing-step-card">
            <p class="eyebrow eyebrow-light">STEP {{ index + 1 }}</p>
            <h3 class="title-md">{{ step.title }}</h3>>
            <p>{{ step.text }}</p>
          </div>
        </div>

        <div>
          <p class="eyebrow">Flow</p>
          <h2 class="title-lg">Clear from payment to approval.</h2>
          <p class="text-muted" style="margin-top: 20px;">
            After proof upload, the registration status page tells the user exactly what is happening: awaiting payment, awaiting admin review, approved, or rejected.
          </p>
          <RouterLink to="/registration-status" class="button button-black" style="margin-top: 1.5rem;">
            Check Registration Status
          </RouterLink>
        </div>
      </div>
    </section>

    <section class="section section-blue">
      <div class="page-container">
        <div style="text-align: center;">
          <p class="eyebrow eyebrow-light">Classes</p>
          <h2 class="title-lg" style="color: white; transform: translateY(-20px);">More than gym access.</h2>
          <p style="color: rgba(255,255,255,0.90); font-weight: 600; max-width: 42rem; margin-inline: auto;">
            Plus and Premium packages include selected group class access.
          </p>
        </div>

        <div class="class-grid">
          <div v-for="item in classes" :key="item" class="panel" style="background: rgba(255,255,255,0.15); color: white; text-align: center; font-weight: 900;">
            {{ item }}
          </div>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="page-container">
        <div class="card" style="background: black; color: white; text-align: center;">
          <h2 class="title-lg" style="color: white;">Ready to become a Fitnez member?</h2>
          <p style="color: rgba(255,255,255,0.88); font-weight: 600; max-width: 42rem; margin-inline: auto;">
            Start registration, complete payment manually, upload proof, and wait for admin approval.
          </p>
          <div class="hero-actions" style="justify-content: center;">
            <RouterLink to="/register" class="button button-landing-orange">Register Today</RouterLink>
            <RouterLink to="/login/member" class="button button-landing-orange">Login</RouterLink>
          </div>
        </div>
      </div>
    </section>

    <footer style="border-top: 1px solid var(--color-border); background: white;">
      <div class="page-container" style="display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap; padding: 1.5rem 1rem;">
        <RouterLink to="/" style="color: var(--color-muted); text-decoration: none; font-weight: 600; font-size: 0.875rem;">Home</RouterLink>
        <RouterLink to="/faq" style="color: var(--color-muted); text-decoration: none; font-weight: 600; font-size: 0.875rem;">FAQ</RouterLink>
        <RouterLink to="/privacy-cookie-policy" style="color: var(--color-muted); text-decoration: none; font-weight: 600; font-size: 0.875rem;">Privacy & Cookie Policy</RouterLink>
        <span style="color: var(--color-muted); font-size: 0.875rem;">&copy; {{ new Date().getFullYear() }} Fitnez</span>
      </div>
    </footer>
  </main>
</template>
