<script setup lang="ts">
import type { DropdownMenuItem } from '@nuxt/ui/components/DropdownMenu.d.vue.ts'
import { Link, router, useHttp } from '@inertiajs/vue3'
import { useDebounceFn } from '@vueuse/core'
import { computed, ref } from 'vue'

import ProjectCard from '@/components/ProjectCard.vue'
import { useConfirm } from '@/composables/useConfirm'
import { formatCurrency } from '@/utils/number.ts'
import { show as clientBillingShow } from '@/wayfinder/routes/clients/billing'
import clientRoutes from '@/wayfinder/routes/clients'
import { destroy as destroyClientShare, store as shareClientRoute } from '@/wayfinder/routes/clients/share'
import { destroy as destroyReceivedShare } from '@/wayfinder/routes/received-shares'
import projectsRoutes from '@/wayfinder/routes/projects'
import { destroy as destroyProjectShare, store as shareProjectRoute } from '@/wayfinder/routes/projects/share'
import sharesRoutes from '@/wayfinder/routes/shares'

type Client = {
  id: string
  name: string
  daily_rate: number
  deleted_at: string | null
  created_at: string
  is_shared: boolean
}

type Project = {
  id: string
  name: string
  description: string
  daily_rate: number
  max_month_budget: number | null
  client: { id: string; name: string }
  created_at: string
  deleted_at: string | null
  is_shared: boolean
}

type ReceivedShare = {
  id: string
  share_id: string
  type: string
  name: string | null
  client_name: string | null
  client_id: string | null
  daily_rate: number | null
  description: string | null
  created_at: string | null
  deleted_at: string | null
  shared_by: string | null
}

type SharedClientProject = {
  id: string
  name: string
  description: string | null
  daily_rate: number | null
  created_at: string | null
  deleted_at: string | null
  client_id: string
  client_name: string
  share_id: string
  shared_by: string
  received_share_id: string | null
}

withDefaults(defineProps<{
  projects: Project[]
  clients: Client[]
  shares_received: ReceivedShare[]
  shared_client_projects?: SharedClientProject[]
  search?: string
  client_id?: string
  with_trashed?: boolean
  sort?: string
  has_trashed?: boolean
  has_active?: boolean
}>(), {
  shared_client_projects: () => [],
})

const sortOptions = [
  { label: 'Date (newest)', value: 'date_desc' },
  { label: 'Date (oldest)', value: 'date_asc' },
  { label: 'Rate (high)', value: 'rate_desc' },
  { label: 'Rate (low)', value: 'rate_asc' },
]

type ShareableType = 'project' | 'client'

const shareOpen = ref(false)
const sharingType = ref<ShareableType>('project')
const sharingItem = ref<Project | Client | null>(null)
const shareUrl = ref('')
const copied = ref(false)
const shareLoading = ref(false)

const confirm = useConfirm()

const forgetClient = (share: ReceivedShare) => confirm({
  title: `Forget "${share.name}"?`,
  description: 'You will no longer have access to this shared client and its projects.',
  onConfirm: () => router.visit(destroyReceivedShare({ id: share.id }), {
    preserveScroll: true,
    only: ['shares_received', 'shared_client_projects'],
  }),
})

const deleteClient = (client: Client) => confirm({
  title: client.deleted_at ? `Delete "${client.name}"?` : `Archive "${client.name}"?`,
  description: client.deleted_at
    ? 'All data will be lost permanently.'
    : 'This will also archive all its projects. You will be able to restore them later.',
  onConfirm: () => router.visit(clientRoutes.destroy(client), { preserveScroll: true }),
})

const clientMenuItems = (client: Client): DropdownMenuItem[][] => {
  if (client.deleted_at) {
    return [
      [{ label: 'Restore', icon: 'i-lucide-rotate-ccw', onSelect: () => router.visit(clientRoutes.restore(client)) }],
      [{ label: 'Delete permanently', icon: 'i-lucide-trash-2', color: 'error' as const, onSelect: () => deleteClient(client) }],
    ]
  }

  return [
    [
      { label: 'Share', icon: 'i-lucide-share-2', onSelect: () => openShare('client', client) },
      { label: 'Edit', icon: 'i-lucide-pencil', href: clientRoutes.edit(client) },
    ],
    [{ label: 'Archive', icon: 'i-lucide-trash-2', color: 'error' as const, onSelect: () => deleteClient(client) }],
  ]
}

const onSearch = useDebounceFn((value: string) => {
  router.visit(projectsRoutes.index({ mergeQuery: { search: value || null } }), { preserveState: true, replace: true })
}, 300)

const http = useHttp()

