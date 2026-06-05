<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { project as shareProject } from '@/wayfinder/routes/share'

defineProps<{
  shareToken: string,
  clientName: string,
  projects: Array<{ id: string, name: string }>,
}>()
</script>

<template>
  <div class="px-6 py-8">
    <div class="mx-auto max-w-xl space-y-6">
      <div>
        <h1 class="text-lg font-semibold">{{ clientName }}</h1>
        <p class="text-sm text-muted">{{ projects.length }} shared project{{ projects.length !== 1 ? 's' : '' }}</p>
      </div>

      <div class="space-y-2">
        <Link
          v-for="p in projects"
          :key="p.id"
          :href="shareProject({ share: shareToken, project: p.id }).url"
          class="flex items-center justify-between rounded-lg border border-default bg-elevated px-4 py-3 transition-colors hover:bg-primary/5"
        >
          <span class="text-sm font-medium">{{ p.name }}</span>
          <UIcon name="i-lucide-chevron-right" class="h-4 w-4 text-muted" />
        </Link>
      </div>
    </div>
  </div>
</template>
