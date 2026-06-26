<script setup lang="ts">
import type { UrlMethodPair } from '@inertiajs/core'
import type { CommandPaletteGroup } from '@nuxt/ui'
import { router, useHttp } from '@inertiajs/vue3'
import { computed, watch } from 'vue'
import { type CommandPaletteData, useCommandPalette } from '@/composables/useCommandPalette'
import { today } from '@/utils/date.ts'
import { commandBar, dashboard, profile, timesheet } from '@/wayfinder/routes'
import { show as showBilling } from '@/wayfinder/routes/clients/billing'
import projects from '@/wayfinder/routes/projects'

const { isOpen, data, close } = useCommandPalette()

const http = useHttp<object, CommandPaletteData>()

watch(isOpen, async newValue => {
  if (newValue) {
    data.value = await http.submit(commandBar())
  }
})

const navigate = (url: UrlMethodPair) => () => {
  close()
  router.visit(url)
}

type Client = CommandPaletteData['clients'][number]
type Project = CommandPaletteData['projects'][number]

const groups = computed<CommandPaletteGroup[]>(() => {
  const months = Array.from({ length: 13 }, (_, i) => {
    const d = today.subtract({ months: i })
    const monthStr = `${d.year}-${String(d.month).padStart(2, '0')}`
    const label = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' })
      .format(new Date(d.year, d.month - 1, 1))
    return {
      label: label.charAt(0).toUpperCase() + label.slice(1),
      icon: 'i-lucide-calendar-days',
      onSelect: navigate(timesheet({ query: { month: monthStr } })),
    }
  })

  const clientProjects = data.value?.projects.reduce<Record<string, Project[]>>(
    (acc, p) => ({
      ...acc,
      [p.client.id]: [
        ...(acc[p.client.id] ?? []),
        p,
      ],
    }),
    {},
  )

  const clientsGroup: CommandPaletteGroup[] = data.value?.clients.length
    ? [{
        id: 'clients',
        label: 'Facturation',
        ignoreFilter: true,
        postFilter: (searchTerm, items) => {
          if (!searchTerm) return items
          const lower = searchTerm.toLowerCase()
          const clientIdsFromProjects = new Set(
            data.value!.projects
              .filter((p: Project) => p.name.toLowerCase().includes(lower))
              .map((p: Project) => p.client.id),
          )
          return items.filter(item => {
            const clientId = (item as { clientId: string }).clientId
            return item.label?.toLowerCase().includes(lower) || clientIdsFromProjects.has(clientId)
          })
        },
        items: data.value!.clients.map((client: Client) => ({
          clientId: client.id,
          label: client.name,
          suffix: clientProjects?.[client.id]?.map((p: Project) => p.name).join(', '),
          icon: 'i-lucide-receipt-text',
          onSelect: navigate(showBilling(client)),
        })),
      }]
    : []

  const projectsGroup: CommandPaletteGroup[] = data.value?.projects.length
    ? [{
        id: 'projects',
        label: 'Projets',
        items: data.value!.projects.map((project: Project) => ({
          label: project.name,
          suffix: project.client.name,
          icon: 'i-lucide-folder-kanban',
          onSelect: navigate(projects.edit(project)),
        })),
      }]
    : []

  return [
    {
      id: 'navigation',
      label: 'Navigation',
      items: [
        { label: 'Tableau de bord', icon: 'i-lucide-layout-dashboard', onSelect: navigate(dashboard()) },
        { label: 'Activité', icon: 'i-lucide-calendar-days', onSelect: navigate(timesheet()) },
        { label: 'Projets', icon: 'i-lucide-folder-kanban', onSelect: navigate(projects.index()) },
        { label: 'Paramètres', icon: 'i-lucide-settings', onSelect: navigate(profile()) },
      ],
    },
    { id: 'months', label: 'Activité', items: months },
    ...clientsGroup,
    ...projectsGroup,
  ]
})
</script>

<template>
  <UModal
    v-model:open="isOpen"
    :ui="{ content: 'p-0 overflow-hidden sm:max-w-xl divide-y divide-default' }"
    :close="false"
  >
    <template #content>
      <UCommandPalette
        placeholder="Rechercher..."
        :groups="groups"
        :close="true"
        @close="close()"
      />
    </template>
  </UModal>
</template>
