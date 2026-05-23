<script setup lang="ts">
import type { PageProps } from '../types'
import { Link, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { coverageLabel, daysInMonth, formatDays, formatMonthName, offsetMonth, parseMonth, TODAY } from '@/utils/timesheet'
import { login } from '@/wayfinder/routes'
import { apply as shareApply } from '@/wayfinder/routes/share'
import AppLayout from '../layouts/AppLayout.vue'

// @ts-expect-error Inertia v3 runtime accepts false to disable layout; types are incomplete
defineOptions({ layout: false })

const props = withDefaults(defineProps<{
  month?: string,
  shareToken?: string,
  projectName?: string,
  clientName?: string,
  projectId?: string,
  entries?: Record<string, EntryData>,
  holidays?: Record<string, string>,
}>(), {
  month: () => new Date().toISOString().slice(0, 7),
  shareToken: '',
  projectName: '',
  clientName: '',
  projectId: '',
  entries: () => ({}),
  holidays: () => ({}),
})
type EntryData = { coverage: number, title: string, description: string }
type ViewingEntry = { date: string } & EntryData

const page = usePage<PageProps>()
const isAuthenticated = computed(() => !!page.props.auth?.user)

const displayYear = computed(() => parseMonth(props.month)[0])
const displayMonth = computed(() => parseMonth(props.month)[1])
const holidays = computed(() => new Map(Object.entries(props.holidays)))

const prevUrl = computed(() => shareApply.url(props.shareToken, { query: { month: offsetMonth(props.month, -1) } }))
const nextUrl = computed(() => shareApply.url(props.shareToken, { query: { month: offsetMonth(props.month, 1) } }))

const viewReportOpen = ref(false)
const viewingEntry = ref<ViewingEntry | null>(null)

const days = computed(() => daysInMonth(displayYear.value, displayMonth.value))

const monthDays = computed(() =>
  Object.values(props.entries).reduce((s, e) => s + e.coverage / 100, 0),
)

function openView(date: string) {
  viewingEntry.value = { date, ...props.entries[date]! }
  viewReportOpen.value = true
}

function entryDateLabel(date: string): string {
  const d = new Date(`${date}T00:00:00`)
  return `${d.getDate()} ${formatMonthName(d.getFullYear(), d.getMonth() + 1)}`
}
</script>

<template>
  <AppLayout v-if="isAuthenticated">
    <div class="flex h-[calc(100vh-3.5rem)] flex-col overflow-hidden px-3 py-4 md:px-6 md:py-6">
      <div class="mb-4 flex items-center justify-between">
        <div>
          <h2 class="text-sm font-semibold text-default">{{ projectName }}</h2>
          <p class="text-xs text-muted">{{ clientName }} — CRA partagé</p>
        </div>
        <div class="flex shrink-0 items-center gap-2">
          <UButton :as="Link" :href="prevUrl" icon="i-lucide-chevron-left" color="neutral" variant="ghost" size="xs" />
          <span class="min-w-35 text-center text-sm font-medium text-default">
            {{ formatMonthName(displayYear, displayMonth) }} {{ displayYear }}
          </span>
          <UButton :as="Link" :href="nextUrl" icon="i-lucide-chevron-right" color="neutral" variant="ghost" size="xs" />
        </div>
      </div>
      <div class="flex-1 min-h-0">
        <div class="overflow-y-auto rounded-md border border-default max-h-full">
          <table class="border-collapse" style="table-layout: fixed; width: max-content; min-width: 100%;">
            <colgroup>
              <col style="width: 160px; min-width: 160px;" />
              <col v-for="day in days" :key="day.n" style="width: 56px; min-width: 56px;" />
            </colgroup>
            <thead>
              <tr>
                <th class="sticky left-0 top-0 z-30 border-b border-r border-default bg-default px-3 py-2 text-left text-xs font-medium text-muted">
                  Projet
                </th>
                <th
                  v-for="day in days" :key="day.n"
                  class="sticky top-0 z-10 border-b border-r border-default px-0 py-1.5 text-center"
                  :class="[
                    holidays.has(day.date) || day.isWeekend ? 'bg-elevated' : 'bg-default',
                    day.date === TODAY ? 'bg-primary/15!' : '',
                  ]"
                  :title="holidays.get(day.date)"
                >
                  <div class="text-xs font-semibold leading-none" :class="day.date === TODAY ? 'text-primary' : 'text-default'">
                    {{ day.n }}
                  </div>
                  <div class="mt-0.5 text-[10px] leading-none" :class="day.date === TODAY ? 'text-primary' : 'text-muted'">
                    {{ day.letter }}
                  </div>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr class="group/row">
                <td class="sticky left-0 z-10 border-b border-r border-default bg-default px-3 py-2">
                  <div class="truncate text-sm font-medium text-default">{{ projectName }}</div>
                  <div class="truncate text-xs text-muted">{{ clientName }}</div>
                </td>
                <td
                  v-for="day in days" :key="day.date"
                  class="group/cell relative border-b border-r border-default select-none overflow-hidden cursor-default"
                  style="height: 52px;"
                  :class="[
                    !entries[day.date] && (holidays.has(day.date) || day.isWeekend) ? 'bg-elevated' : '',
                    (entries[day.date]?.coverage ?? 0) >= 100 ? 'bg-primary/25' : '',
                    (entries[day.date]?.coverage ?? 0) > 0 && (entries[day.date]?.coverage ?? 0) < 100 ? 'bg-primary/10' : '',
                    day.date === TODAY && !entries[day.date] ? 'ring-1 ring-inset ring-primary/50' : '',
                  ]"
                >
                  <span
                    v-if="entries[day.date]?.coverage"
                    class="absolute bottom-2 left-0 right-0 text-center text-sm font-bold text-primary"
                  >
                    {{ coverageLabel(entries[day.date]!.coverage) }}
                  </span>
                  <button
                    v-if="entries[day.date] && (entries[day.date]!.title || entries[day.date]!.description)"
                    type="button"
                    class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded opacity-0 transition-opacity hover:bg-primary/25 group-hover/cell:opacity-100"
                    @click.stop="openView(day.date)"
                  >
                    <UIcon name="i-lucide-eye" class="h-3 w-3 text-primary" />
                  </button>
                  <span
                    v-if="entries[day.date]?.title || entries[day.date]?.description"
                    class="absolute left-1.5 top-1.5 h-1.5 w-1.5 rounded-full bg-primary/70 transition-opacity group-hover/cell:opacity-0"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="mt-auto shrink-0 pt-3 flex items-center justify-between">
        <div class="hidden items-center gap-5 sm:flex">
          <div class="flex items-center gap-1.5">
            <span class="h-3 w-3 rounded-sm border border-default bg-primary/25" /><span class="text-xs text-muted">1 jour</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span class="h-3 w-3 rounded-sm border border-default bg-primary/10" /><span class="text-xs text-muted">½ jour</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span class="h-3 w-3 rounded-sm border border-default bg-elevated" /><span class="text-xs text-muted">Week-ends et jours fériés</span>
          </div>
        </div>
        <span class="text-sm text-muted ml-auto">{{ formatDays(monthDays) }} ce mois</span>
      </div>
    </div>
  </AppLayout>

  <div v-else class="flex h-screen flex-col overflow-hidden bg-default">
    <header class="flex shrink-0 items-center justify-between border-b border-default px-4 py-3 md:px-6">
      <div>
        <p class="text-sm font-semibold text-default">{{ projectName }}</p>
        <p class="text-xs text-muted">{{ clientName }}</p>
      </div>
      <UButton :as="Link" :href="login().url" label="Se connecter" size="sm" />
    </header>
    <div class="flex flex-1 overflow-hidden">
      <main class="flex flex-1 flex-col overflow-hidden px-3 py-4 md:px-6 md:py-6">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-default">
            {{ formatMonthName(displayYear, displayMonth) }} {{ displayYear }}
          </h2>
          <div class="flex shrink-0 items-center gap-2">
            <UButton :as="Link" :href="prevUrl" icon="i-lucide-chevron-left" color="neutral" variant="ghost" size="xs" />
            <UButton :as="Link" :href="nextUrl" icon="i-lucide-chevron-right" color="neutral" variant="ghost" size="xs" />
          </div>
        </div>
        <div class="flex-1 overflow-auto rounded-md border border-default">
          <table class="border-collapse" style="table-layout: fixed; width: max-content; min-width: 100%;">
            <colgroup>
              <col style="width: 160px; min-width: 160px;" />
              <col v-for="day in days" :key="day.n" style="width: 56px; min-width: 56px;" />
            </colgroup>
            <thead>
              <tr>
                <th class="sticky left-0 top-0 z-30 border-b border-r border-default bg-default px-3 py-2 text-left text-xs font-medium text-muted">
                  Projet
                </th>
                <th
                  v-for="day in days" :key="day.n"
                  class="sticky top-0 z-10 border-b border-r border-default px-0 py-1.5 text-center"
                  :class="[
                    holidays.has(day.date) || day.isWeekend ? 'bg-elevated' : 'bg-default',
                    day.date === TODAY ? 'bg-primary/15!' : '',
                  ]"
                  :title="holidays.get(day.date)"
                >
                  <div class="text-xs font-semibold leading-none" :class="day.date === TODAY ? 'text-primary' : 'text-default'">
                    {{ day.n }}
                  </div>
                  <div class="mt-0.5 text-[10px] leading-none" :class="day.date === TODAY ? 'text-primary' : 'text-muted'">
                    {{ day.letter }}
                  </div>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr class="group/row">
                <td class="sticky left-0 z-10 border-b border-r border-default bg-default px-3 py-2">
                  <div class="truncate text-sm font-medium text-default">{{ projectName }}</div>
                  <div class="truncate text-xs text-muted">{{ clientName }}</div>
                </td>
                <td
                  v-for="day in days" :key="day.date"
                  class="group/cell relative border-b border-r border-default select-none overflow-hidden cursor-default"
                  style="height: 52px;"
                  :class="[
                    !entries[day.date] && (holidays.has(day.date) || day.isWeekend) ? 'bg-elevated' : '',
                    (entries[day.date]?.coverage ?? 0) >= 100 ? 'bg-primary/25' : '',
                    (entries[day.date]?.coverage ?? 0) > 0 && (entries[day.date]?.coverage ?? 0) < 100 ? 'bg-primary/10' : '',
                    day.date === TODAY && !entries[day.date] ? 'ring-1 ring-inset ring-primary/50' : '',
                  ]"
                >
                  <span
                    v-if="entries[day.date]?.coverage"
                    class="absolute bottom-2 left-0 right-0 text-center text-sm font-bold text-primary"
                  >
                    {{ coverageLabel(entries[day.date]!.coverage) }}
                  </span>
                  <button
                    v-if="entries[day.date] && (entries[day.date]!.title || entries[day.date]!.description)"
                    type="button"
                    class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded opacity-0 transition-opacity hover:bg-primary/25 group-hover/cell:opacity-100"
                    @click.stop="openView(day.date)"
                  >
                    <UIcon name="i-lucide-eye" class="h-3 w-3 text-primary" />
                  </button>
                  <span
                    v-if="entries[day.date]?.title || entries[day.date]?.description"
                    class="absolute left-1.5 top-1.5 h-1.5 w-1.5 rounded-full bg-primary/70 transition-opacity group-hover/cell:opacity-0"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="mt-3 flex items-center justify-between">
          <div class="hidden items-center gap-5 sm:flex">
            <div class="flex items-center gap-1.5">
              <span class="h-3 w-3 rounded-sm border border-default bg-primary/25" /><span class="text-xs text-muted">1 jour</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="h-3 w-3 rounded-sm border border-default bg-primary/10" /><span class="text-xs text-muted">½ jour</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="h-3 w-3 rounded-sm border border-default bg-elevated" /><span class="text-xs text-muted">Week-ends et jours fériés</span>
            </div>
          </div>
          <span class="text-sm text-muted ml-auto">{{ formatDays(monthDays) }} ce mois</span>
        </div>
      </main>
    </div>
  </div>

  <UModal v-model:open="viewReportOpen" :title="viewingEntry ? entryDateLabel(viewingEntry.date) : ''">
    <template #body>
      <div class="space-y-4">
        <div v-if="viewingEntry?.title" class="flex flex-col gap-1">
          <p class="text-xs font-medium text-muted">Titre</p>
          <p class="text-sm">{{ viewingEntry.title }}</p>
        </div>
        <div v-if="viewingEntry?.description" class="flex flex-col gap-1">
          <p class="text-xs font-medium text-muted">Description</p>
          <p class="text-sm whitespace-pre-wrap">{{ viewingEntry.description }}</p>
        </div>
      </div>
    </template>
    <template #footer="{ close }">
      <div class="flex justify-end">
        <UButton label="Fermer" color="neutral" variant="outline" @click="close" />
      </div>
    </template>
  </UModal>
</template>
