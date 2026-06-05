<script setup lang="ts">
import type { AuthUser } from '../types'
import { computed } from 'vue'

const props = defineProps<{
  user: AuthUser | null,
}>()

const emit = defineEmits<{
  toggleSidebar: [],
}>()

const greeting = computed(() => {
  const first = props.user?.name?.split(' ')[0]
  return first || props.user?.email || ''
})
</script>

<template>
  <header
    class="fixed top-0 left-0 right-0 z-50 flex h-14 items-center justify-between border-b border-default bg-elevated px-4"
  >
    <div class="flex items-center gap-2">
      <img src="/public/images/logo.png" class="h-7 w-7 rounded-md object-cover" />
      <span class="text-sm font-semibold">AssoFlow</span>
    </div>

    <div class="hidden md:block text-sm">
      <span class="text-muted">Hi</span>
      <span v-if="user" class="font-medium pl-1">{{ user.name }}</span>
      <span>!</span>
    </div>

    <UButton
      class="md:hidden"
      icon="i-lucide-menu"
      color="neutral"
      variant="ghost"
      aria-label="Menu"
      @click="emit('toggleSidebar')"
    />
  </header>
</template>
