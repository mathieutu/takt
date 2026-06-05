<script setup lang="ts">
import type { PageProps } from '../types'
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import SharedClientView from '@/Components/SharedClientView.vue'
import SharedTimesheetView from '@/Components/SharedTimesheetView.vue'
import { login } from '@/wayfinder/routes'
import AppLayout from '../layouts/AppLayout.vue'

// @ts-expect-error Inertia v3 runtime accepts false to disable layout; types are incomplete
defineOptions({ layout: false })

type EntryData = { coverage: number, title: string, description: string }

const props = defineProps<{
  type: 'project' | 'client',
  shareToken: string,
  // Project view
  projectName?: string,
  clientName?: string,
  projectId?: string,
  month?: string,
  prevUrl?: string,
  nextUrl?: string,
  backUrl?: string,
  entries?: Record<string, EntryData>,
  holidays?: Record<string, string>,
  // Client view
  projects?: Array<{ id: string, name: string }>,
}>()

const page = usePage<PageProps>()
const isAuthenticated = computed(() => !!page.props.auth?.user)
</script>

<template>
  <AppLayout v-if="isAuthenticated">
    <div class="flex h-[calc(100vh-3.5rem)] flex-col overflow-hidden">
      <SharedTimesheetView
        v-if="type === 'project'"
        :projectName="projectName!"
        :clientName="clientName!"
        :month="month!"
        :prevUrl="prevUrl!"
        :nextUrl="nextUrl!"
        :backUrl="backUrl"
        :entries="entries ?? {}"
        :holidays="holidays ?? {}"
      />
      <SharedClientView
        v-else
        :shareToken="shareToken"
        :clientName="clientName!"
        :projects="projects ?? []"
      />
    </div>
  </AppLayout>

  <div v-else class="flex h-screen flex-col overflow-hidden bg-default">
    <header class="flex shrink-0 items-center justify-between border-b border-default px-4 py-3 md:px-6">
      <div>
        <p class="text-sm font-semibold text-default">{{ type === 'project' ? projectName : clientName }}</p>
        <p v-if="type === 'project'" class="text-xs text-muted">{{ clientName }}</p>
      </div>
      <UButton :as="Link" :href="login().url" label="Sign in" size="sm" />
    </header>

    <div class="flex flex-1 overflow-hidden">
      <main class="flex flex-1 flex-col overflow-hidden">
        <SharedTimesheetView
          v-if="type === 'project'"
          :projectName="projectName!"
          :clientName="clientName!"
          :month="month!"
          :prevUrl="prevUrl!"
          :nextUrl="nextUrl!"
          :backUrl="backUrl"
          :entries="entries ?? {}"
          :holidays="holidays ?? {}"
        />
        <SharedClientView
          v-else
          :shareToken="shareToken"
          :clientName="clientName!"
          :projects="projects ?? []"
        />
      </main>
    </div>
  </div>
</template>
