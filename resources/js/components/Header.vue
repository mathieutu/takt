<script setup lang="ts">
import { computed } from 'vue'
import type { AuthUser } from '../types'

const props = defineProps<{
    user: AuthUser | null
}>()

const emit = defineEmits<{
    'toggle-sidebar': []
}>()

const greeting = computed(() => {
    const first = props.user?.name?.split(' ')[0]
    return first || props.user?.email || ''
})
</script>

<template>
    <header class="fixed top-0 left-0 right-0 z-50 flex h-14 items-center justify-between border-b border-default bg-elevated px-4">
        <div class="flex items-center gap-2">
            <img src="/public/images/logo.png" class="h-7 w-7 rounded-md object-cover">
            <span class="text-sm font-semibold">AssoFlow</span>
        </div>

        <div class="hidden md:flex items-center gap-1 text-sm">
            <span class="text-muted">Bonjour,</span>
            <span class="font-medium">{{ greeting }}</span>
        </div>

        <UButton
            class="md:hidden"
            icon="i-lucide-menu"
            color="neutral"
            variant="ghost"
            aria-label="Menu"
            @click="emit('toggle-sidebar')"
        />
    </header>
</template>
