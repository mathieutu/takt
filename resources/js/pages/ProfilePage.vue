<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3'
import { useToast } from '@nuxt/ui/composables'
import { ref } from 'vue'
import { useConfirm } from '@/composables/useConfirm.ts'
import { api as apiDocUrl } from '@/wayfinder/routes/docs'
import { destroy, update } from '@/wayfinder/routes/profile'
import { destroy as destroyToken, regenerate as regenerateToken } from '@/wayfinder/routes/profile/tokens'

const props = defineProps<{
  user: {
    name: string,
    email: string,
    github_id: string | null,
    api_token: string | null,
  },
}>()

const values = ref(props.user)

const toast = useToast()
const isTokenVisible = ref(false)

const copyToken = async () => {
  await navigator.clipboard.writeText(props.user.api_token!)
  toast.add({ title: 'Token copié dans le presse-papier', color: 'success' })
}

const destroyUserConfirm = useConfirm({
  title: 'Supprimer définitivement votre compte ?',
  description: 'Cette action est irréversible. Toutes vos données seront perdues.',
  onConfirm: () => router.visit(destroy()),
})

const destroyTokenConfirm = useConfirm({
  title: 'Supprimer définitivement votre token d\'api ?',
  description: 'Cette action est irréversible. Votre api ne fonctionnera plus.',
  onConfirm: () => router.visit(destroyToken(), { preserveScroll: true }),
})

const regenerateTokenConfirm = useConfirm({
  title: 'Régénérer votre token d\'api ?',
  description: 'Cette action est irréversible. Votre api ne fonctionnera plus avec l\'ancien Token.',
  onConfirm: () => router.visit(regenerateToken(), { preserveScroll: true }),
})
</script>

<template>
  <Head title="Profil" />
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
          <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold">Accès API</h2>
            <UButton
              label="Documentation"
              icon="i-lucide-book-open"
              color="neutral"
              variant="ghost"
              size="xs"
              :href="apiDocUrl()"
            />
          </div>
        </template>

        <div class="space-y-4">
          <p class="text-sm text-muted">
            Utilisez ce token pour accéder à l'API depuis un script externe.
            Il est affiché en clair — conservez-le en lieu sûr.
          </p>

          <div v-if="user.api_token">
            <UInput
              :value="user.api_token"
              :type="isTokenVisible ? 'text' : 'password'"
              variant="subtle"
              readonly
              class="w-full font-mono text-xs"
              :ui="{
                base: ['pe-16'],
              }"
            >
              <template #trailing>
                <div class="">
                  <UTooltip :text="isTokenVisible ? 'Masquer' : 'Afficher'">
                    <UButton
                      :icon="isTokenVisible ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                      color="neutral"
                      variant="link"
                      size="sm"
                      :aria-label="isTokenVisible ? 'Masquer le token' : 'Afficher le token'"
                      class=""
                      @click="isTokenVisible = !isTokenVisible"
                    />
                  </UTooltip>
                  <UTooltip text="Copier">
                    <UButton
                      icon="i-lucide-copy"
                      color="neutral"
                      variant="link"
                      size="sm"
                      aria-label="Copier le token"
                      class="pr-0"
                      @click="copyToken"
                    />
                  </UTooltip>
                </div>
              </template>
            </UInput>
          </div>
          <p v-else class="text-sm">Aucun token généré.</p>

          <div class="flex justify-end gap-2">
            <UButton
              v-if="user.api_token"
              label="Supprimer le token"
              color="error"
              variant="ghost"
              @click="destroyTokenConfirm()"
            />
            <UButton
              v-if="user.api_token"
              label="Régénérer"
              color="neutral"
              variant="outline"
              @click="regenerateTokenConfirm()"
            />
            <UButton
              v-else
              label="Générer un token"
              color="primary"
              variant="solid"
              preserveScroll
              :href="regenerateToken()"
            />
          </div>
        </div>
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
            @click="() => void destroyUserConfirm()"
          />
        </div>
      </UCard>
    </div>
  </main>
</template>
