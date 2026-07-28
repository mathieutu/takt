<script setup lang="ts">
import type { DropdownMenuItem } from '@nuxt/ui'
import type { NavigationMenuItem } from '@nuxt/ui/components/NavigationMenu.vue.d.ts'
import { router, usePage } from '@inertiajs/vue3'
import { computed, onUnmounted, ref } from 'vue'
import TaktLogo from '@/components/TaktLogo.vue'
import { useCommandPalette } from '@/composables/useCommandPalette'
import { dashboard, login, logout, profile, timesheet } from '@/wayfinder/routes'
import projects from '@/wayfinder/routes/projects'
import { index as sharesIndex } from '@/wayfinder/routes/shares'

const { open } = useCommandPalette()

const page = usePage()
const user = computed(() => page.props.auth?.user)

const mobileMenuOpen = ref(false)
const unsubscribe = router.on('navigate', () => {
  mobileMenuOpen.value = false
})
onUnmounted(unsubscribe)

const navItems = computed<NavigationMenuItem[]>(() => [
  { label: 'Tableau de bord', icon: 'i-lucide-layout-dashboard', to: user.value ? dashboard() : login(), exact: true, active: user.value && page.url.split('?')[0] === dashboard().url, prefetch: true },
  { label: 'Activité', icon: 'i-lucide-calendar-days', to: timesheet(), active: page.url.startsWith(timesheet().url), prefetch: true },
  { label: 'Projets', icon: 'i-lucide-folder-kanban', to: projects.index(), active: page.url.startsWith(projects.index().url), prefetch: true },
])

const accountMenuItems = computed(() => [
  { label: 'Partages', icon: 'i-lucide-share-2', to: sharesIndex(), active: page.url.startsWith(sharesIndex().url), prefetch: true },
  { label: 'Paramètres', icon: 'i-lucide-settings', to: profile(), active: page.url.startsWith(profile().url), prefetch: true },
  { label: 'Se déconnecter', icon: 'i-lucide-log-out', to: logout() },
])

const userMenuItems = computed<DropdownMenuItem[][]>(() => [accountMenuItems.value])

const mobileMenuItems = computed<NavigationMenuItem[]>(() => [
  ...navItems.value,
  ...accountMenuItems.value,
])
</script>

<template>
  <UHeader v-model:open="mobileMenuOpen" :ui="{ toggle: 'hidden' }">
    <template #title>
      <div class="flex items-center gap-2">
        <TaktLogo class="h-7 w-7" />
        <span class="text-sm font-semibold">Takt</span>
      </div>
    </template>

    <UNavigationMenu :items="navItems" />

    <template #right>
      <UTooltip v-if="user" text="Rechercher" :kbds="['meta', 'K']">
        <UButton
          icon="i-lucide-search"
          color="neutral"
          variant="ghost"
          aria-label="Rechercher"
          @click="open()"
        />
      </UTooltip>
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
      <UButton
        v-else
        :href="login()"
        :to="login()"
        variant="outline"
        color="neutral"
        icon="i-lucide-log-in"
        label="Se connecter"
        trailing
        size="sm"
      />
    </template>

    <template v-if="user" #body>
      <UNavigationMenu :items="mobileMenuItems" orientation="vertical" class="-mx-2.5" />
    </template>
  </UHeader>
</template>
