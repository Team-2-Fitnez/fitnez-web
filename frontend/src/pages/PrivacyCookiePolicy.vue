<script setup lang="ts">
import { RouterLink } from 'vue-router'
import LandingPublicNav from '../components/landing/LandingPublicNav.vue'
</script>

<template>
  <div class="bg-surface-bright text-on-surface antialiased min-h-screen flex flex-col gradient-bg Outfit pt-20">
    <LandingPublicNav variant="static" />

    <!-- Main Content Grid -->
    <main class="flex-grow max-w-container-max mx-auto w-full px-gutter py-12 flex flex-col md:flex-row gap-8 relative">
      <!-- Table of Contents Sidebar -->
      <aside class="w-full md:w-1/4 flex-shrink-0">
        <div class="sticky top-[100px] bg-surface-container-lowest p-6 rounded-xl shadow-sm border border-outline-variant">
          <h3 class="font-headline-lg text-[22px] font-bold mb-6 text-on-background">Contents</h3>
          <nav class="flex flex-col gap-4 font-body-md">
            <a class="text-on-surface-variant hover:text-primary transition-colors font-label-bold text-label-bold flex items-center gap-2" href="#introduction">
              <span class="material-symbols-outlined text-[20px] text-primary">info</span> Introduction
            </a>
            <a class="text-on-surface-variant hover:text-primary transition-colors font-label-bold text-label-bold flex items-center gap-2" href="#legal-basis">
              <span class="material-symbols-outlined text-[20px] text-primary">gavel</span> Legal Basis
            </a>
            <a class="text-on-surface-variant hover:text-primary transition-colors font-label-bold text-label-bold flex items-center gap-2" href="#cookie-usage">
              <span class="material-symbols-outlined text-[20px] text-primary">cookie</span> Cookie Usage
            </a>
            <a class="text-on-surface-variant hover:text-primary transition-colors font-label-bold text-label-bold flex items-center gap-2" href="#third-party-transfers">
              <span class="material-symbols-outlined text-[20px] text-primary">share</span> Third Party Transfers
            </a>
            <a class="text-on-surface-variant hover:text-primary transition-colors font-label-bold text-label-bold flex items-center gap-2" href="#user-rights">
              <span class="material-symbols-outlined text-[20px] text-primary">verified_user</span> User Rights
            </a>
            <a class="text-on-surface-variant hover:text-primary transition-colors font-label-bold text-label-bold flex items-center gap-2" href="#contact">
              <span class="material-symbols-outlined text-[20px] text-primary">contact_support</span> Contact DPO
            </a>
          </nav>
        </div>
      </aside>

      <!-- Main Content Canvas -->
      <article class="w-full md:w-3/4 bg-surface-container-lowest p-8 md:p-12 rounded-xl shadow-[0_20px_40px_-15px_rgba(11,28,48,0.05)] border border-outline-variant prose max-w-none">
        <header class="mb-12 border-b border-outline-variant/30 pb-8">
          <h1 class="font-display-lg-mobile md:font-headline-xl text-on-background mb-4">Privacy &amp; Cookie Policy</h1>
          <p class="font-body-lg text-body-lg text-on-surface-variant">Last updated: June 2, 2026</p>
        </header>

        <!-- Section 1 -->
        <section class="mb-12 scroll-mt-24" id="introduction">
          <h2 class="font-headline-xl text-[28px] text-on-background mb-6 flex items-center gap-3">
            <span class="material-symbols-outlined text-primary text-[32px]">info</span>
            Introduction &amp; Data Controller
          </h2>
          <p class="font-body-md text-body-md text-on-surface-variant mb-4 leading-relaxed">
            Fitnez Gym ("we", "us", "our", or the "Company") is fully committed to protecting your privacy and complying with the Personal Data Protection Act (PDPA) No. 27 of 2022 and other relevant laws and regulations in the Republic of Indonesia. This policy describes how we collect, use, store, and protect your Personal Data when you use our website and fitness services.
          </p>
          <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
            In the context of international data protection regulations, Fitnez Gym acts as the <strong>Data Controller</strong>. By using our services, you consent to the data processing practices described in this policy. We recommend that you read this document carefully.
          </p>
        </section>

        <!-- Section 2 -->
        <section class="mb-12 scroll-mt-24" id="legal-basis">
          <h2 class="font-headline-xl text-[28px] text-on-background mb-6 flex items-center gap-3">
            <span class="material-symbols-outlined text-primary text-[32px]">gavel</span>
            Legal Basis for Processing
          </h2>
          <p class="font-body-md text-body-md text-on-surface-variant mb-4 leading-relaxed">
            We process your Personal Data based on one or more of the following legal bases in accordance with Chapter IV Article 20 of the PDPA and international GDPR Article 6 standards:
          </p>
          <ul class="list-disc pl-6 font-body-md text-body-md text-on-surface-variant space-y-2 mb-6">
            <li><strong>Explicit Consent:</strong> You have given valid, explicit, and revocable consent for one or more specific purposes (e.g., marketing, analytical cookies).</li>
            <li><strong>Contract Performance:</strong> Processing is necessary to provide access to your gym membership registration system.</li>
            <li><strong>Legal Obligation:</strong> We need to process your data to comply with legal obligations required by authorized state authorities.</li>
            <li><strong>Legitimate Interests:</strong> To improve our service quality, gym facility security, and prevent transaction registration fraud, while balancing your privacy rights.</li>
          </ul>
        </section>

        <!-- Section 3 -->
        <section class="mb-12 scroll-mt-24" id="cookie-usage">
          <h2 class="font-headline-xl text-[28px] text-on-background mb-6 flex items-center gap-3">
            <span class="material-symbols-outlined text-primary text-[32px]">cookie</span>
            Cookie Usage &amp; Retention Periods
          </h2>
          <p class="font-body-md text-body-md text-on-surface-variant mb-4 leading-relaxed">
            Our website uses cookies, web beacons, and other tracking technologies to optimize your user experience, analyze site traffic, and personalize content. We categorize cookies as follows:
          </p>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 mb-6">
            <div class="bg-surface p-6 rounded-lg border border-outline-variant">
              <h4 class="font-headline-lg text-[18px] text-on-background mb-2">Essential / Security Cookies</h4>
              <p class="font-body-md text-[14px] text-on-surface-variant">Strictly necessary for login, session tokens, and basic security registration functionality such as CSRF protection. <span class="text-xs font-bold bg-green-100 text-green-800 px-2 py-0.5 rounded-full inline-block mt-2">Always Active</span> (Retention: 1 - 12 months)</p>
            </div>
            <div class="bg-surface p-6 rounded-lg border border-outline-variant">
              <h4 class="font-headline-lg text-[18px] text-on-background mb-2">Statistics / Performance Cookies</h4>
              <p class="font-body-md text-[14px] text-on-surface-variant">Help us understand how visitors interact with the registration site via anonymous traffic analytics. <span class="text-xs font-bold bg-orange-100 text-orange-800 px-2 py-0.5 rounded-full inline-block mt-2">Optional</span> (Retention: 1 - 24 months)</p>
            </div>
            <div class="bg-surface p-6 rounded-lg border border-outline-variant">
              <h4 class="font-headline-lg text-[18px] text-on-background mb-2">Preference / Functional Cookies</h4>
              <p class="font-body-md text-[14px] text-on-surface-variant">Enable the site to remember your cookie banner choices and regional display preferences. <span class="text-xs font-bold bg-orange-100 text-orange-800 px-2 py-0.5 rounded-full inline-block mt-2">Optional</span> (Retention: 12 months)</p>
            </div>
          </div>
        </section>

        <!-- Section 4 -->
        <section class="mb-12 scroll-mt-24" id="third-party-transfers">
          <h2 class="font-headline-xl text-[28px] text-on-background mb-6 flex items-center gap-3">
            <span class="material-symbols-outlined text-primary text-[32px]">share</span>
            Data Transfers to Third Parties &amp; Internationally
          </h2>
          <p class="font-body-md text-body-md text-on-surface-variant mb-4 leading-relaxed">
            We share certain encrypted technical data with high-security certified external service providers to monitor performance and process payments securely.
          </p>
          <div class="overflow-x-auto mt-6 rounded-lg border border-outline-variant">
            <table class="w-full text-left border-collapse text-sm">
              <thead class="bg-surface-container text-on-background font-label-bold text-label-bold">
                <tr class="border-b border-outline-variant">
                  <th class="p-4">Third Party</th>
                  <th class="p-4">Processing Purpose</th>
                  <th class="p-4">Data Shared</th>
                  <th class="p-4">Legal Basis for Transfer</th>
                </tr>
              </thead>
              <tbody class="font-body-md text-body-md text-on-surface-variant">
                <tr class="hover:bg-surface/50 transition-colors border-b border-outline-variant">
                  <td class="p-4 font-semibold">Google Analytics</td>
                  <td class="p-4">Statistical analysis of landing page visits</td>
                  <td class="p-4">Anonymous IP, browser data, button interactions</td>
                  <td class="p-4">EU-US Data Privacy Framework / Standard Contractual Clauses (SCC)</td>
                </tr>
                <tr class="hover:bg-surface/50 transition-colors border-b border-outline-variant">
                  <td class="p-4 font-semibold">Bank BCA &amp; QRIS</td>
                  <td class="p-4">Processing membership payment transactions</td>
                  <td class="p-4">Registration code, amount, transfer proof</td>
                  <td class="p-4">Transaction Consent &amp; Local Banking SSL Encryption</td>
                </tr>
                <tr class="hover:bg-surface/50 transition-colors">
                  <td class="p-4 font-semibold">Socket.io (WebSockets)</td>
                  <td class="p-4">Real-time synchronization of chat conversations &amp; notifications</td>
                  <td class="p-4">User ID, read status, chat message content</td>
                  <td class="p-4">WSS Security Protocol with TLS 1.3 Encryption</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Section 5 -->
        <section class="mb-12 scroll-mt-24" id="user-rights">
          <h2 class="font-headline-xl text-[28px] text-on-background mb-6 flex items-center gap-3">
            <span class="material-symbols-outlined text-primary text-[32px]">verified_user</span>
            Rights of Data Subjects (Users)
          </h2>
          <p class="font-body-md text-body-md text-on-surface-variant mb-4 leading-relaxed">
            In accordance with Chapter IV of the Indonesian PDPA and GDPR Articles 12-22, you have full rights over your Personal Data:
          </p>
          <ul class="list-disc pl-6 font-body-md text-body-md text-on-surface-variant space-y-2 mb-6">
            <li><strong>Right to Information:</strong> To receive clear identity, legal basis, and purpose of request for your data.</li>
            <li><strong>Right of Access &amp; Rectification:</strong> To request copies of your data and complete, update, or correct errors in your personal data.</li>
            <li><strong>Right to Erasure (Right to be Forgotten):</strong> To end processing and request deletion of your account and associated data if no longer active.</li>
            <li><strong>Right to Data Portability:</strong> To request and transfer your personal data to another data controller in a structured digital format.</li>
            <li><strong>Right to Object:</strong> To withdraw consent for processing optional analytical or preference cookies at any time.</li>
          </ul>
        </section>

        <!-- Section 6 -->
        <section class="bg-surface p-8 rounded-lg border border-outline-variant text-center scroll-mt-24" id="contact">
          <h2 class="font-headline-xl text-[24px] text-on-background mb-4 flex justify-center items-center gap-3">
            <span class="material-symbols-outlined text-primary text-[32px]">contact_support</span>
            Contact Data Protection Officer (DPO)
          </h2>
          <p class="font-body-md text-body-md text-on-surface-variant mb-6 leading-relaxed">
            If you have any questions, complaints, wish to withdraw your cookie consent, or want to exercise your rights under international regulations, please contact our privacy team at:
          </p>
          <div class="inline-flex flex-col items-center gap-2 font-body-lg text-body-lg text-on-background">
            <div class="flex items-center gap-2"><span class="material-symbols-outlined text-primary">email</span> Email: privacy@fitnezgym.com</div>
            <div class="flex items-center gap-2"><span class="material-symbols-outlined text-primary">phone</span> Phone: +62 21 555 1234</div>
            <div class="flex items-center gap-2"><span class="material-symbols-outlined text-primary">location_on</span> Address: Fitnez Tower Building, Jl. Sudirman No. 88, South Jakarta 12190</div>
          </div>
        </section>
      </article>
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
.bg-surface-container-lowest, .bg-surface-lowest { background-color: #ffffff !important; }
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
.gap-stack-lg { gap: 32px !important; }
.py-stack-sm { padding-top: 8px !important; padding-bottom: 8px !important; }
.scale-102 { transform: scale(1.02); }

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
