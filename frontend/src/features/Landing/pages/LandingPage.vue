<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { landingVisitService } from '@/features/Landing/services/landingVisitService'
import LandingPublicNav from '@/features/Landing/components/LandingPublicNav.vue'
import LandingPublicFooter from '@/features/Landing/components/LandingPublicFooter.vue'
import { getStoredConsent } from '@/shared/utils/cookieConsent'

let stopTracking: null | (() => void) = null
let landingObserver: IntersectionObserver | null = null
let handleLandingScroll: (() => void) | null = null
const showCookieSettingsButton = ref(false)

function syncCookieSettingsButton() {
  showCookieSettingsButton.value = !getStoredConsent()
}

function hideCookieSettingsButton() {
  showCookieSettingsButton.value = false
}

function openCookieSettings() {
  window.dispatchEvent(new CustomEvent('open-cookie-settings'))
}

function smoothScroll(e: Event, targetId: string) {
  e.preventDefault()
  const el = document.getElementById(targetId)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    history.pushState(null, '', `#${targetId}`)
  }
}

onMounted(() => {
  stopTracking = landingVisitService.startLandingTrackingHeartbeat()
  syncCookieSettingsButton()
  window.addEventListener('fitnez-cookie-consent-changed', hideCookieSettingsButton)

  // Navbar scroll effect
  handleLandingScroll = () => {
    const nav = document.getElementById('navbar')
    if (nav) {
      if (window.scrollY > 50) {
        nav.classList.add('nav-scrolled', 'py-2')
        nav.classList.remove('py-4')
      } else {
        nav.classList.remove('nav-scrolled', 'py-2')
        nav.classList.add('py-4')
      }
    }
  }
  window.addEventListener('scroll', handleLandingScroll)

  // Simple Intersection Observer for scroll reveal animations
  const observerOptions = {
    threshold: 0.1,
    rootMargin: "0px 0px -50px 0px"
  }

  landingObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('opacity-100', 'translate-y-0')
        entry.target.classList.remove('opacity-0', 'translate-y-10')
        landingObserver?.unobserve(entry.target) // Only animate once
      }
    })
  }, observerOptions)

  document.querySelectorAll('section > div:first-child').forEach(el => {
    el.classList.add('transition-all', 'duration-700', 'opacity-0', 'translate-y-10')
    landingObserver?.observe(el)
  })
})

onBeforeUnmount(() => {
  if (handleLandingScroll) {
    window.removeEventListener('scroll', handleLandingScroll)
  }
  window.removeEventListener('fitnez-cookie-consent-changed', hideCookieSettingsButton)
  landingObserver?.disconnect()
  stopTracking?.()
})
</script>

