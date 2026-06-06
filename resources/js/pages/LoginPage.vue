<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import TaktLogo from '@/components/TaktLogo.vue'
import { useFlash } from '@/composables/useFlash'
import { disabled } from '@/wayfinder/routes/login'

defineOptions({ layout: () => false })

defineProps<{
  users: Array<{ id: string, name: string, email: string }>,
  redirectUrl: string | null,
}>()

useFlash()
</script>

<template>
  <UApp>
    <div class="flex min-h-screen flex-col items-center justify-center bg-default px-4">
      <div class="mb-8 flex items-center gap-2">
        <TaktLogo class="h-9 w-9" />
        <span class="text-xl font-semibold tracking-tight">Takt</span>
      </div>

      <UCard class="w-full max-w-sm">
        <template #header>
          <h1 class="text-base font-semibold">Sign in</h1>
          <p class="mt-1 text-sm text-muted">Access your management space</p>
        </template>

        <div class="flex flex-col gap-3">
          <template v-if="users.length > 0">
            <p class="text-xs font-medium text-muted uppercase tracking-wide">
              Quick login (local)
            </p>
            <div class="flex flex-col gap-2">
              <Link
                v-for="user in users"
                :key="user.id"
                :href="disabled()"
                :data="{ user_id: user.id }"
                class="flex items-center gap-3 rounded-md border border-default bg-default px-3 py-2 text-sm transition-colors hover:bg-elevated text-left"
              >
                <span class="flex flex-col">
                  <span class="font-medium">{{ user.name }}</span>
                  <span class="text-xs text-muted">{{ user.email }}</span>
                </span>
              </Link>
            </div>
          </template>

          <template v-else>
            <UButton
              as="a"
              :href="redirectUrl"
              label="Sign in with GitHub"
              icon="i-simple-icons-github"
              block
            />
          </template>
        </div>
      </UCard>
    </div>
  </UApp>
</template>
