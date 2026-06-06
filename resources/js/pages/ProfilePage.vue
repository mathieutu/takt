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
  title: 'Permanently delete your account?',
  description: 'This action is irreversible. All your data will be lost.',
  onConfirm: () => router.visit(destroy()),
})
</script>

<template>
  <main class="flex-1 px-6 py-8">
    <div class="mx-auto max-w-2xl space-y-6">
      <div>
        <h1 class="text-lg font-semibold">Profile</h1>
        <p class="text-sm text-muted">Manage your profile and information</p>
      </div>

      <UCard>
        <template #header>
          <h2 class="text-sm font-semibold">Information</h2>
        </template>
        <Form v-slot="{ errors, processing }" class="space-y-6" :action="update()" novalidate>
          <div class="space-y-4">
            <UFormField label="Name" required :error="errors.name">
              <UInput
                v-model="values.name"
                type="text"
                name="name"
                autocomplete="name"
                class="w-full"
              />
            </UFormField>

            <UFormField label="Email address" required :error="errors.email">
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
                Connected via GitHub
              </div>
            </div>
          </div>
          <div class="flex justify-end">
            <UButton type="submit" :loading="processing" label="Save" />
          </div>
        </Form>
      </UCard>

      <UCard>
        <template #header>
          <h2 class="text-sm font-semibold">Danger zone</h2>
        </template>

        <div class="flex items-center justify-between gap-4">
          <div>
            <p class="text-sm font-medium">Delete my account</p>
            <p class="text-xs text-muted">This action is irreversible.</p>
          </div>
          <UButton
            type="button"
            label="Delete"
            color="error"
            variant="outline"
            @click="() => void deleteUser()"
          />
        </div>
      </UCard>
    </div>
  </main>
</template>
