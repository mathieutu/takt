<script setup lang="ts">
import type { DropdownMenuItem } from '@nuxt/ui/components/DropdownMenu.d.vue.ts'
import { Link, router } from '@inertiajs/vue3'
import { useDebounceFn } from '@vueuse/core'
import { ref } from 'vue'

import { useConfirm } from '@/composables/useConfirm'
import { formatDate } from '@/utils/date.ts'
import { formatCurrency } from '@/utils/number.ts'
import clientRoutes from '@/wayfinder/routes/clients'
import { show as clientBillingShow } from '@/wayfinder/routes/clients/billing'
import { destroy as destroyClientShare, store as shareClientRoute } from '@/wayfinder/routes/clients/share'
import projectsRoutes from '@/wayfinder/routes/projects'

type Client = {
  id: string,
  name: string,
  daily_rate: number,
  deleted_at: string | null,
  created_at: string,
  share_url: string | null,
}

type Project = {
  id: string,
  name: string,
  description: string,
  daily_rate: number,
  max_month_budget: number | null,
  max_total_budget: number | null,
  client: { id: string, name: string },
  created_at: string,
  deleted_at: string | null,
}

const props = withDefaults(defineProps<{
  projects: Project[],
  clients: Client[],
  search?: string,
  client_id?: string,
  with_trashed?: boolean,
  sort?: string,
  has_trashed?: boolean,
  has_active?: boolean,
}>(), {})

const sortOptions = [
  { label: 'Date (newest)', value: 'date_desc' },
  { label: 'Date (oldest)', value: 'date_asc' },
  { label: 'Rate (high)', value: 'rate_desc' },
  { label: 'Rate (low)', value: 'rate_asc' },
]

const shareOpen = ref(false)
const sharingItem = ref<Client | null>(null)
const copied = ref(false)

const confirm = useConfirm()

const deleteProject = (project: Project) => confirm({
  title: project.deleted_at ? `Delete "${project.name}"?` : `Archive "${project.name}"?`,
  description: project.deleted_at ? 'All data will be lost permanently.' : 'You will be able to restore it later',
  onConfirm: () => router.visit(projectsRoutes.destroy(project), { preserveScroll: true }),
})

const projectMenuItems = (project: Project): DropdownMenuItem[][] => [
  [
    { label: 'Edit', icon: 'i-lucide-pencil', href: projectsRoutes.edit(project, { mergeQuery: {} }), only: ['modal'] },
    { label: 'Duplicate', icon: 'i-lucide-copy', href: projectsRoutes.duplicate(project) },
  ],
  project.deleted_at ? [
    { label: 'Restore', icon: 'i-lucide-rotate-ccw', href: projectsRoutes.restore(project) },
    {
      label: 'Delete permanently',
      icon: 'i-lucide-trash-2',
      color: 'error' as const,
      onSelect: () => deleteProject(project),
    },
  ] : [{ label: 'Archive', icon: 'i-lucide-archive', color: 'error' as const, onSelect: () => deleteProject(project) }],
]

const deleteClient = (client: Client) => confirm({
  title: client.deleted_at ? `Delete "${client.name}"?` : `Archive "${client.name}"?`,
  description: client.deleted_at
    ? 'All data will be lost permanently.'
    : 'This will also archive all its projects. You will be able to restore them later.',
  onConfirm: () => router.visit(clientRoutes.destroy(client), { preserveScroll: true }),
})

const openShare = (client: Client) => {
  sharingItem.value = client
  copied.value = false
  shareOpen.value = true

  if (!client.share_url) {
    router.visit(shareClientRoute(client.id), {
      preserveState: true,
      preserveScroll: true,
      only: ['clients'],
      onSuccess: () => {
        sharingItem.value = props.clients.find(c => c.id === client.id) ?? sharingItem.value
      },
    })
  }
}

const copyShareUrl = () => {
  if (!sharingItem.value?.share_url) return
  navigator.clipboard.writeText(sharingItem.value.share_url)
  copied.value = true
  setTimeout(() => {
    copied.value = false
  }, 2000)
}

const revokeShare = () => {
  if (!sharingItem.value) return

  confirm({
    title: `Revoke share link for "${sharingItem.value.name}"?`,
    description: 'Anyone with the link will immediately lose access.',
    onConfirm: () => {
      router.visit(destroyClientShare(sharingItem.value!.id), {
        preserveScroll: true,
        only: ['clients'],
        onSuccess: () => {
          shareOpen.value = false
          sharingItem.value = null
        },
      })
    },
  })
}

