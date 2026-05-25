<script setup lang="ts">
import type { DropdownMenuItem } from '@nuxt/ui/components/DropdownMenu.d.vue.ts'
import { Link, router, useHttp } from '@inertiajs/vue3'
import { useDebounceFn } from '@vueuse/core'
import { ref } from 'vue'
import { useConfirm } from '@/composables/useConfirm'
import { formatDate } from '@/utils/date.ts'
import { formatCurrency } from '@/utils/number.ts'
import clientRoutes from '@/wayfinder/routes/clients'
import projectsRoutes from '@/wayfinder/routes/projects'
import { destroy as destroyShare, store as shareRoute } from '@/wayfinder/routes/projects/share'

type Client = {
  id: string,
  name: string,
  daily_rate: number,
  deleted_at: string | null,
  created_at: string,
}

type Project = {
  id: string,
  name: string,
  description: string,
  daily_rate: number,
  max_budget: number | null,
  client: {
    id: string,
    name: string,
  },
  created_at: string,
  deleted_at: string | null,
  is_shared: boolean,
}

defineProps<{
  projects: Project[],
  clients: Client[],
  search?: string,
  client_id?: string,
  with_trashed?: boolean,
  sort?: string,
  has_trashed?: boolean,
  has_active?: boolean,
}>()

const sortOptions = [
  { label: 'Date (newest)', value: 'date_desc' },
  { label: 'Date (oldest)', value: 'date_asc' },
  { label: 'Rate (high)', value: 'rate_desc' },
  { label: 'Rate (low)', value: 'rate_asc' },
]

const shareProjectOpen = ref(false)
const sharingProject = ref<Project | null>(null)
const shareUrl = ref('')
const copied = ref(false)
const shareLoading = ref(false)

const confirm = useConfirm()

const deleteProject = (project: Project) => confirm({
  title: project.deleted_at ? `Delete "${project.name}"?` : `Archive "${project.name}"?`,
  description: project.deleted_at ? 'All data will be lost permanently.' : 'You will be able to restore it later',
  onConfirm: () => router.visit(projectsRoutes.destroy(project), { preserveScroll: true }),
})

const deleteClient = (client: Client) => confirm({
  title: client.deleted_at ? `Delete "${client.name}"?` : `Archive "${client.name}"?`,
  description: client.deleted_at
    ? 'All data will be lost permanently.'
    : 'This will also archive all its projects. You will be able to restore them later.',
  onConfirm: () => router.visit(clientRoutes.destroy(client), { preserveScroll: true }),
})
const duplicateProject = (project: Project) => router.visit(projectsRoutes.store(), { data: {
  client_id: project.client.id,
  name: `[copy] ${project.name}`,
  description: project.description,
  daily_rate: project.daily_rate,
  max_budget: project.max_budget,
} })

const projectMenuItems = (project: Project): DropdownMenuItem[][] => {
  return [[
    project.deleted_at
      ? { label: 'Restore', icon: 'i-lucide-rotate-ccw', href: projectsRoutes.restore(project) }
      : { label: 'Edit', icon: 'i-lucide-pencil', href: projectsRoutes.edit(project), only: ['modal'] },
    { label: 'Duplicate', icon: 'i-lucide-copy', href: projectsRoutes.duplicate(project) },
  ], [
    { label: project.deleted_at ? 'Delete permanently' : 'Archive', icon: 'i-lucide-trash-2', color: 'error', onSelect: () => deleteProject(project) },
  ]]
}

const onSearch = useDebounceFn((value: string) => {
  router.visit(projectsRoutes.index({ mergeQuery: { search: value || null } }), { preserveState: true, replace: true })
}, 300)

const http = useHttp()

function openShare(project: Project) {
  sharingProject.value = project
  shareUrl.value = ''
  copied.value = false
  shareLoading.value = true
  shareProjectOpen.value = true
  http.post(shareRoute(project.id).url, {
    onSuccess: (data: any) => {
      shareUrl.value = data.url
    },
    onError: () => {
      shareUrl.value = ''
    },
    onFinish: () => {
      shareLoading.value = false
    },
  })
}

function copyShareUrl() {
  navigator.clipboard.writeText(shareUrl.value)
  copied.value = true
  setTimeout(() => {
    copied.value = false
  }, 2000)
}

function revokeShare() {
  if (!sharingProject.value) return
  router.delete(destroyShare(sharingProject.value.id).url, {
    preserveScroll: true,
    onSuccess: () => {
      shareProjectOpen.value = false
      sharingProject.value = null
      shareUrl.value = ''
    },
  })
}
</script>

