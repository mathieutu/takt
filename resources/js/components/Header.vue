<script setup lang="ts">
import { computed } from 'vue'

interface User {
    name: string
    role: string
}

const props = defineProps<{
    title: string
    user: User
}>()

const firstName = computed(() => props.user.name.split(' ')[0])

const currentDate = computed(() =>
    new Date().toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    })
)
</script>

<template>
    <header class="flex shrink-0 items-center justify-between border-b border-border px-6 py-4">
        <div>
            <h1 class="text-base font-semibold text-foreground">{{ title }}</h1>
            <p class="capitalize text-xs text-muted-foreground">{{ currentDate }}</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-sm text-muted-foreground">Bonjour,</span>
            <span class="text-sm font-medium text-foreground">{{ firstName }}</span>
        </div>
    </header>
</template>
