<script setup lang="ts">
import type { NavigationMenuItem } from '@nuxt/ui/components/NavigationMenu.vue.d.ts'
import type { AuthUser } from '@/types'
import { usePage } from '@inertiajs/vue3'
import { watch } from 'vue'
import { dashboard, logout, profile, timesheet } from '@/wayfinder/routes'
import exports from '@/wayfinder/routes/exports'
import projects from '@/wayfinder/routes/projects'

defineProps<{
  user: AuthUser | null,
  open: boolean,
}>()

const emit = defineEmits<{
  'update:open': [value: boolean],
}>()

const page = usePage()

const navItems = [
  { label: 'Tableau de bord', icon: 'i-lucide-layout-dashboard', to: dashboard(), exact: true },
  { label: 'Saisie CRA', icon: 'i-lucide-calendar-days', to: timesheet() },
  { label: 'Projets', icon: 'i-lucide-folder-kanban', to: projects.index() },
  { label: 'Export', icon: 'i-lucide-download', to: exports.index() },
] satisfies NavigationMenuItem[]

const settingsItems = [
  { label: 'Paramètres', icon: 'i-lucide-settings', to: profile() },
  { label: 'Log out', icon: 'i-lucide-log-out', to: logout() },
] satisfies NavigationMenuItem[]

watch(() => page.url, () => emit('update:open', false))
</script>

<template>
  <!-- Desktop sidebar (icônes uniquement + tooltips) -->
  <aside
    class="hidden md:flex fixed left-0 top-14 z-40 h-[calc(100vh-3.5rem)] w-14 flex-col border-r border-default bg-elevated py-2"
  >
    <UNavigationMenu
      orientation="vertical"
      collapsed
      :tooltip="true"
      :items="navItems"
      :ui="{ link: 'justify-center' }"
      class="flex-1 w-full"
    />
    <UNavigationMenu
      orientation="vertical"
      collapsed
      :tooltip="true"
      :items="settingsItems"
      :ui="{ link: 'justify-center' }"
      class="w-full"
    />
  </aside>

  <!-- Mobile drawer -->
  <USlideover
    :open="open"
    side="left"
    :ui="{ content: 'max-w-64', body: 'p-0 sm:p-0 flex flex-col' }"
    @update:open="emit('update:open', $event)"
  >
    <template #header="{ close }">
      <div class="flex items-center justify-between w-full">
        <div class="flex items-center gap-2">
          <img alt="" src="/public/images/logo.png" class="h-7 w-7 rounded-md object-cover" />
          <span class="text-sm font-semibold">AssoFlow</span>
        </div>
        <UButton icon="i-lucide-x" color="neutral" variant="ghost" size="sm" @click="close" />
      </div>
    </template>
    <template #body>
      <UNavigationMenu
        orientation="vertical"
        :items="navItems"
        class="flex-1 py-2"
      />
      <div class="mt-auto border-t border-default py-2">
        <UNavigationMenu
          orientation="vertical"
          :items="settingsItems"
        />
        <UButton
          label="Se déconnecter"
          icon="i-lucide-log-out"
          color="error"
          variant="ghost"
          class="w-full"
          @click="logout"
        />
      </div>
    </template>
  </USlideover>
</template>
