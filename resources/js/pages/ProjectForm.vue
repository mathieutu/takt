<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'
import ProjectPage from '@/components/ProjectPage.vue'
import { index, store, update } from '@/wayfinder/routes/projects'

type FormClient = {
  id: string,
  name: string,
  daily_rate: number,
}

type ProjectFormData = {
  id: string,
  name: string,
  description: string,
  daily_rate: number | null,
  max_budget: number | null,
  client_id: string,
}

type BackgroundClient = {
  id: string,
  name: string,
  daily_rate: number,
  deleted_at: string | null,
  created_at: string,
}

type BackgroundProject = {
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

const { modal } = defineProps<{
  modal: {
    project?: ProjectFormData | null,
    clients: FormClient[],
  },
  page: {
    projects?: BackgroundProject[],
    clients?: BackgroundClient[],
    search?: string,
    client_id?: string,
    with_trashed?: boolean,
    sort?: string,
  },
}>()

const form = useForm({
  name: modal.project?.name ?? '',
  description: modal.project?.description ?? '',
  daily_rate: modal.project?.daily_rate ?? null as unknown as number,
  max_budget: modal.project?.max_budget ?? null as unknown as number,
  client_id: modal.project?.client_id ?? modal.clients[0]?.id ?? null,
  client_name: '',
  client_rate: null as unknown as number,
})

watch(() => form.client_id, clientId => {
  if (!clientId) {
    return form.daily_rate = null as unknown as number
  }

  const client = modal.clients.find(({ id }) => id === clientId)

  if (client && !modal.project) {
    form.daily_rate = client.daily_rate
  }
}, { immediate: !modal.project })

watch(() => form.client_rate, clientRate => {
  if (!form.client_id && !modal.project) {
    form.daily_rate = clientRate
  }
})

const clientSelectItems = computed(() => [
  ...(!modal.project ? [{ value: null, label: '+ Nouveau client' }, { type: 'separator' as const }, { type: 'label' as const, label: 'Clients existants' }] : []),
  ...modal.clients.map(c => ({ value: c.id, label: c.name })),
])

const submit = () => form.submit(modal.project ? update(modal.project) : store())
const close = () => router.visit(index())
</script>

<template>
  <ProjectPage v-bind="page">
    <UModal
      :open="true"
      :title="modal.project ? 'Modifier le projet' : 'Nouveau projet'"
      @update:open="(v: boolean) => !v && close()"
    >
      <template #body>
        <UForm id="project-form" class="space-y-4" @submit.prevent="submit">
          <UFormField label="Client" required :error="form.errors.client_id">
            <USelect
              v-model="form.client_id"
              :items="clientSelectItems"
              class="w-full"
            />
          </UFormField>

          <template v-if="!form.client_id && !modal.project">
            <div class="rounded-md border border-default bg-muted/40 p-3 space-y-3">
              <UFormField label="Nom du client" required :error="form.errors.client_name">
                <UInput
                  v-model="form.client_name"
                  type="text"
                  class="w-full"
                />
              </UFormField>
              <UFormField label="TJM du client (€/jour)" required :error="form.errors.client_rate">
                <UInput
                  type="number"
                  min="0"
                  step="0.01"
                  class="w-full"
                  :modelValue="form.client_rate && form.client_rate / 100"
                  @update:modelValue="(val: number) => form.client_rate = Math.round(val * 100)"
                />
              </UFormField>
            </div>
          </template>

          <UFormField label="Nom du projet" required :error="form.errors.name">
            <UInput
              v-model="form.name"
              type="text"
              class="w-full"
            />
          </UFormField>

          <UFormField label="Description" :error="form.errors.description">
            <UInput v-model="form.description" type="text" class="w-full" />
          </UFormField>

          <UFormField label="TJM du projet (€/jour)" required :error="form.errors.daily_rate">
            <UInput
              type="number"
              min="0"
              step="0.01"
              :modelValue="form.daily_rate && form.daily_rate / 100"
              class="w-full"
              @update:modelValue="(val: number) => form.daily_rate = Math.round(val * 100)"
            />
          </UFormField>

          <UFormField label="Budget maximum (€/mois)" :error="form.errors.max_budget">
            <UInput
              type="number"
              min="0"
              step="0.01"
              placeholder="Illimité"
              :modelValue="form.max_budget && form.max_budget / 100"
              class="w-full"
              @update:modelValue="(val: number) => form.max_budget = Math.round(val * 100)"
            />
          </UFormField>
        </UForm>
      </template>
      <template #footer>
        <div class="flex justify-end gap-2">
          <UButton :as="Link" :href="index()" label="Annuler" color="neutral" variant="outline" />
          <UButton
            type="submit"
            form="project-form"
            :label="modal.project ? 'Enregistrer' : 'Créer'"
            :loading="form.processing"
            @click="submit"
          />
        </div>
      </template>
    </UModal>
  </ProjectPage>
</template>
