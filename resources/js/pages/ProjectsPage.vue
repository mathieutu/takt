<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import ProjectPage from '@/components/ProjectPage.vue'

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
  start_date: string,
  end_date: string | null,
  is_archived: boolean,
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
  <Head title="Projets" />
  <ProjectPage v-bind="props">
    <slot />
  </ProjectPage>
</template>
