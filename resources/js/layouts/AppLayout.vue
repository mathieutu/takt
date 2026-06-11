<script setup lang="ts">
import type { DropdownMenuItem } from '@nuxt/ui'
import type { NavigationMenuItem } from '@nuxt/ui/components/NavigationMenu.vue.d.ts'
import { router, usePage } from '@inertiajs/vue3'
import { computed, onUnmounted, ref } from 'vue'
import TaktLogo from '@/components/TaktLogo.vue'
import { formatDateTime } from '@/utils/date.ts'
import { dashboard, login, logout, profile, timesheet } from '@/wayfinder/routes'
import projects from '@/wayfinder/routes/projects'
import { useFlash } from '../composables/useFlash'

const page = usePage()
const user = computed(() => page.props.auth?.user)
const updatedAt = computed(() => page.props.updatedAt)

useFlash()

const mobileMenuOpen = ref(false)
const unsubscribe = router.on('navigate', () => {
  mobileMenuOpen.value = false
})
onUnmounted(unsubscribe)

const navItems = computed<NavigationMenuItem[]>(() => [
  { label: 'Tableau de bord', icon: 'i-lucide-layout-dashboard', to: dashboard(), exact: true, active: page.url === dashboard().url, prefetch: true },
  { label: 'Activité', icon: 'i-lucide-calendar-days', to: timesheet(), active: page.url.startsWith(timesheet().url), prefetch: true },
  { label: 'Projets', icon: 'i-lucide-folder-kanban', to: projects.index(), active: page.url.startsWith(projects.index().url), prefetch: true },
])

const userMenuItems = computed<DropdownMenuItem[][]>(() => [[
  { label: 'Paramètres', icon: 'i-lucide-settings', to: profile(), active: page.url.startsWith(profile().url), prefetch: true },
  user.value
    ? { label: 'Se déconnecter', icon: 'i-lucide-log-out', to: logout() }
    : { label: 'Connexion', icon: 'i-lucide-log-in', to: login() },
]])

const mobileMenuItems = computed<NavigationMenuItem[]>(() => [
  ...navItems.value,
  { label: 'Paramètres', icon: 'i-lucide-settings', to: profile(), active: page.url.startsWith(profile().url), prefetch: true },
  user.value
    ? { label: 'Se déconnecter', icon: 'i-lucide-log-out', to: logout() }
    : { label: 'Connexion', icon: 'i-lucide-log-in', to: login() },
])
</script>

<template>
  <UApp>
    <UHeader v-model:open="mobileMenuOpen" :ui="{ toggle: 'hidden' }">
      <template #title>
        <div class="flex items-center gap-2">
          <TaktLogo class="h-7 w-7" />
          <span class="text-sm font-semibold">Takt</span>
        </div>
      </template>

      <UNavigationMenu :items="navItems" />

      <template #right>
        <template v-if="user">
          <UButton
            class="lg:hidden"
            :avatar="user.avatar ? { src: user.avatar, alt: '', class: 'rounded-none squircle' } : undefined"
            :label="user.name"
            color="neutral"
            variant="ghost"
            @click="mobileMenuOpen = !mobileMenuOpen"
          />
          <div class="hidden lg:flex">
            <UDropdownMenu :items="userMenuItems">
              <UButton
                class="flex items-center gap-2"
                :avatar="user.avatar ? { src: user.avatar, alt: '', class: 'rounded-none squircle' } : undefined"
                :label="user.name"
                color="neutral"
                variant="ghost"
              />
            </UDropdownMenu>
          </div>
        </template>
        <UButton v-else :to="login()" label="Connexion" color="neutral" variant="ghost" />
      </template>

      <template #body>
        <UNavigationMenu :items="mobileMenuItems" orientation="vertical" class="-mx-2.5" />
      </template>
    </UHeader>

    <div v-if="page.props.auth?.isDemo" class="bg-primary-200 text-primary-800 px-4 py-4 text-center text-base">
      Compte de démonstration · Vous pouvez modifier les données, mais elles sont réinitialisée régulièrement.
    </div>

    <main class="min-h-[calc(100vh-var(--ui-header-height)-4rem)]">
      <slot />
    </main>
    <UFooter>
      <template #left>
        <p class="text-muted text-xs">© {{ new Date().getFullYear() }} Takt · Dernière mise à jour {{ formatDateTime(updatedAt) }}</p>
      </template>
      <template #right>
        <UButton
          icon="i-simple-icons:github"
          color="neutral"
          variant="ghost"
          to="https://github.com/mathieutu/cra"
          target="_blank"
          aria-label="GitHub"
          size="xs"
        />
        <p class="text-muted text-xs">
          Made with ♥︎ by
          <ULink to="https://mathieutu.dev" target="_blank" class="font-medium hover:text-primary">Mathieu TUDISCO</ULink>
        </p>
      </template>
    </UFooter>
  </UApp>
</template>

<style>
.squircle {
  mask-image: url("data:image/svg+xml,%3csvg width='200' height='200' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M100 0C20 0 0 20 0 100s20 100 100 100 100-20 100-100S180 0 100 0Z'/%3e%3c/svg%3e");
  mask-size: contain;
  mask-position: center;
  mask-repeat: no-repeat;
}
</style>
