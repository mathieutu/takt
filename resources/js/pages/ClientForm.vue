<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import ProjectPage from '@/components/ProjectPage.vue'
import { update } from '@/wayfinder/routes/clients'
import { index } from '@/wayfinder/routes/projects'

type ClientFormData = {
  id: string,
  name: string,
  daily_rate: number,
}

type BackgroundClient = {
  id: string,
  name: string,
  daily_rate: number,
  deleted_at: string | null,
  created_at: string,
  share_url: string | null,
}

type BackgroundProject = {
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

const { modal } = defineProps<{
  modal: {
    client: ClientFormData,
  },
  page: {
    projects: BackgroundProject[],
    clients: BackgroundClient[],
    search?: string,
    client_id?: string,
    with_trashed?: boolean,
    sort?: string,
    has_trashed?: boolean,
    has_active?: boolean,
  },
}>()

const form = useForm({
  name: modal.client.name,
  daily_rate: modal.client.daily_rate,
})

const submit = () => form.submit(update(modal.client))
const close = () => router.visit(index({ mergeQuery: {} }))
</script>

<template>
  <ProjectPage v-bind="page">
    <UModal
      :open="true"
      title="Edit Client"
      @update:open="(v: boolean) => !v && close()"
    >
      <template #body>
        <UForm id="client-form" class="space-y-4" @submit.prevent="submit">
          <UFormField label="Name" required :error="form.errors.name">
            <UInput
              v-model="form.name"
              type="text"
              class="w-full"
            />
          </UFormField>
          <UFormField label="Daily Rate (€/day)" required :error="form.errors.daily_rate">
            <UInput
              :modelValue="form.daily_rate / 100"
              type="number"
              min="0"
              step="0.01"
              class="w-full"
              @update:modelValue="(val: number) => form.daily_rate = Math.round(val * 100)"
            />
          </UFormField>
        </UForm>
      </template>
      <template #footer>
        <div class="flex justify-end gap-2">
          <UButton :href="index()" label="Cancel" color="neutral" variant="outline" />
          <UButton label="Save" :loading="form.processing" type="submit" form="client-form" />
        </div>
      </template>
    </UModal>
  </ProjectPage>
</template>
