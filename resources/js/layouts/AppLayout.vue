<script setup lang="ts">
import type { DropdownMenuItem } from '@nuxt/ui'
import type { NavigationMenuItem } from '@nuxt/ui/components/NavigationMenu.vue.d.ts'
import type { PageProps } from '../types'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import TaktLogo from '@/components/TaktLogo.vue'
import { dashboard, login, logout, profile, timesheet } from '@/wayfinder/routes'
import projects from '@/wayfinder/routes/projects'
import { useFlash } from '../composables/useFlash'

const page = usePage<PageProps>()
const user = computed(() => page.props.auth?.user)

useFlash()

const navItems: NavigationMenuItem[] = [
  { label: 'Dashboard', icon: 'i-lucide-layout-dashboard', to: dashboard(), exact: true },
  { label: 'Timesheet', icon: 'i-lucide-calendar-days', to: timesheet() },
  { label: 'Projects', icon: 'i-lucide-folder-kanban', to: projects.index() },
]

const userMenuItems = computed<DropdownMenuItem[][]>(() => [[
  { label: 'Settings', icon: 'i-lucide-settings', to: profile() },
  user.value
    ? { label: 'Log out', icon: 'i-lucide-log-out', to: logout() }
    : { label: 'Login', icon: 'i-lucide-log-in', to: login() },
]])

const mobileMenuItems = computed<NavigationMenuItem[]>(() => [
  ...navItems,
  { label: 'Settings', icon: 'i-lucide-settings', to: profile() },
  user.value
    ? { label: 'Log out', icon: 'i-lucide-log-out', to: logout() }
    : { label: 'Login', icon: 'i-lucide-log-in', to: login() },
])
</script>

<template>
  <UApp>
    <UHeader title="AssoFlow">
      <template #title>
        <div class="flex items-center gap-2">
          <TaktLogo class="h-7 w-7" />
          <span class="text-sm font-semibold">Takt</span>
        </div>
      </template>

      <UNavigationMenu :items="navItems" />

      <template #right>
        <template v-if="user">
          <UDropdownMenu :items="userMenuItems">
            <div class="flex items-center gap-2">
              <UAvatar
                :src="user.avatar ?? undefined"
                alt=""
                size="sm"
                class="cursor-pointer"
              />
              <span class="text-sm font-semibold">
                {{ user.name }}
              </span>
            </div>
          </UDropdownMenu>
        </template>
        <UButton v-else :to="login()" label="Login" color="neutral" variant="ghost" />
      </template>

      <template #body>
        <UNavigationMenu :items="mobileMenuItems" orientation="vertical" class="-mx-2.5" />
      </template>
    </UHeader>

    <main class="min-h-[calc(100vh-var(--ui-header-height)-4rem)]">
      <slot />
    </main>
    <UFooter>
      <template #left>
        <p class="text-muted text-xs">© {{ new Date().getFullYear() }} AssoFlow</p>
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
