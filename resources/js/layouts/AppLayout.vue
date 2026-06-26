<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { computed, onMounted, onUnmounted } from 'vue'
import AppHeader from '@/components/AppHeader.vue'
import CommandPalette from '@/components/CommandPalette.vue'
import { useCommandPalette } from '@/composables/useCommandPalette'
import { formatDateTime } from '@/utils/date.ts'
import { useFlash } from '../composables/useFlash'

const page = usePage()
const updatedAt = computed(() => page.props.updatedAt)

useFlash()

const { toggle } = useCommandPalette()

const handleKeydown = (e: KeyboardEvent) => {
  if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
    e.preventDefault()
    toggle()
  }
}

onMounted(() => window.addEventListener('keydown', handleKeydown))
onUnmounted(() => window.removeEventListener('keydown', handleKeydown))
</script>

<template>
  <UApp>
    <AppHeader />
    <CommandPalette />

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
