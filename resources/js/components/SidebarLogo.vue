<script setup lang="ts">
import { Clock } from 'lucide-vue-next'
</script>

<template>
    <div class="relative flex items-center justify-center border-r border-border bg-sidebar">
        <a
            href="/dashboard"
            class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary"
        >
            <Clock class="h-5 w-5 text-primary-foreground" />
        </a>

        <div class="absolute bottom-0 left-1/2 h-px w-8 -translate-x-1/2 bg-border" />
    </div>
</template>
