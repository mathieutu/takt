<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import TaktLogo from '@/components/TaktLogo.vue'
import { useFlash } from '@/composables/useFlash'
import { demo } from '@/wayfinder/routes'
import { disabled, redirect } from '@/wayfinder/routes/login'

defineOptions({ layout: () => false })

defineProps<{
  users: Array<{ id: string, name: string, email: string }>,
}>()

useFlash()
</script>

<template>
  <UApp>
    <div class="relative h-screen overflow-hidden bg-slate-50 dark:bg-slate-950">
      <!-- Photo de fond (droite, desktop uniquement) -->
      <img
        src="https://picsum.photos/1920/1080"
        alt=""
        class="absolute inset-0 hidden h-full w-full object-cover lg:block"
        loading="eager"
      />

      <!-- Panneau gauche diagonal (desktop) — s'adapte au light/dark -->
      <div
        class="absolute inset-y-0 left-0 hidden w-full bg-white lg:block dark:bg-slate-950"
        style="clip-path: polygon(0 0, 50% 0, 36% 100%, 0 100%)"
      >
        <!-- Motif grille -->
        <div
          class="pointer-events-none absolute inset-0 opacity-60 dark:opacity-100"
          style="background-image: linear-gradient(rgba(100,116,139,0.08) 1px, transparent 1px),
                                   linear-gradient(90deg, rgba(100,116,139,0.08) 1px, transparent 1px);
                 background-size: 32px 32px;"
        />

        <!-- Glow rose (plus discret en light) -->
        <div class="pointer-events-none absolute left-1/3 top-1/2 size-[500px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary-500/8 blur-3xl dark:bg-primary-500/12" />

        <!-- Contenu éditorial -->
        <div class="absolute inset-0 flex flex-col justify-center px-12 xl:px-16">
          <div class="mb-10 flex items-center gap-4">
            <TaktLogo class="h-12 w-12" />
            <span class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Takt</span>
          </div>

          <h2 class="mb-4 text-2xl font-bold leading-snug tracking-tight text-slate-900 dark:text-white xl:text-3xl">
            Gérez votre temps.<br />
            <span class="text-primary-500 dark:text-primary-400">Suivez vos revenus.</span>
          </h2>

          <p class="max-w-[260px] text-sm leading-relaxed text-slate-500 dark:text-slate-400">
            Suivi du temps, projets, clients et facturation — tout ce dont un freelance a besoin, sans le superflu.
          </p>

          <div class="mt-8 flex flex-col gap-2.5">
            <div
              v-for="item in ['Tableau de bord avec KPIs et projections', 'Feuille de temps mensuelle par projet', 'Facturation et partage client']"
              :key="item"
              class="flex items-center gap-2.5 text-xs text-slate-500 dark:text-slate-500"
            >
              <div class="size-1.5 shrink-0 rounded-full bg-primary-500/60" />
              {{ item }}
            </div>
          </div>
        </div>

        <!-- Lien retour accueil -->
        <div class="absolute bottom-6 left-8">
          <Link
            href="/"
            class="flex items-center gap-1.5 text-xs text-slate-400 transition-colors hover:text-slate-700 dark:text-slate-600 dark:hover:text-slate-300"
          >
            <UIcon name="i-lucide-arrow-left" class="size-3" />
            Retour à l'accueil
          </Link>
        </div>
      </div>

      <!-- Carte login : centrée, superposée à la diagonale -->
      <div class="flex h-full items-center justify-center px-4">
        <div class="relative z-10 w-full max-w-sm">
          <!-- Lien mobile retour accueil -->
          <div class="mb-5 lg:hidden">
            <Link
              href="/"
              class="flex items-center gap-1.5 text-xs text-slate-400 transition-colors hover:text-slate-700 dark:hover:text-slate-300"
            >
              <UIcon name="i-lucide-arrow-left" class="size-3" />
              Retour à l'accueil
            </Link>
          </div>

          <div class="overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-slate-950 dark:ring-white/10">
            <div class="px-8 py-6">
              <template v-if="users.length > 0">
                <p class="mb-3 text-xs font-medium uppercase tracking-wide text-slate-400">
                  Connexion rapide (dev)
                </p>
                <div class="flex flex-col gap-2">
                  <Link
                    v-for="user in users"
                    :key="user.id"
                    :href="disabled()"
                    :data="{ user_id: user.id }"
                    class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm transition-colors hover:bg-slate-100 dark:border-slate-800 dark:bg-slate-900 dark:hover:bg-slate-800"
                  >
                    <span class="flex flex-col items-start">
                      <span class="font-medium">{{ user.name }}</span>
                      <span class="text-xs text-slate-400">{{ user.email }}</span>
                    </span>
                  </Link>
                </div>
              </template>

              <UButton
                v-else
                :href="redirect().url"
                external
                label="Se connecter avec GitHub"
                icon="i-simple-icons:github"
                block
                size="xl"
              />

              <div class="mt-4 text-center">
                <Link
                  :href="demo()"
                  class="text-xs text-slate-400 transition-colors hover:text-slate-600 dark:hover:text-slate-300"
                >
                  Essayer avec le compte de démonstration
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </UApp>
</template>
