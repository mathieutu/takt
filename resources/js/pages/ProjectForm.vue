<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'
import DateInput from '@/components/DateInput.vue'
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
  max_month_budget: number | null,
  max_total_budget: number | null,
  client_id: string,
  start_date: string,
  end_date: string | null,
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
  start_date: string,
  end_date: string | null,
  is_inactive: boolean,
}

const { modal } = defineProps<{
  modal: {
    project?: ProjectFormData | null,
    clients: FormClient[],
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
  name: modal.project?.name ?? '',
  description: modal.project?.description ?? '',
  daily_rate: modal.project?.daily_rate ?? null,
  max_month_budget: modal.project?.max_month_budget ?? null,
  max_total_budget: modal.project?.max_total_budget ?? null,
  client_id: modal.project?.client_id ?? modal.clients[0]?.id ?? null,
  client_name: '',
  client_rate: null as number | null,
  start_date: modal.project?.start_date ?? '',
  end_date: modal.project?.end_date ?? '',
})

watch(() => form.client_id, clientId => {
  if (!clientId) {
    return form.daily_rate = null
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
const close = () => router.visit(index({ mergeQuery: {} }), { only: ['modal'] })
</script>

<template>
  <Head :title="modal.project?.name ?? 'Nouveau projet'" />
  <ProjectPage v-bind="page">
    <UModal
      :open="true"
      :title="modal.project ? 'Modifier le projet' : 'Nouveau projet'"
      :ui="{ content: 'sm:max-w-2xl' }"
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
              <UFormField label="Tarif journalier client (€/j)" required :error="form.errors.client_rate">
                <UInput
                  type="number"
                  min="0"
                  step="0.01"
                  class="w-full"
                  :modelValue="form.client_rate && form.client_rate / 100"
                  @update:modelValue="(val: number | null) => form.client_rate = val ? Math.round(val * 100) : null"
                />
              </UFormField>
            </div>
          </template>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
          </div>

          <UFormField label="Tarif journalier projet (€/j)" required :error="form.errors.daily_rate">
            <UInput
              type="number"
              min="0"
              step="0.01"
              :modelValue="form.daily_rate != null ? form.daily_rate / 100 : null"
              class="w-full"
              @update:modelValue="(val: number | null) => form.daily_rate = val != null ? Math.round(val * 100) : null"
            />
          </UFormField>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <UFormField label="Plafond mensuel (€/mois)" :error="form.errors.max_month_budget">
              <UInput
                type="number"
                min="0"
                step="0.01"
                placeholder="Illimité"
                :modelValue="form.max_month_budget && form.max_month_budget / 100"
                class="w-full"
                @update:modelValue="(val: number | null) => form.max_month_budget = val ? Math.round(val * 100) : null"
              />
            </UFormField>

            <UFormField label="Enveloppe budgétaire totale (€)" :error="form.errors.max_total_budget">
              <UInput
                type="number"
                min="0"
                step="0.01"
                placeholder="Sans limite"
                :modelValue="form.max_total_budget && form.max_total_budget / 100"
                class="w-full"
                @update:modelValue="(val: number | null) => form.max_total_budget = val ? Math.round(val * 100) : null"
              />
            </UFormField>
          </div>

          <div v-if="modal.project" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <UFormField label="Début le" required :error="form.errors.start_date">
              <DateInput v-model="form.start_date" />
            </UFormField>

            <UFormField label="Fin le" :error="form.errors.end_date">
              <DateInput v-model="form.end_date" />
            </UFormField>
          </div>
        </UForm>
      </template>
      <template #footer>
        <div class="flex justify-end gap-2">
          <UButton :href="index()" label="Annuler" color="neutral" variant="outline" />
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
