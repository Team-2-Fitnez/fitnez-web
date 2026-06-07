<script setup lang="ts">
type InsightItem = {
  label: string
  value: string | number
  hint?: string
}

withDefaults(defineProps<{
  title: string
  subtitle: string
  items: InsightItem[]
  actionLabel?: string
  actionTo?: string
}>(), {
  actionLabel: '',
  actionTo: '',
})
</script>

<template>
  <section class="member-insight">
    <div class="member-insight__copy">
      <p class="member-insight__eyebrow">Ringkasan Member</p>
      <h2>{{ title }}</h2>
      <p>{{ subtitle }}</p>
    </div>

    <div class="member-insight__items">
      <article v-for="item in items" :key="item.label" class="member-insight__item">
        <span>{{ item.label }}</span>
        <strong>{{ item.value }}</strong>
        <small v-if="item.hint">{{ item.hint }}</small>
      </article>
    </div>

    <RouterLink v-if="actionLabel && actionTo" :to="actionTo" class="member-insight__action">
      {{ actionLabel }}
    </RouterLink>
  </section>
</template>

<style scoped>
.member-insight {
  align-items: stretch;
  background: #ffffff;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 1.25rem;
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
  display: grid;
  gap: 1rem;
  grid-template-columns: minmax(0, 1.2fr) minmax(280px, 1fr);
  margin-bottom: 1.25rem;
  padding: 1.25rem;
}

.member-insight__copy h2 {
  color: #0f172a;
  font-size: clamp(1.1rem, 2vw, 1.45rem);
  font-weight: 900;
  margin: 0.25rem 0 0;
}

.member-insight__copy p {
  color: #64748b;
  font-size: 0.9rem;
  font-weight: 700;
  line-height: 1.6;
  margin: 0.4rem 0 0;
  max-width: 46rem;
}

.member-insight__eyebrow {
  color: #2563eb !important;
  font-size: 0.72rem !important;
  font-weight: 900 !important;
  letter-spacing: 0.22em;
  margin: 0 !important;
  text-transform: uppercase;
}

.member-insight__items {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.member-insight__item {
  background: #f8fafc;
  border: 1px solid rgba(15, 23, 42, 0.06);
  border-radius: 1rem;
  min-height: 6rem;
  padding: 0.9rem;
}

.member-insight__item span {
  color: #64748b;
  display: block;
  font-size: 0.72rem;
  font-weight: 900;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.member-insight__item strong {
  color: #0f172a;
  display: block;
  font-size: 1.45rem;
  font-weight: 950;
  margin-top: 0.35rem;
}

.member-insight__item small {
  color: #64748b;
  display: block;
  font-size: 0.75rem;
  font-weight: 800;
  margin-top: 0.25rem;
}

.member-insight__action {
  align-self: center;
  background: #0f172a;
  border-radius: 0.9rem;
  color: #ffffff;
  font-size: 0.85rem;
  font-weight: 900;
  justify-self: end;
  padding: 0.85rem 1rem;
  text-decoration: none;
}

@media (max-width: 900px) {
  .member-insight {
    grid-template-columns: 1fr;
  }

  .member-insight__items {
    grid-template-columns: 1fr;
  }

  .member-insight__action {
    justify-self: stretch;
    text-align: center;
  }
}
</style>
