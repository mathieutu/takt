<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppHeader from '@/components/AppHeader.vue'
import { formatDateTime } from '@/utils/date.ts'
import { useFlash } from '../composables/useFlash'

const page = usePage()
const updatedAt = computed(() => page.props.updatedAt)

useFlash()
</script>

<template>
  <UApp>
    <AppHeader />

    <div v-if="page.props.auth?.isDemo" class="bg-primary-200 text-primary-800 px-4 py-4 text-center text-base">
      Compte de démonstration · Vous pouvez modifier les données, mais elles sont réinitialisées régulièrement.
    </div>

    <main class="min-h-[calc(100vh-var(--ui-header-height)-4rem)]">
      <slot />
    </main>
    <UFooter>
      <template #left>
        <p class="text-muted text-xs">© {{ new Date().getFullYear() }} Takt · Dernière mise à jour {{ formatDateTime(updatedAt) }}</p>
      </template>
      <template #right>
        <UButton
          icon="i-simple-icons:github"
          color="neutral"
          variant="ghost"
          to="https://github.com/mathieutu/cra"
          target="_blank"
          aria-label="GitHub"
          size="xs"
        />
        <p class="text-muted text-xs">
          Made with ♥︎ by
          <ULink to="https://mathieutu.dev" target="_blank" class="font-medium hover:text-primary">Mathieu TUDISCO</ULink>
        </p>
      </template>
    </UFooter>
  </UApp>
</template>
