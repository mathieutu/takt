<script setup lang="ts">
import ProjectPage from '@/components/ProjectPage.vue'

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

const props = withDefaults(defineProps<{
  projects?: Project[]
  clients?: Client[]
  shares_received?: ReceivedShare[]
  shared_client_projects?: SharedClientProject[]
  search?: string
  client_id?: string
  with_trashed?: boolean
  sort?: string
  has_trashed?: boolean
  has_active?: boolean
}>(), {
  projects: () => [],
  clients: () => [],
  shares_received: () => [],
  shared_client_projects: () => [],
  search: '',
  client_id: '',
  with_trashed: false,
  sort: 'date_desc',
  has_trashed: false,
  has_active: false,
})
</script>

<template>
  <ProjectPage v-bind="props">
    <slot />
  </ProjectPage>
</template>
