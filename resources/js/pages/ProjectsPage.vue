<script setup lang="ts">
import ProjectPage from '@/components/ProjectPage.vue'

type Client = {
  id: string,
  name: string,
  daily_rate: number,
  deleted_at: string | null,
  created_at: string,
  is_shared: boolean,
}

type Project = {
  id: string,
  name: string,
  description: string,
  daily_rate: number,
  max_month_budget: number | null,
  client: { id: string, name: string },
  created_at: string,
  deleted_at: string | null,
}

const props = withDefaults(defineProps<{
  projects?: Project[],
  clients?: Client[],
  search?: string,
  client_id?: string,
  with_trashed?: boolean,
  sort?: string,
  has_trashed?: boolean,
  has_active?: boolean,
}>(), {
  projects: () => [],
  clients: () => [],
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