<template>
  <div class="flex min-h-screen flex-col bg-default">
    <main class="flex-1 px-6 py-8">
      <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-lg font-semibold">Projects</h1>
            <p class="text-sm text-muted">
              {{ projects.length }} project{{ projects.length !== 1 ? 's' : '' }}
              <template v-if="client_id">
                — <Link
                  class="text-primary hover:underline"
                  :href="projectsRoutes.index({ mergeQuery: { client_id: null } })"
                >
                  {{ clients.find(c => c.id === client_id)?.name }}
                  ×
                </Link>
              </template>
            </p>
          </div>
          <UButton
            :as="Link"
            :href="projectsRoutes.create()"
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
            class="rounded-lg border border-default bg-elevated p-5 transition-opacity flex flex-col h-full justify-between"
            :class="project.deleted_at ? 'opacity-60' : ''"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="flex min-w-0 flex-1 items-center gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold" :class="project.deleted_at ? 'line-through text-muted' : ''">{{ project.name }}</p>
                  <Link
                    class="truncate text-xs text-muted hover:text-primary transition-colors text-left"
                    :href="projectsRoutes.index({ mergeQuery: { client_id: project.client.id } })"
                  >
                    {{ project.client.name }}
                  </Link>
                </div>
              </div>
              <div class="flex shrink-0 items-center gap-1">
                <UTooltip v-if="!project.deleted_at" text="Share">
                  <UButton
                    icon="i-lucide-share-2"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    @click="openShare(project)"
                  />
                </UTooltip>
                <UDropdownMenu :items="projectMenuItems(project)" class="shrink-0">
                  <UButton
                    icon="i-lucide-more-vertical"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                  />
                </UDropdownMenu>
              </div>
            </div>

            <p v-if="project.description" class="mt-3 text-xs text-muted line-clamp-2">
              {{ project.description }}
            </p>

            <div class="mt-3 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="text-xs font-medium">{{ formatCurrency(project.daily_rate) }}/day</span>
                <span
                  v-if="project.deleted_at"
                  class="inline-flex items-center gap-1 rounded-full bg-error/10 px-2 py-0.5 text-xs text-error"
                >
                  <UIcon name="i-lucide-archive" class="h-3 w-3" />
                  Archived
                </span>
                <span
                  v-else-if="project.is_shared"
                  class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs text-muted"
                  title="This project is shared"
                >
                  <UIcon name="i-lucide-users" class="h-3 w-3" />
                  Shared
                </span>
              </div>
              <span v-if="project.created_at" class="text-xs text-muted">{{ formatDate(project.created_at) }}</span>
            </div>
          </div>
        </div>

        <div v-if="clients.length > 0" class="space-y-1">
          <div class="mb-2 flex items-center justify-between">
            <p class="text-xs font-medium uppercase tracking-wide text-muted">Clients</p>
          </div>
          <div
            v-for="client in clients"
            :key="client.id"
            class="flex items-center justify-between rounded-md px-3 py-2 transition-colors"
            :class="!client.deleted_at && client_id === client.id ? 'bg-primary/10 ring-1 ring-primary/20' : 'hover:bg-elevated'"
          >
            <Link
              class="flex flex-1 min-w-0 items-center gap-2"
              :class="client.deleted_at ? 'opacity-60 pointer-events-none' : 'cursor-pointer'"
              :href="projectsRoutes.index({ mergeQuery: { client_id: client.id === client_id ? null : client.id } })"
            >
              <span
                class="text-sm"
                :class="[
                  client.deleted_at ? 'line-through text-muted' : '',
                  !client.deleted_at && client_id === client.id ? 'text-primary font-medium' : '',
                ]"
              >{{ client.name }}</span>
              <span class="text-xs text-muted">{{ formatCurrency(client.daily_rate) }}/day</span>
              <span
                v-if="client.deleted_at"
                class="inline-flex items-center gap-1 rounded-full bg-error/10 px-2 py-0.5 text-xs text-error"
              >
                <UIcon name="i-lucide-archive" class="h-3 w-3" />
                Archived
              </span>
            </Link>
            <div class="flex items-center gap-1">
              <template v-if="client.deleted_at">
                <UTooltip text="Restore">
                  <UButton
                    icon="i-lucide-rotate-ccw"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    @click="router.visit(clientRoutes.restore(client))"
                  />
                </UTooltip>
              </template>
              <template v-else>
                <UTooltip text="Edit">
                  <UButton
                    :as="Link"
                    :href="clientRoutes.edit(client)"
                    icon="i-lucide-pencil"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                  />
                </UTooltip>
              </template>
              <UTooltip :text="client.deleted_at ? 'Delete permanently' : 'Archive'">
                <UButton
                  icon="i-lucide-trash-2"
                  color="error"
                  variant="ghost"
                  size="xs"
                  @click="deleteClient(client)"
                />
              </UTooltip>
            </div>
          </div>
        </div>
      </div>
    </main>

    <slot />

    <UModal v-model:open="shareProjectOpen" title="Share project">
      <template #body>
        <div class="space-y-3">
          <p class="text-sm text-muted">
            Copy this link and send it to the person you want to share
            <span class="font-medium text-default">{{ sharingProject?.name }}</span> with.
          </p>
          <div class="flex gap-2">
            <UInput
              :modelValue="shareLoading ? 'Loading…' : shareUrl"
              readonly
              class="min-w-0 flex-1"
            />
            <UButton
              :label="copied ? 'Copied!' : 'Copy'"
              :disabled="shareLoading || !shareUrl"
              @click="copyShareUrl"
            />
          </div>
          <div v-if="shareUrl" class="flex justify-end border-t border-default pt-3">
            <button
              type="button"
              class="text-xs text-muted underline-offset-2 hover:text-error hover:underline transition-colors"
              @click="revokeShare"
            >
              Disable this share link
            </button>
          </div>
        </div>
      </template>
      <template #footer="{ close }">
        <div class="flex justify-end gap-2">
          <UButton label="Close" color="neutral" variant="outline" @click="close" />
        </div>
      </template>
    </UModal>
  </div>
</template>
