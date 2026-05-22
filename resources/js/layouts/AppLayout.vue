<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { PageProps } from '../types'
import Header from '../components/Header.vue'
import Sidebar from '../components/Sidebar.vue'

const page = usePage<PageProps>()
const user = computed(() => page.props.auth?.user)
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-background">
        <Header :user="user" />
        <Sidebar :user="user" />
        <main class="mt-14 md:ml-14 flex-1 overflow-y-auto">
            <slot />
        </main>
    </div>
</template>
