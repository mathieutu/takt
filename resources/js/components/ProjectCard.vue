<script setup lang="ts">
import type { DropdownMenuItem } from '@nuxt/ui/components/DropdownMenu.d.vue.ts'
import { Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useConfirm } from '@/composables/useConfirm'
import { formatDate } from '@/utils/date.ts'
import { formatCurrency } from '@/utils/number.ts'
import projectsRoutes from '@/wayfinder/routes/projects'
import { show as billingShow } from '@/wayfinder/routes/projects/billing'
import { destroy as destroyReceivedShare } from '@/wayfinder/routes/received-shares'
import sharesRoutes from '@/wayfinder/routes/shares'

type Project = {
  id: string
  name: string
  description?: string | null
  daily_rate?: number | null
  client: { id: string; name: string }
  created_at?: string | null
  deleted_at?: string | null
  is_shared?: boolean
}

const props = withDefaults(defineProps<{
  project: Project
  sharedBy?: string | null
  shareId?: string | null
  receivedShareId?: string | null
  receivedViaClient?: boolean
}>(), {
  sharedBy: null,
  shareId: null,
  receivedShareId: null,
  receivedViaClient: false,
})

const emit = defineEmits<{
  share: [project: Project]
}>()

const isReceived = computed(() => !!props.sharedBy)

const confirm = useConfirm()

const deleteProject = () => confirm({
  title: props.project.deleted_at ? `Delete "${props.project.name}"?` : `Archive "${props.project.name}"?`,
  description: props.project.deleted_at ? 'All data will be lost permanently.' : 'You will be able to restore it later',
  onConfirm: () => router.visit(projectsRoutes.destroy(props.project), { preserveScroll: true }),
})

const forgetItem = () => confirm({
  title: props.receivedViaClient ? `Forget "${props.project.client.name}"?` : `Forget "${props.project.name}"?`,
  description: props.receivedViaClient
    ? 'You will no longer have access to this shared client and its projects.'
    : 'You will no longer have access to this shared project.',
  onConfirm: () => router.visit(destroyReceivedShare({ id: props.receivedShareId! }), {
    preserveScroll: true,
    only: ['shares_received', 'shared_client_projects'],
  }),
})

const menuItems = computed((): DropdownMenuItem[][] => {
  if (isReceived.value) {
    return [[{
      label: props.receivedViaClient ? 'Forget client' : 'Forget project',
      icon: 'i-lucide-x',
      color: 'error' as const,
      onSelect: forgetItem,
    }]]
  }

  if (props.project.deleted_at) {
    return [
      [{ label: 'Restore', icon: 'i-lucide-rotate-ccw', href: projectsRoutes.restore(props.project) }],
      [{ label: 'Delete permanently', icon: 'i-lucide-trash-2', color: 'error' as const, onSelect: deleteProject }],
    ]
  }

  return [
    [
      { label: 'Edit', icon: 'i-lucide-pencil', href: projectsRoutes.edit(props.project), only: ['modal'] },
      { label: 'Duplicate', icon: 'i-lucide-copy', href: projectsRoutes.duplicate(props.project) },
    ],
    [{ label: 'Share', icon: 'i-lucide-share-2', onSelect: () => emit('share', props.project) }],
    [{ label: 'Archive', icon: 'i-lucide-trash-2', color: 'error' as const, onSelect: deleteProject }],
  ]
})

const billingHref = computed(() => {
  if (!isReceived.value) {
    return billingShow(props.project)
  }
  if (props.shareId) {
    return sharesRoutes.show(props.shareId)
  }
  return null
})
</script>

<template>
  <div
    class="rounded-lg border border-default bg-elevated p-5 flex flex-col h-full justify-between transition-opacity"
    :class="[project.deleted_at ? 'opacity-60' : '', isReceived ? 'opacity-75 hover:opacity-100' : '']"
  >
    <div class="flex items-start justify-between gap-2">
      <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-semibold" :class="project.deleted_at ? 'line-through text-muted' : ''">
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
        <UTooltip v-if="!project.deleted_at && billingHref" text="Billing">
          <UButton
            :as="Link"
            :href="billingHref"
            icon="i-lucide-receipt-text"
            color="neutral"
            variant="ghost"
            size="xs"
          />
        </UTooltip>
        <UTooltip text="Actions">
          <UDropdownMenu :items="menuItems" class="shrink-0">
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
        <span
          v-else-if="project.is_shared && !isReceived"
          class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs text-muted"
          title="Shared with others"
        >
          <UIcon name="i-lucide-users" class="h-3 w-3" />
          Shared
        </span>
        <span
          v-else-if="isReceived"
          class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-xs text-primary"
        >
          <UIcon name="i-lucide-share-2" class="h-3 w-3" />
          Shared by {{ sharedBy }}
        </span>
      </div>
      <span v-if="project.created_at" class="text-xs text-muted">{{ formatDate(project.created_at) }}</span>
    </div>
  </div>
</template>
