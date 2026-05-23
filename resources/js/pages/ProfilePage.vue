<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useConfirm } from '@/composables/useConfirm.ts'
import { destroy, update } from '@/wayfinder/routes/profile'

const props = defineProps<{
  user: {
    name: string,
    email: string,
    github_id: string | null,
  },
}>()

const values = ref(props.user)

const deleteUser = useConfirm({
  title: 'Supprimer définitivement votre compte ?',
  description: 'Cette action est irréversible. Toutes vos données seront perdues.',
  onConfirm: () => router.visit(destroy()),
})
</script>

<template>
  <main class="flex-1 px-6 py-8">
    <div class="mx-auto max-w-2xl space-y-6">
      <div>
        <h1 class="text-lg font-semibold">Profil</h1>
        <p class="text-sm text-muted">Gérez votre profil et vos informations</p>
      </div>

      <UCard>
        <template #header>
          <h2 class="text-sm font-semibold">Informations</h2>
        </template>
        <Form v-slot="{ errors, processing }" class="space-y-6" :action="update()" novalidate>
          <div class="space-y-4">
            <UFormField label="Nom" required :error="errors.name">
              <UInput
                v-model="values.name"
                type="text"
                name="name"
                autocomplete="name"
                class="w-full"
              />
            </UFormField>

            <UFormField label="Adresse e-mail" required :error="errors.email">
              <UInput
                v-model="values.email"
                name="email"
                type="email"
                autocomplete="email"
                class="w-full"
              />
            </UFormField>

            <div v-if="user.github_id" class="flex flex-col gap-1.5">
              <span class="text-sm font-medium">GitHub</span>
              <div class="flex items-center gap-2 text-sm text-muted">
                <UIcon name="i-simple-icons-github" class="size-4 shrink-0" />
                Connecté via GitHub
              </div>
            </div>
          </div>
          <div class="flex justify-end">
            <UButton type="submit" :loading="processing" label="Enregistrer" />
          </div>
        </Form>
      </UCard>

      <UCard>
        <template #header>
          <h2 class="text-sm font-semibold">Zone de danger</h2>
        </template>

        <div class="flex items-center justify-between gap-4">
          <div>
            <p class="text-sm font-medium">Supprimer mon compte</p>
            <p class="text-xs text-muted">Cette action est irréversible.</p>
          </div>
          <UButton
            type="button"
            label="Supprimer"
            color="error"
            variant="outline"
            @click="deleteUser"
          />
        </div>
      </UCard>
    </div>
  </main>
</template>
