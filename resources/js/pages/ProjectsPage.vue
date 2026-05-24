<script setup lang="ts">
import ProjectPage from '@/components/ProjectPage.vue'

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
  client: { id: string, name: string },
  created_at: string,
  deleted_at: string | null,
  is_shared: boolean,
}

const props = withDefaults(defineProps<{
  projects?: Project[],
  clients?: Client[],
  search?: string,
  client_id?: string,
  with_trashed?: boolean,
  sort?: string,
}>(), {
  projects: () => [],
  clients: () => [],
  search: '',
  client_id: '',
  with_trashed: false,
  sort: 'date_desc',
})
</script>

<template>
  <ProjectPage v-bind="props" />
</template>