<template>
  <div class="landing-page-root bg-background text-on-background font-body-md selection:bg-primary-fixed selection:text-on-primary-fixed">
    <LandingPublicNav variant="landing" @smooth-scroll="smoothScroll" />

    <!-- Hero Section -->
    <header class="relative pt-[160px] pb-section-padding-mobile md:pb-section-padding-desktop overflow-hidden bg-gradient-to-br from-surface via-surface-container-low to-surface-variant">
      <!-- Abstract Shapes -->
      <div class="absolute top-0 right-0 w-full h-full overflow-hidden pointer-events-none">
        <div class="absolute -top-[20%] -right-[10%] w-[70%] h-[70%] rounded-full bg-gradient-to-bl from-primary/10 to-transparent blur-3xl"></div>
        <div class="absolute top-[40%] -left-[10%] w-[50%] h-[50%] rounded-full bg-gradient-to-tr from-tertiary/10 to-transparent blur-3xl"></div>
      </div>
      <div class="max-w-container-max mx-auto px-gutter grid grid-cols-1 lg:grid-cols-2 gap-16 items-center relative z-10">
        <div>
          <p class="font-label-bold text-label-bold text-primary uppercase tracking-widest mb-4">Fitnez Gym Membership</p>
          <h1 class="font-display-lg text-display-lg-mobile md:text-display-lg mb-6 leading-tight">Train stronger.<br/><span class="text-primary">Join with clarity.</span></h1>
          <p class="font-body-lg text-body-lg text-on-surface-variant mb-10 max-w-xl">Register, choose your membership package, upload payment proof, and access Fitnez features after admin approval. No hidden fees, just pure progress.</p>
          <div class="flex flex-wrap gap-4">
            <RouterLink to="/register" class="bg-[#f97316] text-white px-8 py-4 rounded-full font-label-bold text-label-bold hover:shadow-xl hover:-translate-y-1 transition-all text-center block sm:inline-block">Register Today</RouterLink>
            <a href="#packages" @click="smoothScroll($event, 'packages')" class="border-2 border-on-background px-8 py-4 rounded-full font-label-bold text-label-bold hover:bg-on-background hover:text-white transition-all text-center block sm:inline-block">View Packages</a>
          </div>
          <div class="mt-12 flex gap-8 items-center border-t border-outline-variant/30 pt-8">
            <div>
              <p class="font-headline-lg text-headline-lg">15k+</p>
              <p class="text-on-surface-variant font-label-bold text-label-bold">Members</p>
            </div>
            <div>
              <p class="font-headline-lg text-headline-lg">50+</p>
              <p class="text-on-surface-variant font-label-bold text-label-bold">Trainers</p>
            </div>
            <div>
              <p class="font-headline-lg text-headline-lg">24/7</p>
              <p class="text-on-surface-variant font-label-bold text-label-bold">Access</p>
            </div>
          </div>
        </div>
        <div class="relative group perspective-1000">
          <!-- Floating Card UI inspired by the image -->
          <div class="relative bg-on-background rounded-[40px] p-8 md:p-12 shadow-2xl text-white animate-float overflow-hidden border border-white/10 backdrop-blur-sm">
            <div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent pointer-events-none"></div>
            <div class="absolute top-0 right-0 p-4">
              <span class="bg-tertiary text-white px-4 py-1.5 rounded-full text-[12px] font-bold uppercase animate-pulse shadow-[0_0_15px_rgba(153,65,0,0.5)]">Recommended</span>
            </div>
            <h3 class="text-[36px] md:text-[42px] font-extrabold leading-tight mb-2">12 Months Premium</h3>
            <p class="text-surface-variant/80 mb-8 text-sm max-w-xs font-body-md">The best long-term plan for members who want full-year access, class benefits, and better membership value.</p>
            <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/20 mb-8 hover:bg-white/15 transition-colors">
              <p class="text-white/60 text-xs font-bold uppercase mb-1 tracking-wider">Package Price</p>
              <p class="text-3xl font-black">Rp2.200.000</p>
            </div>
            <RouterLink to="/register" class="w-full bg-[#f97316] text-white py-4 rounded-xl font-bold mb-8 hover:scale-[1.02] hover:shadow-lg transition-all text-center block">Start Registration</RouterLink>
            <div class="grid grid-cols-2 gap-4">
              <div class="bg-white/5 p-4 rounded-xl hover:bg-white/10 transition-colors">
                <p class="font-bold text-sm mb-1">Full-year access</p>
                <p class="text-[10px] text-white/50">Train consistently for 12 months.</p>
              </div>
              <div class="bg-white/5 p-4 rounded-xl hover:bg-white/10 transition-colors">
                <p class="font-bold text-sm mb-1">Best value</p>
                <p class="text-[10px] text-white/50">Lower effective monthly cost.</p>
              </div>
              <div class="bg-white/5 p-4 rounded-xl hover:bg-white/10 transition-colors">
                <p class="font-bold text-sm mb-1">Free classes</p>
                <p class="text-[10px] text-white/50">Access selected group classes.</p>
              </div>
              <div class="bg-white/5 p-4 rounded-xl hover:bg-white/10 transition-colors">
                <p class="font-bold text-sm mb-1">Priority renewal</p>
                <p class="text-[10px] text-white/50">Easier long-term control.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Features Bento Section -->
    <section class="py-section-padding-desktop bg-surface-container-low" id="features">
      <div class="max-w-container-max mx-auto px-gutter">
        <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-stack-lg">
          <div class="max-w-2xl">
            <p class="font-label-bold text-label-bold text-primary mb-4 uppercase tracking-widest">Why Fitnez</p>
            <h2 class="font-headline-xl text-headline-xl mb-6">Your digital fitness companion.</h2>
            <p class="font-body-lg text-body-lg text-on-surface-variant">Fitnez helps members manage their training journey with exercise tracking, video tutorials, meal plans, and nutrition calculation features for protein, carbs, and fat.</p>
          </div>
          <RouterLink to="/register" class="bg-primary text-white px-8 py-4 rounded-full font-label-bold text-label-bold hover:shadow-xl hover:-translate-y-1 transition-all text-center block sm:inline-block">Register Today</RouterLink>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <!-- Feature 1 -->
          <div class="bg-primary text-white p-10 rounded-[32px] hover:-translate-y-4 hover:shadow-2xl transition-all duration-300 group">
            <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 group-hover:bg-white/20 transition-all">
              <span class="material-symbols-outlined text-4xl" data-icon="fitness_center">fitness_center</span>
            </div>
            <p class="text-xs font-bold text-primary-fixed uppercase tracking-widest mb-2">Training</p>
            <h3 class="font-headline-lg text-headline-lg mb-4">Exercise Tracking</h3>
            <p class="text-white/80 font-body-md text-body-md">Record workout progress, completed sessions, and training consistency with our intuitive log.</p>
          </div>
          <!-- Feature 2 -->
          <div class="bg-on-background text-white p-10 rounded-[32px] hover:-translate-y-4 hover:shadow-2xl transition-all duration-300 group">
            <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 group-hover:bg-white/20 transition-all">
              <span class="material-symbols-outlined text-4xl" data-icon="restaurant_menu">restaurant_menu</span>
            </div>
            <p class="text-xs font-bold text-surface-variant/60 uppercase tracking-widest mb-2">Nutrition</p>
            <h3 class="font-headline-lg text-headline-lg mb-4">Meal Plan</h3>
            <p class="text-white/80 font-body-md text-body-md">Organize daily meals to support bulking, cutting, or maintenance goals with nutritionist-backed recipes.</p>
          </div>
          <!-- Feature 3 -->
          <div class="bg-[#1e293b] text-white p-10 rounded-[32px] hover:-translate-y-4 hover:shadow-2xl transition-all duration-300 group">
            <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center mb-8 group-hover:scale-110 group-hover:bg-white/20 transition-all">
              <span class="material-symbols-outlined text-4xl" data-icon="calculate">calculate</span>
            </div>
            <p class="text-xs font-bold text-surface-variant/60 uppercase tracking-widest mb-2">Calculator</p>
            <h3 class="font-headline-lg text-headline-lg mb-4">Nutrition Calculator</h3>
            <p class="text-white/80 font-body-md text-body-md">Estimate protein, carbs, and fat needs for daily planning tailored to your body metrics.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Packages Section -->
    <section class="py-section-padding-desktop bg-white" id="packages">
      <div class="max-w-container-max mx-auto px-gutter">
        <div class="text-center mb-16">
          <p class="font-label-bold text-label-bold text-primary mb-4 uppercase tracking-widest">Packages</p>
          <h2 class="font-headline-xl text-headline-xl mb-4">Choose your training plan.</h2>
          <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl mx-auto">6-month and 12-month packages include free access to classes such as yoga and aerobics.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 items-end">
          <!-- 1 Month -->
          <div class="bg-[#f97316] text-white rounded-[32px] p-8 flex flex-col hover:-translate-y-4 hover:shadow-2xl transition-all duration-300 group h-full">
            <span class="bg-white/20 w-fit px-3 py-1 rounded-full text-[10px] font-bold mb-4">1 Month</span>
            <h3 class="font-headline-lg text-[24px] mb-2">1 Month Basic</h3>
            <p class="text-white/80 text-sm mb-6">Trial and short-term training</p>
            <p class="text-3xl font-black mb-6 group-hover:scale-105 transition-transform origin-left">Rp250.000</p>
            <ul class="space-y-3 mb-10 text-sm flex-1">
              <li class="flex items-center gap-2"><span class="material-symbols-outlined text-lg" data-icon="check">check</span> Gym access</li>
              <li class="flex items-center gap-2"><span class="material-symbols-outlined text-lg" data-icon="check">check</span> Manual payment</li>
            </ul>
            <RouterLink :to="{ name: 'register', query: { package: 'PKG_1_MONTH' } }" class="w-full bg-white text-[#f97316] py-3 rounded-xl font-bold hover:bg-opacity-90 hover:shadow-md transition-all text-center block">Select Package</RouterLink>
          </div>
          <!-- 3 Month -->
          <div class="bg-[#f97316] text-white rounded-[32px] p-8 flex flex-col hover:-translate-y-4 hover:shadow-2xl transition-all duration-300 group h-full">
            <span class="bg-white/20 w-fit px-3 py-1 rounded-full text-[10px] font-bold mb-4">3 Months</span>
            <h3 class="font-headline-lg text-[24px] mb-2">3 Months Basic</h3>
            <p class="text-white/80 text-sm mb-6">Building a consistent habit</p>
            <p class="text-3xl font-black mb-6 group-hover:scale-105 transition-transform origin-left">Rp675.000</p>
            <ul class="space-y-3 mb-10 text-sm flex-1">
              <li class="flex items-center gap-2"><span class="material-symbols-outlined text-lg" data-icon="check">check</span> Gym access</li>
              <li class="flex items-center gap-2"><span class="material-symbols-outlined text-lg" data-icon="check">check</span> Better monthly value</li>
            </ul>
            <RouterLink :to="{ name: 'register', query: { package: 'PKG_3_MONTHS' } }" class="w-full bg-white text-[#f97316] py-3 rounded-xl font-bold hover:bg-opacity-90 hover:shadow-md transition-all text-center block">Select Package</RouterLink>
          </div>
          <!-- 6 Month -->
          <div class="bg-[#f97316] text-white rounded-[32px] p-8 flex flex-col hover:-translate-y-4 hover:shadow-2xl transition-all duration-300 group relative h-full">
            <div class="absolute -top-3 right-8 bg-on-background text-white px-3 py-1 rounded-full text-[10px] font-bold flex items-center gap-1 shadow-lg">
              <span class="material-symbols-outlined text-sm" data-icon="stars" style="font-variation-settings: 'FILL' 1;">stars</span> Free Class
            </div>
            <span class="bg-white/20 w-fit px-3 py-1 rounded-full text-[10px] font-bold mb-4">6 Months</span>
            <h3 class="font-headline-lg text-[24px] mb-2">6 Months Plus</h3>
            <p class="text-white/80 text-sm mb-6">Members who want classes</p>
            <p class="text-3xl font-black mb-6 group-hover:scale-105 transition-transform origin-left">Rp1.200.000</p>
            <ul class="space-y-3 mb-10 text-sm flex-1">
              <li class="flex items-center gap-2"><span class="material-symbols-outlined text-lg" data-icon="check">check</span> Gym access</li>
              <li class="flex items-center gap-2"><span class="material-symbols-outlined text-lg" data-icon="check">check</span> Free class access</li>
            </ul>
            <RouterLink :to="{ name: 'register', query: { package: 'PKG_6_MONTHS_PLUS' } }" class="w-full bg-white text-[#f97316] py-3 rounded-xl font-bold hover:bg-opacity-90 hover:shadow-md transition-all text-center block">Select Package</RouterLink>
          </div>
          <!-- 12 Month (Best Value) -->
          <div class="bg-[#f97316] text-white rounded-[32px] p-8 flex flex-col lg:scale-105 hover:-translate-y-4 hover:shadow-[0_25px_50px_-12px_rgba(249,115,22,0.5)] transition-all duration-300 group z-10 shadow-xl relative border-4 border-white h-full lg:mb-4 lg:-mt-4">
            <div class="absolute -top-4 right-6 bg-on-background text-white px-4 py-1.5 rounded-full text-[12px] font-bold flex items-center gap-1 shadow-lg animate-pulse">
              <span class="material-symbols-outlined text-sm" data-icon="stars" style="font-variation-settings: 'FILL' 1;">stars</span> Best Value
            </div>
            <span class="bg-on-background/20 w-fit px-3 py-1 rounded-full text-[10px] font-bold mb-4">12 Months</span>
            <h3 class="font-headline-lg text-[24px] mb-2">12 Months Premium</h3>
            <p class="text-white/80 text-sm mb-6">Best long-term value</p>
            <p class="text-4xl font-black mb-6 group-hover:scale-105 transition-transform origin-left">Rp2.200.000</p>
            <ul class="space-y-3 mb-10 text-sm flex-1">
              <li class="flex items-center gap-2"><span class="material-symbols-outlined text-lg" data-icon="check" style="font-variation-settings: 'wght' 700;">check</span> Full-year access</li>
              <li class="flex items-center gap-2"><span class="material-symbols-outlined text-lg" data-icon="check" style="font-variation-settings: 'wght' 700;">check</span> Free class access</li>
              <li class="flex items-center gap-2"><span class="material-symbols-outlined text-lg" data-icon="check" style="font-variation-settings: 'wght' 700;">check</span> Priority renewal</li>
              <li class="flex items-center gap-2"><span class="material-symbols-outlined text-lg" data-icon="check" style="font-variation-settings: 'wght' 700;">check</span> Premium support</li>
            </ul>
            <RouterLink :to="{ name: 'register', query: { package: 'PKG_12_MONTHS_PREMIUM' } }" class="w-full bg-on-background text-white py-4 rounded-xl font-bold hover:bg-opacity-90 hover:shadow-lg transition-all text-center block">Select Package</RouterLink>
          </div>
        </div>
      </div>
    </section>

    <!-- Flow Section -->
    <section class="py-section-padding-desktop bg-surface-container" id="how-it-works">
      <div class="max-w-container-max mx-auto px-gutter">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-20 items-center">
          <div class="order-2 lg:order-1 relative">
            <!-- Connecting line for steps (desktop) -->
            <div class="hidden md:block absolute left-1/2 top-[10%] bottom-[10%] w-0.5 bg-outline-variant/30 -translate-x-1/2 z-0"></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 relative z-10">
              <!-- Step 1 -->
              <div class="bg-primary text-white p-8 rounded-3xl relative overflow-hidden group hover:-translate-y-2 hover:shadow-xl transition-all duration-300">
                <div class="absolute top-0 right-0 p-4 opacity-20 text-6xl font-black">1</div>
                <p class="font-label-bold text-label-bold text-primary-fixed mb-4">STEP 1</p>
                <h4 class="font-headline-lg text-[20px] mb-4">Fill personal data</h4>
                <p class="text-sm text-white/80">Create your application with email, phone, and secure password.</p>
              </div>
              <!-- Step 2 -->
              <div class="bg-on-background text-white p-8 rounded-3xl relative overflow-hidden group hover:-translate-y-2 hover:shadow-xl transition-all duration-300 md:mt-12">
                <div class="absolute top-0 right-0 p-4 opacity-20 text-6xl font-black">2</div>
                <p class="font-label-bold text-label-bold text-surface-variant mb-4">STEP 2</p>
                <h4 class="font-headline-lg text-[20px] mb-4">Choose package</h4>
                <p class="text-sm text-white/80">Select 1, 3, 6, or 12 months. Higher packages include class access.</p>
              </div>
              <!-- Step 3 -->
              <div class="bg-on-background text-white p-8 rounded-3xl relative overflow-hidden group hover:-translate-y-2 hover:shadow-xl transition-all duration-300">
                <div class="absolute top-0 right-0 p-4 opacity-20 text-6xl font-black">3</div>
                <p class="font-label-bold text-label-bold text-surface-variant mb-4">STEP 3</p>
                <h4 class="font-headline-lg text-[20px] mb-4">Pay manually</h4>
                <p class="text-sm text-white/80">Use QRIS or bank transfer. Enter the exact amount shown by system.</p>
              </div>
              <!-- Step 4 -->
              <div class="bg-on-background text-white p-8 rounded-3xl relative overflow-hidden group hover:-translate-y-2 hover:shadow-xl transition-all duration-300 md:mt-12">
                <div class="absolute top-0 right-0 p-4 opacity-20 text-6xl font-black">4</div>
                <p class="font-label-bold text-label-bold text-surface-variant mb-4">STEP 4</p>
                <h4 class="font-headline-lg text-[20px] mb-4">Upload proof</h4>
                <p class="text-sm text-white/80">Upload transaction screenshot so admin can verify your payment.</p>
              </div>
              <!-- Step 5 -->
              <div class="bg-on-background text-white p-8 rounded-3xl relative overflow-hidden group hover:-translate-y-2 hover:shadow-xl transition-all duration-300">
                <div class="absolute top-0 right-0 p-4 opacity-20 text-6xl font-black">5</div>
                <p class="font-label-bold text-label-bold text-surface-variant mb-4">STEP 5</p>
                <h4 class="font-headline-lg text-[20px] mb-4">Wait for approval</h4>
                <p class="text-sm text-white/80">Admin reviews the proof and activates your account if valid.</p>
              </div>
              <!-- Step 6 -->
              <div class="bg-on-background text-white p-8 rounded-3xl relative overflow-hidden group hover:-translate-y-2 hover:shadow-xl transition-all duration-300 md:mt-12">
                <div class="absolute top-0 right-0 p-4 opacity-20 text-6xl font-black">6</div>
                <p class="font-label-bold text-label-bold text-surface-variant mb-4">STEP 6</p>
                <h4 class="font-headline-lg text-[20px] mb-4">Login directly</h4>
                <p class="text-sm text-white/80">After approval, login using your email and password.</p>
              </div>
            </div>
          </div>
          <div class="order-1 lg:order-2">
            <p class="font-label-bold text-label-bold text-primary mb-4 uppercase tracking-widest">Flow</p>
            <h2 class="font-headline-xl text-headline-xl mb-6">Clear from payment to approval.</h2>
            <p class="font-body-lg text-body-lg text-on-surface-variant mb-10">After proof upload, the registration status page tells the user exactly what is happening: awaiting payment, awaiting admin review, approved, or rejected.</p>
            <RouterLink to="/registration-status" class="bg-on-background text-white px-10 py-4 rounded-full font-label-bold text-label-bold hover:shadow-xl hover:-translate-y-1 transition-all text-center inline-block">Check Registration Status</RouterLink>
            <div class="mt-16 p-8 bg-primary/5 rounded-3xl border border-primary/10 hover:border-primary/30 transition-colors">
              <div class="flex items-start gap-4">
                <div class="p-3 bg-primary text-white rounded-full flex items-center justify-center">
                  <span class="material-symbols-outlined">info</span>
                </div>
                <div>
                  <h5 class="font-bold text-on-background mb-2">Need help with registration?</h5>
                  <p class="text-on-surface-variant text-sm">Our support team is available 24/7 to help you through the process. Contact us on WhatsApp.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Classes Section -->
    <section class="py-section-padding-desktop bg-on-background text-white">
      <div class="max-w-container-max mx-auto px-gutter text-center">
        <p class="font-label-bold text-label-bold text-surface-variant mb-4 uppercase tracking-[0.2em]">Classes</p>
        <h2 class="font-headline-xl text-headline-xl mb-6">More than gym access.</h2>
        <p class="text-surface-variant/80 font-body-md text-body-md mb-12">Plus and Premium packages include selected group class access.</p>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <div class="bg-white/5 hover:bg-primary transition-all duration-300 hover:-translate-y-2 py-8 px-4 rounded-2xl flex flex-col items-center gap-4 cursor-default group">
            <span class="material-symbols-outlined text-4xl group-hover:scale-110 transition-transform" data-icon="self_improvement">self_improvement</span>
            <span class="font-bold tracking-wide">Yoga</span>
          </div>
          <div class="bg-white/5 hover:bg-primary transition-all duration-300 hover:-translate-y-2 py-8 px-4 rounded-2xl flex flex-col items-center gap-4 cursor-default group">
            <span class="material-symbols-outlined text-4xl group-hover:scale-110 transition-transform" data-icon="directions_run">directions_run</span>
            <span class="font-bold tracking-wide">Aerobics</span>
          </div>
          <div class="bg-white/5 hover:bg-primary transition-all duration-300 hover:-translate-y-2 py-8 px-4 rounded-2xl flex flex-col items-center gap-4 cursor-default group">
            <span class="material-symbols-outlined text-4xl group-hover:scale-110 transition-transform" data-icon="fitness_center">fitness_center</span>
            <span class="font-bold tracking-wide">Strength</span>
          </div>
          <div class="bg-white/5 hover:bg-primary transition-all duration-300 hover:-translate-y-2 py-8 px-4 rounded-2xl flex flex-col items-center gap-4 cursor-default group">
            <span class="material-symbols-outlined text-4xl group-hover:scale-110 transition-transform" data-icon="stretching">align_stretch</span>
            <span class="font-bold tracking-wide">Mobility</span>
          </div>
          <div class="bg-white/5 hover:bg-primary transition-all duration-300 hover:-translate-y-2 py-8 px-4 rounded-2xl flex flex-col items-center gap-4 cursor-default group">
            <span class="material-symbols-outlined text-4xl group-hover:scale-110 transition-transform" data-icon="bolt">bolt</span>
            <span class="font-bold tracking-wide">HIIT</span>
          </div>
          <div class="bg-white/5 hover:bg-primary transition-all duration-300 hover:-translate-y-2 py-8 px-4 rounded-2xl flex flex-col items-center gap-4 cursor-default group">
            <span class="material-symbols-outlined text-4xl group-hover:scale-110 transition-transform" data-icon="sports_gymnastics">sports_gymnastics</span>
            <span class="font-bold tracking-wide">Functional</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Final CTA -->
    <section class="py-section-padding-desktop">
      <div class="max-w-container-max mx-auto px-gutter">
        <div class="bg-on-background rounded-[48px] p-12 md:p-24 text-center text-white relative overflow-hidden group">
          <!-- Abstract Glow Background -->
          <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[400px] bg-primary/20 rounded-full blur-[120px] pointer-events-none transition-all duration-700 group-hover:bg-primary/30 group-hover:scale-110"></div>
          <h2 class="font-headline-xl text-headline-xl mb-6 relative z-10">Ready to become a Fitnez member?</h2>
          <p class="text-surface-variant/80 mb-12 max-w-2xl mx-auto relative z-10 font-body-lg">Start registration, complete payment manually, upload proof, and wait for admin approval. Join the community of achievers today.</p>
          <div class="flex flex-wrap justify-center gap-6 relative z-10">
            <RouterLink to="/register" class="bg-[#f97316] text-white px-10 py-5 rounded-full font-label-bold text-label-bold hover:scale-105 hover:shadow-xl transition-all text-center block sm:inline-block">Register Today</RouterLink>
            <RouterLink to="/login/member" class="bg-white text-on-background px-10 py-5 rounded-full font-label-bold text-label-bold hover:bg-primary-container hover:text-white transition-all text-center block sm:inline-block">Login</RouterLink>
          </div>
        </div>
      </div>
    </section>

    <LandingPublicFooter />

    <!-- Cookie Banner Replacement floating element -->
    <button v-if="showCookieSettingsButton" @click="openCookieSettings" class="fixed bottom-6 right-6 bg-[#f97316] text-white px-6 py-3 rounded-full shadow-2xl font-bold text-sm flex items-center gap-2 hover:-translate-y-1 hover:shadow-[0_10px_25px_-5px_rgba(249,115,22,0.5)] transition-all z-50">
      <span class="material-symbols-outlined text-lg" data-icon="settings">settings</span>
      Cookie Settings
    </button>

  </div>
