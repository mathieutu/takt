<script setup lang="ts">
import type { PageProps } from '../types'
import { usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Header from '../components/Header.vue'
import Sidebar from '../components/Sidebar.vue'
import { useFlash } from '../composables/useFlash'

const page = usePage<PageProps>()
const user = computed(() => page.props.auth?.user)
const sidebarOpen = ref(false)

useFlash()
</script>

<template>
  <UApp>
    <div class="flex h-screen overflow-hidden bg-default">
      <Header :user="user" @toggleSidebar="sidebarOpen = !sidebarOpen" />
      <Sidebar v-model:open="sidebarOpen" :user="user" />
      <main class="mt-14 md:ml-14 flex-1 overflow-y-auto">
        <slot />
      </main>
    </div>
  </UApp>
</template>