function openShare(type: ShareableType, item: Project | Client) {
  sharingType.value = type
  sharingItem.value = item
  shareUrl.value = ''
  copied.value = false
  shareLoading.value = true
  shareOpen.value = true

  const route = type === 'project'
    ? shareProjectRoute(item.id)
    : shareClientRoute(item.id)

  http.submit(route, {
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
  if (!sharingItem.value) {
    return
  }

  const url = sharingType.value === 'project'
    ? destroyProjectShare(sharingItem.value.id)
    : destroyClientShare(sharingItem.value.id)

  router.visit(url, {
    preserveScroll: true,
    only: ['projects', 'clients', 'shares_received'],
    onSuccess: () => {
      shareOpen.value = false
      sharingItem.value = null
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
                  class="text-primary hover:underline focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary rounded"
                  :href="projectsRoutes.index({ mergeQuery: { client_id: null } })"
                >
                  {{ clients.find(c => c.id === client_id)?.name }}
                  ×
                </Link>
              </template>
            </p>
          </div>
          <UButton
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

        <div
          v-if="projects.length > 0 || shares_received.some(s => s.type === 'projects') || shared_client_projects.length > 0"
          class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
          <ProjectCard
            v-for="project in projects"
            :key="project.id"
            :project="project"
            @share="openShare('project', $event)"
          />

          <ProjectCard
            v-for="share in shares_received.filter(s => s.type === 'projects')"
            :key="share.id"
            :project="{
              id: share.id,
              name: share.name ?? '',
              description: share.description,
              daily_rate: share.daily_rate,
              client: { id: share.client_id ?? '', name: share.client_name ?? '' },
              created_at: share.created_at,
              deleted_at: share.deleted_at,
            }"
            :shared-by="share.shared_by"
            :share-id="share.share_id"
            :received-share-id="share.id"
          />

          <ProjectCard
            v-for="project in shared_client_projects"
            :key="project.id"
            :project="{
              id: project.id,
              name: project.name,
              description: project.description,
              daily_rate: project.daily_rate,
              client: { id: project.client_id, name: project.client_name },
              created_at: project.created_at,
              deleted_at: project.deleted_at,
            }"
            :shared-by="project.shared_by"
            :share-id="project.share_id"
            :received-share-id="project.received_share_id"
            :received-via-client="true"
          />
        </div>

        <div v-if="clients.length > 0 || shares_received.some(s => s.type === 'clients')" class="space-y-1">
          <div class="mb-2">
            <p class="text-xs font-medium uppercase tracking-wide text-muted">Clients</p>
          </div>

          <div
            v-for="client in clients"
            :key="client.id"
            class="flex items-center gap-1 rounded-md px-2 py-1.5 transition-colors"
            :class="!client.deleted_at && client_id === client.id ? 'bg-primary/10 ring-1 ring-primary/20' : 'hover:bg-elevated'"
          >
            <UTooltip text="Filter projects">
              <UButton
                icon="i-lucide-filter"
                color="neutral"
                variant="ghost"
                size="xs"
                :disabled="!!client.deleted_at"
                :href="client.deleted_at ? undefined : projectsRoutes.index({ mergeQuery: { client_id: client.id === client_id ? null : client.id } })"
                :class="client_id === client.id ? 'text-primary' : ''"
              />
            </UTooltip>
            <div class="flex flex-1 min-w-0 items-center gap-2 px-1">
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
              <span
                v-else-if="client.is_shared"
                class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs text-muted"
                title="Shared with others"
              >
                <UIcon name="i-lucide-users" class="h-3 w-3" />
                Shared
              </span>
            </div>
            <UTooltip v-if="!client.deleted_at" text="Billing">
              <UButton
                :as="Link"
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

          <div
            v-for="share in shares_received.filter(s => s.type === 'clients')"
            :key="share.id"
            class="flex items-center gap-1 rounded-md px-2 py-1.5 hover:bg-elevated transition-colors opacity-75 hover:opacity-100"
          >
            <UTooltip text="Filter projects">
              <UButton
                icon="i-lucide-filter"
                color="neutral"
                variant="ghost"
                size="xs"
                :href="projectsRoutes.index({ mergeQuery: { client_id: share.client_id === client_id ? null : share.client_id } })"
              />
            </UTooltip>
            <div class="flex flex-1 min-w-0 items-center gap-2 px-1">
              <span class="text-sm">{{ share.name }}</span>
              <span v-if="share.daily_rate" class="text-xs text-muted">{{ formatCurrency(share.daily_rate) }}/day</span>
              <span class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-xs text-primary">
                <UIcon name="i-lucide-share-2" class="h-3 w-3" />
                Shared by {{ share.shared_by }}
              </span>
            </div>
            <UTooltip text="Billing">
              <UButton
                :as="Link"
                :href="sharesRoutes.show(share.share_id)"
                icon="i-lucide-receipt-text"
                color="neutral"
                variant="ghost"
                size="xs"
              />
            </UTooltip>
            <UDropdownMenu
              :items="[[{
                label: 'Forget',
                icon: 'i-lucide-x',
                color: 'error',
                onSelect: () => forgetClient(share),
              }]]"
              class="shrink-0"
            >
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
    </main>

    <slot />

    <UModal v-model:open="shareOpen" :title="`Share ${sharingType === 'project' ? 'project' : 'client'}`">
      <template #body>
        <div class="space-y-3">
          <p class="text-sm text-muted">
            Copy this link and send it to the person you want to share
            <span class="font-medium text-default">{{ sharingItem?.name }}</span> with.
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
          <div v-if="shareUrl" class="flex justify-between items-center border-t border-default pt-3">
            <UButton
              label="Revoke link"
              icon="i-lucide-link-2-off"
              color="error"
              variant="ghost"
              size="sm"
              @click="revokeShare"
            />
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