</template>

<style scoped>
@import '@/features/Landing/components/landing-public.css';
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');

.landing-page-root {
  overflow-x: hidden;
}

@keyframes float {
  0% { transform: translateY(0px) rotate(2deg); }
  50% { transform: translateY(-20px) rotate(0deg); }
  100% { transform: translateY(0px) rotate(2deg); }
}

.animate-float {
  animation: float 6s ease-in-out infinite;
}

/* Custom Outlined Fonts Overrides */
.font-display-lg {
  font-family: 'Outfit', sans-serif !important;
  font-size: 72px !important;
  line-height: 1.1 !important;
  letter-spacing: -0.04em !important;
  font-weight: 800 !important;
}
.font-display-lg-mobile {
  font-family: 'Outfit', sans-serif !important;
  font-size: 40px !important;
  line-height: 1.2 !important;
  letter-spacing: -0.02em !important;
  font-weight: 800 !important;
}
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

/* Color palettes */
.bg-background { background-color: #f8f9ff !important; }
.text-on-background { color: #0b1c30 !important; }
.text-on-surface-variant { color: #424754 !important; }
.bg-surface-container-low { background-color: #eff4ff !important; }
.bg-surface-variant { background-color: #d3e4fe !important; }
.bg-primary { background-color: #0058be !important; }
.text-primary { color: #0058be !important; }
.border-primary { border-color: #0058be !important; }
.bg-on-background { background-color: #0b1c30 !important; }
.text-on-primary { color: #ffffff !important; }
.bg-surface-container { background-color: #e5eeff !important; }
.bg-surface { background-color: #f8f9ff !important; }
.text-surface-bright { color: #f8f9ff !important; }
.bg-tertiary { background-color: #994100 !important; }
.text-tertiary { color: #994100 !important; }

/* Custom margins / spacing properties mapped to tailwind extend */
.max-w-container-max { max-width: 1280px !important; }
.gap-stack-md { gap: 16px !important; }
.gap-stack-sm { gap: 8px !important; }
.py-section-padding-desktop { padding-top: 140px !important; padding-bottom: 140px !important; }
.py-section-padding-mobile { padding-top: 80px !important; padding-bottom: 80px !important; }
.pb-section-padding-mobile { padding-bottom: 80px !important; }
.pb-section-padding-desktop { padding-bottom: 140px !important; }

</style>
