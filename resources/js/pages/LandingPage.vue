<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import TaktLogo from '@/components/TaktLogo.vue'
import { formatMonthName } from '@/utils/date.ts'
import { demo, login } from '@/wayfinder/routes'

defineOptions({ layout: () => false })

type Feature = {
  icon: string,
  title: string,
  description: string,
}

type StackItem = {
  icon: string,
  label: string,
  color: string,
  class?: string,
}

const features: Feature[] = [
  {
    icon: 'i-lucide-layout-dashboard',
    title: 'Tableau de bord',
    description: 'KPIs mensuels, graphique de revenus sur 12 mois et projection de fin de mois.',
  },
  {
    icon: 'i-lucide-calendar-days',
    title: 'Feuille de temps',
    description: 'Grille calendaire mensuelle par projet, saisie en fraction de journée. Jours fériés français intégrés.',
  },
  {
    icon: 'i-lucide-folder-kanban',
    title: 'Projets & Clients',
    description: 'TJM par projet, budgets mensuels et totaux, duplication, corbeille et restauration.',
  },
  {
    icon: 'i-lucide-euro',
    title: 'Suivi de la facturation',
    description: 'Suivi des factures par projet avec alertes de dépassement de budget mensuel ou cumulé.',
  },
  {
    icon: 'i-lucide-share-2',
    title: 'Partage client',
    description: 'Lien public en lecture seule pour partager un bilan de facturation sans compte requis.',
  },
  {
    icon: 'i-lucide-receipt',
    title: 'Pas d\'édition de factures',
    description: 'Délègue l\'édition de devis ou de factures à votre Plateforme Agréée de facturation electronique (PA/PDP).',
  },
]

const stack: StackItem[] = [
  { icon: 'i-simple-icons:laravel', label: 'PHP Laravel', color: 'text-red-500' },
  { icon: 'i-simple-icons:vuedotjs', label: 'Vue + Nuxt UI', color: 'text-emerald-500' },
  { icon: 'i-simple-icons:typescript', label: 'TypeScript', color: 'text-blue-500' },
  { icon: 'i-simple-icons:tailwindcss', label: 'Tailwind CSS', color: 'text-cyan-500' },
  { icon: 'i-simple-icons:postgresql', label: 'PostgreSQL', color: 'text-sky-600' },
  { icon: 'i-simple-icons:github', label: 'GitHub OAuth', color: 'text-slate-500' },
  { icon: 'i-simple-icons:docker', label: 'Container Docker prêt à l\'emploi', color: 'text-blue-400', class: 'col-span-2 justify-center' },
]
</script>

