<script setup lang="ts">
import { computed, ref } from 'vue'
import { daysInMonth, formatDays, formatMonthName, parseMonth } from '@/utils/date.ts'
import TimesheetGrid from '@/Components/TimesheetGrid.vue'

type EntryData = { coverage: number, title: string, description: string }
type ViewingEntry = { date: string } & EntryData

const props = defineProps<{
  projectName: string,
  clientName: string,
  month: string,
  prevUrl: string,
  nextUrl: string,
  backUrl?: string,
  entries: Record<string, EntryData>,
  holidays: Record<string, string>,
}>()

const parsed = computed(() => parseMonth(props.month))
const displayYear = computed(() => parsed.value.year)
const displayMonth = computed(() => parsed.value.month)
const days = computed(() => daysInMonth(parsed.value))
const holidaysMap = computed(() => new Map(Object.entries(props.holidays)))
const monthDays = computed(() =>
  Object.values(props.entries).reduce((s, e) => s + e.coverage / 100, 0),
)

const gridProjects = computed(() => [{
  id: 'shared',
  name: props.projectName,
  client: { name: props.clientName },
  entries: props.entries,
}])

const viewReportOpen = ref(false)
const viewingEntry = ref<ViewingEntry | null>(null)

function openView(_projectId: string, date: string) {
  if (!props.entries[date]?.title && !props.entries[date]?.description) { return }
  viewingEntry.value = { date, ...props.entries[date]! }
  viewReportOpen.value = true
}

function entryDateLabel(date: string): string {
  const d = new Date(`${date}T00:00:00`)
  return `${d.getDate()} ${formatMonthName(d.getFullYear(), d.getMonth() + 1)}`
}
</script>

<template>
  <div class="flex flex-1 flex-col overflow-hidden px-3 py-4 md:px-6 md:py-6">
    <div class="mb-4 flex items-center justify-between">
      <div>
        <div class="flex items-center gap-2">
          <UButton
            v-if="backUrl"
            :href="backUrl"
            icon="i-lucide-chevron-left"
            color="neutral"
            variant="ghost"
            size="xs"
          />
          <h2 class="text-sm font-semibold text-default">{{ projectName }}</h2>
        </div>
        <p class="text-xs text-muted">{{ clientName }}</p>
      </div>
      <div class="flex shrink-0 items-center gap-2">
        <UButton :href="prevUrl" icon="i-lucide-chevron-left" color="neutral" variant="ghost" size="xs" />
        <span class="min-w-35 text-center text-sm font-medium text-default">
          {{ formatMonthName(displayYear, displayMonth) }} {{ displayYear }}
        </span>
        <UButton :href="nextUrl" icon="i-lucide-chevron-right" color="neutral" variant="ghost" size="xs" />
      </div>
    </div>

    <div class="flex-1 min-h-0">
      <div class="overflow-y-auto rounded-md border border-default max-h-full">
        <TimesheetGrid
          :days="days"
          :holidays="holidaysMap"
          :projects="gridProjects"
          :readonly="true"
          @actionClick="openView"
        />
      </div>
    </div>

    <div class="mt-auto shrink-0 pt-3 flex items-center justify-between">
      <div class="hidden items-center gap-5 sm:flex">
        <div class="flex items-center gap-1.5">
          <span class="h-3 w-3 rounded-sm border border-default bg-primary/25" /><span class="text-xs text-muted">1 day</span>
        </div>
        <div class="flex items-center gap-1.5">
          <span class="h-3 w-3 rounded-sm border border-default bg-primary/10" /><span class="text-xs text-muted">½ day</span>
        </div>
        <div class="flex items-center gap-1.5">
          <span class="h-3 w-3 rounded-sm border border-default bg-elevated" /><span class="text-xs text-muted">Weekends and holidays</span>
        </div>
      </div>
      <span class="text-sm text-muted ml-auto">{{ formatDays(monthDays) }} this month</span>
    </div>
  </div>

  <UModal v-model:open="viewReportOpen" :title="viewingEntry ? entryDateLabel(viewingEntry.date) : ''">
    <template #body>
      <div class="space-y-4">
        <div v-if="viewingEntry?.title" class="flex flex-col gap-1">
          <p class="text-xs font-medium text-muted">Title</p>
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
        <UButton label="Close" color="neutral" variant="outline" @click="close" />
      </div>
    </template>
  </UModal>
</template>
