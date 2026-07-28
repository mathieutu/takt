<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import ProjectPage from '@/components/ProjectPage.vue'
import { useConfirm } from '@/composables/useConfirm.ts'
import { update } from '@/wayfinder/routes/clients'
import { destroy as destroyClientShare, store as storeClientShare } from '@/wayfinder/routes/clients/share'
import { index } from '@/wayfinder/routes/projects'

type ClientFormData = {
  id: string,
  name: string,
  daily_rate: number,
  share_url: string | null,
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

const copied = ref(false)

const shareClient = () => {
  router.visit(storeClientShare(modal.client.id), { preserveScroll: true, only: ['modal'] })
}

const copyShareUrl = async () => {
  if (!modal.client.share_url) return
  await navigator.clipboard.writeText(modal.client.share_url)
  copied.value = true
  setTimeout(() => {
    copied.value = false
  }, 2000)
}

const confirm = useConfirm()

const revokeShare = () => {
  confirm({
    title: `Révoquer le lien de partage pour "${modal.client.name}" ?`,
    description: 'Quiconque dispose du lien perdra immédiatement l\'accès.',
    onConfirm: () => router.visit(destroyClientShare(modal.client.id), { preserveScroll: true, only: ['modal'] }),
  })
}
</script>

<template>
  <Head :title="modal.client.name" />
  <ProjectPage v-bind="page">
    <UModal
      :open="true"
      title="Modifier le client"
      @update:open="(v: boolean) => !v && close()"
    >
      <template #body>
        <div class="space-y-6">
          <UForm id="client-form" class="space-y-4" @submit.prevent="submit">
            <UFormField label="Nom" required :error="form.errors.name">
              <UInput
                v-model="form.name"
                type="text"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Tarif journalier (€/j)" required :error="form.errors.daily_rate">
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

          <div class="space-y-2 border-t border-default pt-4">
            <p class="text-sm font-medium">Partage</p>

            <div v-if="modal.client.share_url" class="space-y-2">
              <p class="text-xs text-muted">
                Copiez ce lien et envoyez-le à la personne avec qui vous souhaitez partager ce client.
              </p>
              <UInput :modelValue="modal.client.share_url" readonly class="w-full" />
              <div class="flex justify-end gap-2">
                <UButton
                  label="Révoquer"
                  icon="i-lucide-link-2-off"
                  color="error"
                  variant="ghost"
                  size="sm"
                  @click="revokeShare"
                />
                <UButton
                  :label="copied ? 'Copié !' : 'Copier'"
                  icon="i-lucide-copy"
                  color="neutral"
                  variant="ghost"
                  size="sm"
                  @click="copyShareUrl"
                />
              </div>
            </div>
            <UButton
              v-else
              label="Partager ce client"
              icon="i-lucide-share-2"
              color="neutral"
              variant="outline"
              size="sm"
              @click="shareClient"
            />
          </div>
        </div>
      </template>
      <template #footer>
        <div class="flex justify-end gap-2">
          <UButton :href="index()" label="Annuler" color="neutral" variant="outline" />
          <UButton label="Enregistrer" :loading="form.processing" type="submit" form="client-form" />
        </div>
      </template>
    </UModal>
  </ProjectPage>
</template>