<template>
  <UApp>
    <div class="min-h-screen bg-white text-slate-900 dark:bg-slate-950 dark:text-slate-100">
      <!-- Header -->
      <header class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/90 backdrop-blur-md dark:border-slate-800/60 dark:bg-slate-950/80">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
          <div class="flex items-center gap-2">
            <TaktLogo class="h-7 w-7" />
            <span class="text-sm font-semibold tracking-tight">Takt</span>
          </div>
          <div class="flex items-center gap-3">
            <UButton
              :href="login()"
              variant="outline"
              color="neutral"
              icon="i-lucide-log-in"
              label="Connexion"
              size="sm"
            />
          </div>
        </div>
      </header>

      <!-- ═══════════════════════════════════════════
           HERO — fond blanc avec glows et points
      ═══════════════════════════════════════════ -->
      <section class="relative overflow-hidden px-6 pb-20 pt-16 lg:pb-28 lg:pt-24">
        <!-- Pattern points -->
        <div
          class="pointer-events-none absolute inset-0 opacity-50 dark:opacity-30"
          style="background-image: radial-gradient(circle, rgba(148,163,184,0.25) 1px, transparent 1px);
                 background-size: 28px 28px;"
        />
        <!-- Glow rose haut-gauche -->
        <div class="pointer-events-none absolute -left-32 -top-32 size-[500px] rounded-full bg-primary-400/15 blur-3xl dark:bg-primary-500/10" />
        <!-- Glow indigo bas-droite -->
        <div class="pointer-events-none absolute -bottom-24 right-0 size-[400px] rounded-full bg-indigo-400/10 blur-3xl dark:bg-indigo-500/8" />

        <div class="relative mx-auto max-w-7xl">
          <div class="grid items-center gap-12 lg:grid-cols-5">
            <div class="lg:col-span-3">
              <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-primary-200 bg-primary-50 px-3 py-1 text-xs font-medium text-primary-600 dark:border-primary-500/20 dark:bg-primary-500/5 dark:text-primary-400">
                <UIcon name="i-lucide-calendar-days" class="size-3" />
                Outil pour freelance et intermittent · Gratuit et open source
              </div>

              <h1 class="mb-5 text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                Gérez votre temps.<br />
                <span class="text-primary-500 dark:text-primary-400">Suivez vos revenus.</span>
              </h1>

              <p class="mb-8 max-w-lg text-base leading-relaxed text-slate-500 dark:text-slate-400 sm:text-lg">
                Takt centralise votre suivi du temps, vos projets et votre facturation.
                Simple à prendre en main, gratuit.
              </p>

              <div class="flex flex-wrap gap-3">
                <Link href="/login">
                  <UButton label="S'inscrire" size="lg" icon="i-lucide-arrow-right" trailing />
                </Link>
                <UButton
                  href="https://github.com/mathieutu/takt"
                  target="_blank"
                  label="Code source"
                  size="lg"
                  variant="ghost"
                  color="neutral"
                  icon="i-simple-icons:github"
                />
              </div>

              <p class="mt-4 text-sm text-slate-400 dark:text-slate-500">
                <UButton :href="demo()" label="Essayer la démo sans inscription" variant="link" color="neutral" />
              </p>
            </div>

            <!-- Mini dashboard décoratif -->
            <div class="lg:col-span-2">
              <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white/80 p-5 shadow-xl ring-1 ring-slate-200/50 backdrop-blur-sm dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/50">
                <div class="mb-4 flex items-center gap-1.5">
                  <div class="size-2.5 rounded-full bg-red-400" />
                  <div class="size-2.5 rounded-full bg-yellow-400" />
                  <div class="size-2.5 rounded-full bg-green-400" />
                  <span class="ml-2 text-xs text-slate-400">Takt</span>
                </div>

                <div class="mb-4 grid grid-cols-2 gap-2">
                  <div
                    v-for="kpi in [
                      { label: 'Ce mois', value: '18 j', sub: `+12% / ${formatMonthName(new Date().getMonth())}` },
                      { label: 'Revenus', value: '9 600 €', sub: `projeté fin ${formatMonthName(new Date().getMonth() + 1)}` },
                      { label: 'À facturer', value: '3 200 €', sub: 'en retard' },
                      { label: 'TJM', value: '650 €', sub: `moyen sur ${Math.floor(Math.random() * 5) + 2} projets` },
                    ]"
                    :key="kpi.label"
                    class="rounded-lg border border-slate-100 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800"
                  >
                    <p class="text-xs text-slate-400">{{ kpi.label }}</p>
                    <p class="mt-0.5 text-base font-semibold text-slate-800 dark:text-slate-100">{{ kpi.value }}</p>
                    <p class="text-xs text-primary-500 lowercase">{{ kpi.sub }}</p>
                  </div>
                </div>

                <div class="flex items-end gap-1 px-1" style="height: 60px">
                  <div
                    v-for="(h, i) in [30, 55, 40, 70, 45, 80, 60, 50, 75, 85, 65, 90]"
                    :key="i"
                    class="flex-1 rounded-t"
                    :class="i === 11 ? 'bg-primary-500/80' : 'bg-primary-400/40 dark:bg-primary-500/30 hover:bg-primary-500/80'"
                    :style="{ height: `${h}%` }"
                  />
                </div>
                <p class="mt-2 text-center text-xs text-slate-400">Revenus sur 12 mois</p>

                <div class="pointer-events-none absolute -bottom-6 -right-6 size-28 rounded-full bg-primary-400/20 blur-2xl dark:bg-primary-500/10" />
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══════════════════════════════════════════
           FEATURES — fond dégradé avec grille
      ═══════════════════════════════════════════ -->
      <section class="relative overflow-hidden bg-slate-50 px-6 py-20 dark:bg-slate-900">
        <!-- Dégradé light mode uniquement -->
        <div class="pointer-events-none absolute inset-0 bg-linear-to-b from-slate-50 to-slate-100 dark:hidden" />
        <!-- Grille subtile -->
        <div
          class="pointer-events-none absolute inset-0 opacity-100 dark:opacity-40"
          style="background-image: linear-gradient(rgba(100,116,139,0.07) 1px, transparent 1px),
                                   linear-gradient(90deg, rgba(100,116,139,0.07) 1px, transparent 1px);
                 background-size: 40px 40px;"
        />
        <!-- Accent rose en haut à droite -->
        <div class="pointer-events-none absolute right-0 top-0 h-px w-48 bg-linear-to-l from-primary-400/40 to-transparent dark:from-primary-500/30" />
        <div class="pointer-events-none absolute right-0 top-0 h-48 w-px bg-linear-to-b from-primary-400/40 to-transparent dark:from-primary-500/30" />

        <div class="relative mx-auto max-w-7xl dark:text-slate-100">
          <div class="mb-10">
            <div class="mb-3 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-primary-500 dark:text-primary-400">
              <div class="h-px w-6 bg-primary-400" />
              Fonctionnalités
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
              Tout ce qu'il faut, rien de plus
            </h2>
            <p class="mt-2 text-slate-500 dark:text-slate-400">
              Conçu pour les freelances qui veulent aller à l'essentiel.
            </p>
          </div>

          <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
              v-for="feature in features"
              :key="feature.title"
              class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-5 transition-all hover:-translate-y-0.5 hover:shadow-lg dark:border-slate-700 dark:bg-slate-800/50"
            >
              <!-- Accent coloré en haut -->
              <div class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-primary-400/0 via-primary-400/60 to-primary-400/0 opacity-0 transition-opacity group-hover:opacity-100" />
              <div class="mb-3 inline-flex size-9 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-500/10">
                <UIcon :name="feature.icon" class="size-4 text-primary-500 dark:text-primary-400" />
              </div>
              <h3 class="mb-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ feature.title }}</h3>
              <p class="text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ feature.description }}</p>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══════════════════════════════════════════
           SCREENSHOTS — section contrastée light
      ═══════════════════════════════════════════ -->
      <section class="relative overflow-hidden px-6 py-20">
        <!-- Fond dégradé teinté indigo/violet très clair -->
        <div class="pointer-events-none absolute inset-0 bg-linear-to-br from-indigo-50 via-slate-100 to-pink-50/60 dark:from-indigo-950/40 dark:via-slate-900 dark:to-pink-950/20" />
        <!-- Pattern grille -->
        <div
          class="pointer-events-none absolute inset-0"
          style="background-image: linear-gradient(rgba(99,102,241,0.06) 1px, transparent 1px),
                                   linear-gradient(90deg, rgba(99,102,241,0.06) 1px, transparent 1px);
                 background-size: 36px 36px;"
        />
        <!-- Glow indigo haut-gauche -->
        <div class="pointer-events-none absolute -left-24 -top-24 size-80 rounded-full bg-indigo-400/15 blur-3xl dark:bg-indigo-500/8" />
        <!-- Glow rose bas-droite -->
        <div class="pointer-events-none absolute -bottom-16 -right-16 size-96 rounded-full bg-pink-400/12 blur-3xl dark:bg-pink-500/6" />

        <div class="relative mx-auto max-w-7xl">
          <div class="mb-10">
            <div class="mb-3 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-indigo-500 dark:text-indigo-400">
              <div class="h-px w-6 bg-indigo-400" />
              Interface
            </div>
            <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
              Une interface pensée pour aller vite
            </h2>
            <p class="mt-2 text-slate-500 dark:text-slate-400">
              Vue d'ensemble, saisie rapide, partage en un clic.
            </p>
          </div>

          <div class="grid gap-5 lg:grid-cols-3">
            <!-- ── Mockup 1 : Tableau de bord ─────────────── -->
            <div class="overflow-hidden rounded-xl border border-white bg-white shadow-md ring-1 ring-indigo-100/80 dark:border-slate-700 dark:bg-slate-800 dark:ring-slate-700">
              <div class="relative aspect-video w-full overflow-hidden bg-slate-50 p-3 dark:bg-slate-900/80">
                <div class="mb-2 flex items-center gap-1">
                  <div class="size-1.5 rounded-full bg-red-400" /><div class="size-1.5 rounded-full bg-yellow-400" /><div class="size-1.5 rounded-full bg-green-400" />
                  <span class="ml-1.5 text-[9px] font-medium text-slate-500 dark:text-slate-400">Tableau de bord</span>
                  <span class="ml-auto rounded border border-primary-200 bg-primary-50 px-1.5 text-[7px] text-primary-600 dark:border-primary-500/20 dark:bg-primary-500/10 dark:text-primary-400">{{ formatMonthName(new Date().getMonth() + 1) }} {{ new Date().getFullYear() }}</span>
                </div>
                <div class="mb-1.5 grid grid-cols-4 gap-1">
                  <div class="rounded border border-slate-200 bg-white px-1.5 py-1 dark:border-slate-700 dark:bg-slate-800">
                    <p class="text-[6px] text-slate-400">Jours ce mois</p>
                    <p class="mt-0.5 text-[10px] font-bold text-slate-800 dark:text-slate-100">14 j</p>
                    <div class="my-0.5 h-px w-full rounded bg-slate-100 dark:bg-slate-700">
                      <div class="h-px rounded bg-primary-500" style="width:67%" />
                    </div>
                    <p class="text-[6px] text-emerald-500">↑ +12%</p>
                  </div>
                  <div class="rounded border border-slate-200 bg-white px-1.5 py-1 dark:border-slate-700 dark:bg-slate-800">
                    <p class="text-[6px] text-slate-400">Revenus</p>
                    <p class="mt-0.5 text-[10px] font-bold text-slate-800 dark:text-slate-100">9 100 €</p>
                    <p class="text-[6px] text-slate-400">proj. <span class="text-primary-500">13 650 €</span></p>
                    <p class="text-[6px] text-emerald-500">↑ +8%</p>
                  </div>
                  <div class="rounded border border-slate-200 bg-white px-1.5 py-1 dark:border-slate-700 dark:bg-slate-800">
                    <p class="text-[6px] text-slate-400">Factures</p>
                    <p class="mt-0.5 text-[10px] font-bold text-slate-800 dark:text-slate-100">3 200 €</p>
                    <p class="text-[6px] text-slate-400">2 impayées</p>
                    <p class="text-[6px] text-emerald-500">✓ Aucun retard</p>
                  </div>
                  <div class="rounded border border-slate-200 bg-white px-1.5 py-1 dark:border-slate-700 dark:bg-slate-800">
                    <p class="text-[6px] text-slate-400">12 mois</p>
                    <p class="mt-0.5 text-[10px] font-bold text-slate-800 dark:text-slate-100">98 400 €</p>
                    <p class="text-[6px] text-slate-400">TJM 650 €/j</p>
                    <p class="text-[6px] text-emerald-500">↑ +15%</p>
                  </div>
                </div>
                <div class="rounded border border-slate-200 bg-white p-1.5 dark:border-slate-700 dark:bg-slate-800">
                  <p class="mb-1 text-[7px] font-medium text-slate-500 dark:text-slate-400">Activité sur 12 mois</p>
                  <div class="flex items-end gap-0.5" style="height:38px">
                    <div
                      v-for="(h, i) in [28, 42, 55, 38, 62, 48, 70, 43, 58, 78, 63, 90]"
                      :key="i"
                      class="flex-1 rounded-t-sm"
                      :class="i === 11 ? 'bg-indigo-500' : 'bg-indigo-400/40'"
                      :style="{ height: `${h}%` }"
                    />
                  </div>
                  <div class="mt-0.5 flex justify-between text-[6px] text-slate-300 dark:text-slate-600">
                    <span>
                      {{ formatMonthName(new Date().getMonth() + 2).slice(0, 4) }}
                      {{ (new Date().getFullYear() - 1).toString().slice(2) }}
                    </span>
                    <span>
                      {{ formatMonthName(new Date().getMonth() + 1).slice(0, 4) }}
                      {{ new Date().getFullYear().toString().slice(2) }}
                    </span>
                  </div>
                </div>
              </div>
              <div class="border-t border-slate-100 px-4 py-2.5 dark:border-slate-700">
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Tableau de bord</p>
              </div>
            </div>

            <!-- ── Mockup 2 : Feuille de temps ──────────── -->
            <div class="overflow-hidden rounded-xl border border-white bg-white shadow-md ring-1 ring-indigo-100/80 dark:border-slate-700 dark:bg-slate-800 dark:ring-slate-700">
              <div class="relative aspect-video w-full overflow-hidden bg-slate-50 p-3 dark:bg-slate-900/80">
                <div class="mb-2 flex items-center justify-between">
                  <div class="flex items-center gap-1">
                    <div class="size-1.5 rounded-full bg-red-400" /><div class="size-1.5 rounded-full bg-yellow-400" /><div class="size-1.5 rounded-full bg-green-400" />
                    <span class="ml-1.5 text-[9px] font-medium text-slate-500 dark:text-slate-400">Feuille de temps</span>
                  </div>
                  <div class="flex items-center gap-1 text-[7px] text-slate-500">
                    <span>‹</span><span class="font-medium">juin 2025</span><span>›</span>
                  </div>
                </div>
                <div class="overflow-hidden rounded border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                  <div class="grid border-b border-slate-100 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/80" style="grid-template-columns: 3.5rem repeat(14, 1fr)">
                    <div class="px-1 py-1 text-[6px] text-slate-400">Projet</div>
                    <div
                      v-for="(d, i) in [2, 3, 4, 5, 6, 9, 10, 11, 12, 13, 16, 17, 18, 19]"
                      :key="i"
                      class="py-1 text-center text-[6px]"
                      :class="i === 5 ? 'bg-primary-50 font-semibold text-primary-500 dark:bg-primary-500/10' : 'text-slate-400'"
                    >
                      {{ d }}
                    </div>
                  </div>
                  <!-- Acme Corp -->
                  <div class="grid items-center border-b border-slate-100 dark:border-slate-700" style="grid-template-columns: 3.5rem repeat(14, 1fr)">
                    <div class="flex items-center gap-1 px-1 py-1">
                      <div class="size-1 shrink-0 rounded-full bg-indigo-500" /><span class="truncate text-[6.5px] text-slate-600 dark:text-slate-300">Acme Corp</span>
                    </div>
                    <div class="mx-px rounded-sm bg-indigo-400/70" style="height:11px" /><div class="mx-px rounded-sm bg-indigo-400/70" style="height:11px" /><div class="mx-px rounded-sm bg-indigo-400/70" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-indigo-400/70" style="height:11px" /><div class="mx-px rounded-sm bg-indigo-400/70" style="height:11px" /><div class="mx-px rounded-sm bg-indigo-400/70" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-indigo-400/70" style="height:11px" /><div class="mx-px rounded-sm bg-indigo-400/70" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-indigo-400/70" style="height:11px" /><div class="mx-px rounded-sm bg-indigo-400/70" style="height:11px" />
                  </div>
                  <!-- StartupXYZ -->
                  <div class="grid items-center border-b border-slate-100 dark:border-slate-700" style="grid-template-columns: 3.5rem repeat(14, 1fr)">
                    <div class="flex items-center gap-1 px-1 py-1">
                      <div class="size-1 shrink-0 rounded-full bg-rose-500" /><span class="truncate text-[6.5px] text-slate-600 dark:text-slate-300">StartupXYZ</span>
                    </div>
                    <div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-rose-400/70" style="height:11px" /><div class="mx-px rounded-sm bg-rose-400/70" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-rose-400/70" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-rose-400/70" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" />
                  </div>
                  <!-- Freelance -->
                  <div class="grid items-center" style="grid-template-columns: 3.5rem repeat(14, 1fr)">
                    <div class="flex items-center gap-1 px-1 py-1">
                      <div class="size-1 shrink-0 rounded-full bg-amber-500" /><span class="truncate text-[6.5px] text-slate-600 dark:text-slate-300">Freelance</span>
                    </div>
                    <div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-amber-400/70" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" /><div class="mx-px rounded-sm bg-amber-400/40" style="height:11px" /><div class="mx-px rounded-sm bg-slate-100 dark:bg-slate-700/40" style="height:11px" />
                  </div>
                </div>
                <div class="mt-1.5 flex items-center justify-between px-0.5">
                  <span class="text-[7px] text-slate-400 dark:text-slate-500">14 j enregistrés</span>
                  <span class="text-[8px] font-semibold text-slate-600 dark:text-slate-300">9 100 €</span>
                </div>
              </div>
              <div class="border-t border-slate-100 px-4 py-2.5 dark:border-slate-700">
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Feuille de temps</p>
              </div>
            </div>

            <!-- ── Mockup 3 : Facturation ──────────────────── -->
            <div class="overflow-hidden rounded-xl border border-white bg-white shadow-md ring-1 ring-indigo-100/80 dark:border-slate-700 dark:bg-slate-800 dark:ring-slate-700">
              <div class="relative aspect-video w-full overflow-hidden bg-slate-50 p-3 dark:bg-slate-900/80">
                <div class="mb-2 flex items-center gap-1">
                  <div class="size-1.5 rounded-full bg-red-400" /><div class="size-1.5 rounded-full bg-yellow-400" /><div class="size-1.5 rounded-full bg-green-400" />
                  <span class="ml-1.5 text-[9px] font-medium text-slate-500 dark:text-slate-400">Facturation · Acme Corp</span>
                </div>
                <div class="mb-1.5 flex items-start justify-between">
                  <div>
                    <p class="text-[8px] font-semibold text-slate-700 dark:text-slate-200">Projet Alpha</p>
                    <p class="text-[6.5px] text-slate-400">650 €/j · max 8 000 €/mois</p>
                  </div>
                  <div class="rounded border border-primary-200 bg-primary-50 px-1.5 py-0.5 text-[7px] font-medium text-primary-600 dark:border-primary-500/30 dark:bg-primary-500/10 dark:text-primary-400">
                    + Facture
                  </div>
                </div>
                <div class="overflow-hidden rounded border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                  <div class="grid border-b border-slate-100 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/80" style="grid-template-columns: 2fr 1.8fr 1.8fr 1.4fr">
                    <div class="px-1.5 py-0.5 text-[6px] font-medium uppercase tracking-wide text-slate-400">Mois</div>
                    <div class="px-1.5 py-0.5 text-right text-[6px] font-medium uppercase tracking-wide text-slate-400">Travaillé</div>
                    <div class="px-1.5 py-0.5 text-right text-[6px] font-medium uppercase tracking-wide text-slate-400">Facturé</div>
                    <div class="px-1.5 py-0.5 text-right text-[6px] font-medium uppercase tracking-wide text-slate-400">Solde</div>
                  </div>
                  <div class="grid items-center border-b border-slate-100 dark:border-slate-700" style="grid-template-columns: 2fr 1.8fr 1.8fr 1.4fr">
                    <div class="px-1.5 py-1 text-[7px] font-medium text-slate-600 dark:text-slate-300">Juin 2025</div>
                    <div class="px-1.5 py-1 text-right text-[7px] tabular-nums text-slate-400">5 200 €</div>
                    <div class="px-1.5 py-1 text-right text-[7px] tabular-nums text-slate-300 dark:text-slate-600">—</div>
                    <div class="px-1.5 py-1 text-right text-[7px] tabular-nums font-medium text-amber-500">-5 200 €</div>
                  </div>
                  <div class="grid items-center border-b border-slate-100 dark:border-slate-700" style="grid-template-columns: 2fr 1.8fr 1.8fr 1.4fr">
                    <div class="px-1.5 py-1 text-[7px] font-medium text-slate-600 dark:text-slate-300">Mai 2025</div>
                    <div class="px-1.5 py-1 text-right text-[7px] tabular-nums text-slate-400">7 800 €</div>
                    <div class="px-1.5 py-1 text-right text-[7px] tabular-nums font-medium text-emerald-500">7 800 €</div>
                    <div class="px-1.5 py-1 text-right text-[7px] tabular-nums text-emerald-500">0 €</div>
                  </div>
                  <div class="grid items-center border-b border-slate-100 dark:border-slate-700" style="grid-template-columns: 2fr 1.8fr 1.8fr 1.4fr">
                    <div class="px-1.5 py-1 text-[7px] font-medium text-slate-600 dark:text-slate-300">Avr 2025</div>
                    <div class="px-1.5 py-1 text-right text-[7px] tabular-nums text-slate-400">6 500 €</div>
                    <div class="px-1.5 py-1 text-right text-[7px] tabular-nums font-medium text-emerald-500">6 500 €</div>
                    <div class="px-1.5 py-1 text-right text-[7px] tabular-nums text-emerald-500">0 €</div>
                  </div>
                  <div class="grid items-center border-t-2 border-slate-200 bg-slate-50/80 dark:border-slate-700 dark:bg-slate-800/50" style="grid-template-columns: 2fr 1.8fr 1.8fr 1.4fr">
                    <div class="px-1.5 py-1 text-[7px] font-semibold text-slate-600 dark:text-slate-300">Total</div>
                    <div class="px-1.5 py-1 text-right text-[7px] tabular-nums font-medium text-slate-500">19 500 €</div>
                    <div class="px-1.5 py-1 text-right text-[7px] tabular-nums font-semibold text-emerald-500">14 300 €</div>
                    <div class="px-1.5 py-1 text-right text-[7px] tabular-nums font-semibold text-amber-500">-5 200 €</div>
                  </div>
                </div>
                <div class="mt-1.5 flex gap-1.5">
                  <div class="flex-1 rounded border border-slate-200 bg-white px-1.5 py-1 dark:border-slate-700 dark:bg-slate-800">
                    <p class="text-[6px] text-slate-400">À facturer</p>
                    <p class="text-[9px] font-semibold text-amber-500">5 200 €</p>
                  </div>
                  <div class="flex-1 rounded border border-slate-200 bg-white px-1.5 py-1 dark:border-slate-700 dark:bg-slate-800">
                    <p class="text-[6px] text-slate-400">Total facturé</p>
                    <p class="text-[9px] font-semibold text-emerald-500">14 300 €</p>
                  </div>
                  <div class="flex-1 rounded border border-slate-200 bg-white px-1.5 py-1 dark:border-slate-700 dark:bg-slate-800">
                    <p class="text-[6px] text-slate-400">Reste budget</p>
                    <p class="text-[9px] font-semibold text-slate-600 dark:text-slate-300">2 800 €</p>
                  </div>
                </div>
              </div>
              <div class="border-t border-slate-100 px-4 py-2.5 dark:border-slate-700">
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Facturation</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══════════════════════════════════════════
           EN LIGNE vs AUTO-HÉBERGÉ
      ═══════════════════════════════════════════ -->
      <section class="relative overflow-hidden px-6 py-20">
        <!-- Fond dégradé léger -->
        <div class="pointer-events-none absolute inset-0 bg-linear-to-br from-primary-50/60 via-white to-indigo-50/40 dark:from-primary-500/3 dark:via-slate-950 dark:to-indigo-500/3" />
        <!-- Pattern points discret -->
        <div
          class="pointer-events-none absolute inset-0 opacity-40 dark:opacity-20"
          style="background-image: radial-gradient(circle, rgba(148,163,184,0.3) 1px, transparent 1px);
                 background-size: 32px 32px;"
        />

        <div class="relative mx-auto max-w-7xl">
          <div class="mb-10">
            <div class="mb-3 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-primary-500 dark:text-primary-400">
              <div class="h-px w-6 bg-primary-400" />
              Déploiement
            </div>
            <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
              Comment voulez-vous l'utiliser ?
            </h2>
          </div>

          <div class="grid gap-6 lg:grid-cols-2">
            <!-- Utiliser en ligne -->
            <div class="relative overflow-hidden rounded-2xl border border-primary-200 bg-gradient-to-br from-primary-50 to-rose-50/50 p-8 dark:border-primary-500/20 dark:from-primary-500/8 dark:to-rose-500/5">
              <div class="pointer-events-none absolute -right-12 -top-12 size-56 rounded-full bg-primary-300/20 blur-3xl dark:bg-primary-500/10" />
              <div class="pointer-events-none absolute -bottom-8 left-8 size-32 rounded-full bg-rose-300/15 blur-2xl dark:bg-rose-500/8" />

              <div class="relative">
                <div class="mb-4 inline-flex items-center gap-1.5 rounded-full border border-primary-200 bg-white px-3 py-1 text-xs font-medium text-primary-600 dark:border-primary-500/30 dark:bg-primary-500/10 dark:text-primary-400">
                  <UIcon name="i-lucide-cloud" class="size-3" />
                  Version hébergée
                </div>

                <h3 class="mb-2 text-xl font-bold tracking-tight">Utilisez Takt en ligne</h3>
                <p class="mb-6 text-slate-600 dark:text-slate-400">
                  Accédez directement à cette instance. Connexion via votre compte GitHub,
                  aucune installation requise.
                </p>

                <ul class="mb-8 space-y-2">
                  <li
                    v-for="item in ['Connexion sécurisée via GitHub OAuth', 'Accès immédiat, sans configuration', 'Interface complète avec toutes les fonctionnalités']"
                    :key="item"
                    class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-400"
                  >
                    <UIcon name="i-lucide-check" class="mt-0.5 size-4 shrink-0 text-primary-500" />
                    {{ item }}
                  </li>
                </ul>

                <div class="flex flex-wrap items-center gap-4">
                  <UButton :href="login()" label="S'inscrire" size="md" icon="i-lucide-arrow-right" trailing />
                  <UButton :href="demo()" label="Essayer avec compte de démo" size="md" variant="link" color="neutral" />
                </div>
              </div>
            </div>

            <!-- Auto-héberger -->
            <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-linear-to-br from-slate-50 to-slate-100/50 p-8 dark:border-slate-700 dark:from-slate-900 dark:to-slate-800/50">
              <div class="pointer-events-none absolute -right-12 -top-12 size-56 rounded-full bg-indigo-300/10 blur-3xl dark:bg-indigo-500/8" />

              <div class="relative">
                <div class="mb-4 inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-400">
                  <UIcon name="i-simple-icons:github" class="size-3" />
                  Open Source · MIT
                </div>

                <h3 class="mb-2 text-xl font-bold tracking-tight">Hébergez-le vous-même</h3>
                <p class="mb-5 text-slate-600 dark:text-slate-400">
                  Code source entièrement ouvert. Déployez sur votre propre infrastructure,
                  vos données restent chez vous.
                </p>

                <div class="mb-6 grid grid-cols-2 gap-2">
                  <div
                    v-for="item in stack"
                    :key="item.label"
                    class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs dark:border-slate-600 dark:bg-slate-800"
                    :class="item.class"
                  >
                    <UIcon :name="item.icon" class="size-4 shrink-0" :class="[item.color]" />
                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ item.label }}</span>
                  </div>
                </div>

                <UButton
                  as="a"
                  href="https://github.com/mathieutu/takt"
                  target="_blank"
                  rel="noopener noreferrer"
                  label="Voir sur GitHub"
                  size="md"
                  variant="outline"
                  color="neutral"
                  icon="i-simple-icons:github"
                />
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ═══════════════════════════════════════════
           AUTEUR — bandeau teinté
      ═══════════════════════════════════════════ -->
      <section class="relative overflow-hidden border-t border-slate-100 px-6 py-16 dark:border-slate-800">
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-slate-50 to-white dark:from-slate-900 dark:to-slate-950" />
        <div class="pointer-events-none absolute left-0 top-0 h-px w-64 bg-gradient-to-r from-transparent via-primary-400/30 to-transparent" />

        <div class="relative mx-auto max-w-7xl">
          <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:gap-8">
            <div class="flex size-14 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary-100 to-rose-100 text-base font-bold tracking-tight text-primary-500 dark:from-primary-500/15 dark:to-rose-500/10 dark:text-primary-400">
              MT
            </div>
            <div>
              <div class="mb-1 flex flex-wrap items-center gap-3">
                <h3 class="text-base font-semibold">Mathieu TUDISCO</h3>
                <a
                  href="https://github.com/mathieutu"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="inline-flex items-center gap-1.5 text-xs text-slate-400 transition-colors hover:text-slate-600 dark:hover:text-slate-300"
                >
                  <UIcon name="i-simple-icons:github" class="size-3" />
                  @mathieutu
                </a>
              </div>
              <p class="max-w-2xl text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                Takt est un projet personnel open source, né d'un besoin quotidien de suivi du temps et de facturation
                en freelance. Développé avec des outils modernes, il reste volontairement simple, sans bloatware,
                et entièrement auto-hébergeable.
              </p>
            </div>
          </div>
        </div>
      </section>

      <!-- Footer -->
      <footer class="border-t border-slate-100 bg-slate-50 px-6 py-6 dark:border-slate-800 dark:bg-slate-900">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-center gap-2">
            <TaktLogo class="h-5 w-5" />
            <span class="text-xs text-slate-400">© 2025 Mathieu TUDISCO</span>
          </div>
          <div class="flex items-center gap-5">
            <a
              href="https://github.com/mathieutu/takt"
              target="_blank"
              rel="noopener noreferrer"
              class="text-xs text-slate-400 transition-colors hover:text-slate-600 dark:hover:text-slate-300"
            >
              GitHub
            </a>
            <Link href="/login" class="text-xs text-slate-400 transition-colors hover:text-slate-600 dark:hover:text-slate-300">
              Se connecter
            </Link>
          </div>
        </div>
      </footer>
    </div>
  </UApp>
</template>