const clientMenuItems = (client: Client): DropdownMenuItem[][] => {
  if (client.deleted_at) {
    return [
      [{ label: 'Restore', icon: 'i-lucide-rotate-ccw', onSelect: () => router.visit(clientRoutes.restore(client)) }],
      [{ label: 'Delete permanently', icon: 'i-lucide-trash-2', color: 'error' as const, onSelect: () => deleteClient(client) }],
    ]
  }

  return [
    [
      { label: 'Share', icon: 'i-lucide-share-2', onSelect: () => openShare(client) },
      { label: 'Edit', icon: 'i-lucide-pencil', href: clientRoutes.edit(client, { mergeQuery: {} }) },
    ],
    [{ label: 'Archive', icon: 'i-lucide-archive', color: 'error' as const, onSelect: () => deleteClient(client) }],
  ]
}

const onSearch = useDebounceFn((value: string) => {
  router.visit(projectsRoutes.index({ mergeQuery: { search: value || null } }), { preserveState: true, replace: true })
}, 300)
</script>

<template>
  <div class="flex flex-col bg-default">
    <div class="flex-1 px-6 py-8">
      <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-lg font-semibold">Projects</h1>
            <p class="text-sm text-muted">
              {{ projects.length }} project{{ projects.length !== 1 ? 's' : '' }} in
              <Link
                v-if="client_id"
                class="text-primary hover:underline focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary rounded"
                :href="projectsRoutes.index({ mergeQuery: { client_id: null } })"
              >
                {{ clients.find(c => c.id === client_id)?.name }}
                ×
              </Link>
              <template v-else>
                {{ clients.length }} client{{ clients.length !== 1 ? 's' : '' }}
              </template>
            </p>
          </div>
          <UButton
            :href="projectsRoutes.create({ mergeQuery: {} })"
            :only="['modal']"
            label="New project"
            icon="i-lucide-plus"
          />
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <UInput
            :modelValue="search"
            type="text"
            placeholder="Search for a project or client..."
            icon="i-lucide-search"
            class="w-72"
            :ui="{ trailing: 'pe-1' }"
            @update:modelValue="onSearch"
          >
            <template v-if="search?.length" #trailing>
              <UButton
                color="neutral"
                variant="link"
                size="sm"
                icon="i-lucide-x"
                aria-label="Clear input"
                preserveState
                replace
                :href="projectsRoutes.index({ mergeQuery: { search: null } })"
              />
            </template>
          </UInput>
          <USelect
            :modelValue="sort"
            :items="sortOptions"
            class="w-44"
            @update:modelValue="router.visit(
              projectsRoutes.index({ mergeQuery: { sort: $event } }),
              { preserveState: true, replace: true },
            )"
          />
          <template v-if="has_trashed">
            <UButton
              v-if="!with_trashed"
              label="Show archived"
              icon="i-lucide-archive"
              color="neutral"
              variant="outline"
              size="sm"
              :href="projectsRoutes.index({ mergeQuery: { with_trashed: true } })"
            />
            <UButton
              v-else-if="has_active"
              label="Hide archived"
              icon="i-lucide-eye-off"
              color="error"
              variant="outline"
              size="sm"
              :href="projectsRoutes.index({ mergeQuery: { with_trashed: null } })"
            />
          </template>
        </div>

        <p v-if="projects.length === 0" class="text-sm text-muted">
          <template v-if="search || client_id || with_trashed">No projects match your filters.</template>
          <template v-else>No projects yet.</template>
        </p>

        <div v-if="projects.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="project in projects"
            :key="project.id"
            class="rounded-lg border border-default bg-elevated p-5 flex flex-col h-full justify-between transition-opacity"
            :class="project.deleted_at ? 'opacity-60' : ''"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold">
                  {{ project.name }}
                </p>
                <Link
                  class="truncate text-xs text-muted hover:text-primary transition-colors"
                  :href="projectsRoutes.index({ mergeQuery: { client_id: project.client.id } })"
                >
                  {{ project.client.name }}
                </Link>
              </div>
              <div class="flex shrink-0 items-center gap-1">
                <UTooltip text="Billing">
                  <UButton
                    :href="clientBillingShow(project.client)"
                    icon="i-lucide-receipt-text"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                  />
                </UTooltip>
                <UTooltip text="Actions">
                  <UDropdownMenu :items="projectMenuItems(project)" class="shrink-0">
                    <UButton
                      icon="i-lucide-more-vertical"
                      color="neutral"
                      variant="ghost"
                      size="xs"
                    />
                  </UDropdownMenu>
                </UTooltip>
              </div>
            </div>

            <p v-if="project.description" class="mt-3 text-xs text-muted line-clamp-2">
              {{ project.description }}
            </p>

            <div class="mt-3 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span v-if="project.daily_rate" class="text-xs font-medium">{{ formatCurrency(project.daily_rate) }}/day</span>
                <span
                  v-if="project.deleted_at"
                  class="inline-flex items-center gap-1 rounded-full bg-error/10 px-2 py-0.5 text-xs text-error"
                >
                  <UIcon name="i-lucide-archive" class="h-3 w-3" />
                  Archived
                </span>
              </div>
              <span v-if="project.created_at" class="text-xs text-muted">{{ formatDate(project.created_at) }}</span>
            </div>
          </div>
        </div>

        <div v-if="clients.length > 0" class="space-y-1">
          <div class="mb-2">
            <p class="text-xs font-medium uppercase tracking-wide text-muted">Clients</p>
          </div>

          <div
            v-for="client in clients"
            :key="client.id"
            class="flex items-center gap-1 rounded-md px-2 py-1.5 transition-colors"
            :class="client_id === client.id ? 'bg-primary/10 ring-1 ring-primary/20' : 'hover:bg-elevated'"
          >
            <div class="flex flex-1 min-w-0 items-center gap-2 px-1">
              <span
                class="text-sm"
                :class="{ 'text-primary font-medium': client_id === client.id }"
              >{{ client.name }}</span>
              <span class="text-xs text-muted">{{ formatCurrency(client.daily_rate) }}/day</span>
              <span
                v-if="client.deleted_at"
                class="inline-flex items-center gap-1 rounded-full bg-error/10 px-2 py-0.5 text-xs text-error"
              >
                <UIcon name="i-lucide-archive" class="h-3 w-3" />
                Archived
              </span>
              <span
                v-else-if="client.share_url"
                class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs text-muted"
                title="Shared with others"
              >
                <UIcon name="i-lucide-users" class="h-3 w-3" />
                Shared
              </span>
            </div>
            <UTooltip text="Filter projects">
              <UButton
                icon="i-lucide-filter"
                color="neutral"
                variant="ghost"
                size="xs"
                :href="projectsRoutes.index({ mergeQuery: { client_id: client.id === client_id ? null : client.id } })"
                :class="client_id === client.id ? 'text-primary' : ''"
              />
            </UTooltip>
            <UTooltip text="Billing">
              <UButton
                :href="clientBillingShow(client)"
                icon="i-lucide-receipt-text"
                color="neutral"
                variant="ghost"
                size="xs"
              />
            </UTooltip>
            <UDropdownMenu :items="clientMenuItems(client)" class="shrink-0">
              <UButton
                icon="i-lucide-more-vertical"
                color="neutral"
                variant="ghost"
                size="xs"
              />
            </UDropdownMenu>
          </div>
        </div>
      </div>
    </div>

    <slot />

    <UModal v-model:open="shareOpen" title="Share client">
      <template #body>
        <div class="space-y-3">
          <p class="text-sm text-muted">
            Copy this link and send it to the person you want to share
            <span class="font-medium text-default">{{ sharingItem?.name }}</span> with.
          </p>
          <div class="flex gap-2">
            <UInput
              :modelValue="sharingItem?.share_url ?? 'Generating…'"
              readonly
              class="min-w-0 flex-1"
            />
          </div>
          <div v-if="sharingItem?.share_url" class="flex justify-end items-center border-t border-default pt-3">
            <UButton
              label="Revoke"
              icon="i-lucide-link-2-off"
              color="error"
              variant="ghost"
              size="sm"
              @click="revokeShare"
            />
            <UButton
              :label="copied ? 'Copied!' : 'Copy'"
              icon="i-lucide-copy"
              :disabled="!sharingItem?.share_url"
              variant="ghost"
              size="sm"
              @click="copyShareUrl"
            />
          </div>
        </div>
      </template>
    </UModal>
  </div>
</template>
