<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import LandingPublicNav from '../components/landing/LandingPublicNav.vue'
import LandingPublicFooter from '../components/landing/LandingPublicFooter.vue'

onMounted(() => {
  document.title = '404 | Fitnez Gym'
})
</script>

<template>
  <div class="not-found-page landing-page-root bg-background text-on-background font-body-md">
    <LandingPublicNav variant="static" />

    <main class="not-found-hero">
      <div class="not-found-glow not-found-glow--left"></div>
      <div class="not-found-glow not-found-glow--right"></div>

      <section class="not-found-shell">
        <div class="not-found-copy">
          <p class="not-found-label">I guess someone get lost</p>
          <h1>404</h1>
          <h2>This page that you looking for isn't in our development.</h2>
          <div class="not-found-actions">
            <RouterLink class="not-found-primary" to="/">Go back to Landing Page</RouterLink>
            <RouterLink class="not-found-secondary" to="/login/member">Or, log In to Account</RouterLink>
          </div>
        </div>

        <div class="not-found-stage">
          <div class="error-card error-card--main">
            <span>Page Status</span>
            <strong>Not Found</strong>
            <p>404</p>
          </div>
          <div class="error-card error-card--ghost">
            <span>Fitnez Signal</span>
            <strong>Route Missing</strong>
          </div>
        </div>
      </section>
    </main>

    <LandingPublicFooter />
  </div>
</template>

<style scoped>
@import '../components/landing/landing-public.css';
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap');

.font-headline-lg {
  font-family: 'Outfit', sans-serif !important;
}
.font-label-bold {
  font-family: 'Outfit', sans-serif !important;
  font-weight: 700 !important;
}
.bg-background { background-color: #f8f9ff !important; }
.text-on-background { color: #0b1c30 !important; }
.bg-on-background { background-color: #0b1c30 !important; }
.bg-primary { background-color: #0058be !important; }
.text-primary { color: #0058be !important; }

.not-found-page {
  background:
    radial-gradient(circle at 12% 0%, rgba(183, 211, 244, 0.48), transparent 32rem),
    radial-gradient(circle at 88% 12%, rgba(249, 115, 22, 0.12), transparent 24rem),
    linear-gradient(180deg, #f8f5ef 0%, #fffdf9 48%, #f7fbff 100%);
  color: #0f172a;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.not-found-hero {
  overflow: hidden;
  padding: 9rem 1.25rem 5rem;
  position: relative;
  flex: 1;
}

.not-found-shell {
  align-items: center;
  display: grid;
  gap: 2rem;
  grid-template-columns: minmax(0, 1.05fr) minmax(300px, 0.95fr);
  margin: 0 auto;
  max-width: 1220px;
}

.not-found-copy {
  position: relative;
  z-index: 2;
}

.not-found-label {
  color: #f97316;
  font-size: 0.82rem;
  font-weight: 900;
  letter-spacing: 0.22em;
  margin: 0 0 1rem;
  text-transform: uppercase;
}

.not-found-copy h1 {
  color: #0f172a;
  font-family: 'Outfit', sans-serif;
  font-size: clamp(5.5rem, 16vw, 11rem);
  font-weight: 950;
  letter-spacing: -0.08em;
  line-height: 0.9;
  margin: 0;
}

.not-found-copy h2 {
  color: #0f172a;
  font-family: 'Outfit', sans-serif;
  font-size: clamp(1.4rem, 3vw, 2.4rem);
  font-weight: 900;
  letter-spacing: -0.03em;
  line-height: 1.08;
  margin: 1rem 0;
  max-width: 12ch;
}

.not-found-copy p {
  color: #475569;
  font-size: 1rem;
  font-weight: 600;
  line-height: 1.75;
  margin: 0;
  max-width: 38rem;
}

.not-found-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-top: 2rem;
}

.not-found-primary,
.not-found-secondary {
  border-radius: 999px;
  display: inline-flex;
  font-size: 0.95rem;
  font-weight: 900;
  justify-content: center;
  min-height: 3.3rem;
  padding: 1rem 1.6rem;
  text-decoration: none;
}

.not-found-primary {
  background: #f97316;
  color: #ffffff;
  box-shadow: 0 20px 45px rgba(249, 115, 22, 0.28);
}

.not-found-secondary {
  background: transparent;
  border: 1px solid rgba(15, 23, 42, 0.18);
  color: #0f172a;
}

.not-found-stage {
  min-height: 28rem;
  position: relative;
}

.error-card {
  backdrop-filter: blur(10px);
  background: rgba(15, 23, 42, 0.92);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 2rem;
  box-shadow: 0 26px 70px rgba(15, 23, 42, 0.22);
  color: #ffffff;
  position: absolute;
}

.error-card--main {
  inset: 12% 8% auto auto;
  padding: 1.6rem;
  transform: rotate(-7deg);
  width: min(100%, 20rem);
}

.error-card--main span,
.error-card--ghost span {
  color: rgba(255, 255, 255, 0.64);
  display: block;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

.error-card--main strong,
.error-card--ghost strong {
  display: block;
  font-size: 1.8rem;
  font-weight: 950;
  margin-top: 0.55rem;
}

.error-card--main p {
  color: #f97316;
  font-size: 5rem;
  font-weight: 950;
  line-height: 0.9;
  margin: 0.9rem 0 0;
}

.error-card--ghost {
  background: rgba(255, 255, 255, 0.88);
  color: #0f172a;
  inset: auto auto 16% 6%;
  padding: 1.35rem;
  transform: rotate(8deg);
  width: min(100%, 17rem);
}

.error-card--ghost span {
  color: #475569;
}

.error-outline {
  align-items: center;
  border: 1px dashed rgba(15, 23, 42, 0.24);
  border-radius: 2.4rem;
  color: #0f172a;
  display: flex;
  font-size: clamp(2.4rem, 8vw, 4.8rem);
  font-weight: 950;
  inset: 8% 6% 10%;
  justify-content: center;
  letter-spacing: -0.06em;
  position: absolute;
}

.not-found-glow {
  border-radius: 999px;
  filter: blur(70px);
  pointer-events: none;
  position: absolute;
}

.not-found-glow--left {
  background: rgba(59, 130, 246, 0.22);
  height: 20rem;
  left: -4rem;
  top: 6rem;
  width: 20rem;
}

.not-found-glow--right {
  background: rgba(249, 115, 22, 0.18);
  height: 22rem;
  right: -5rem;
  top: 8rem;
  width: 22rem;
}

@media (max-width: 980px) {
  .not-found-shell {
    grid-template-columns: 1fr;
  }

  .not-found-copy h2 {
    max-width: none;
  }

  .not-found-stage {
    min-height: 24rem;
  }
}

@media (max-width: 640px) {
  .not-found-hero {
    padding-top: 8rem;
  }

  .not-found-actions {
    display: grid;
  }

  .not-found-primary,
  .not-found-secondary {
    width: 100%;
  }

  .error-card--main,
  .error-card--ghost {
    position: relative;
    inset: auto;
    margin: 0 auto 1rem;
    transform: none;
    width: 100%;
  }

  .error-outline {
    inset: auto;
    min-height: 10rem;
    position: relative;
  }
}
</style>
