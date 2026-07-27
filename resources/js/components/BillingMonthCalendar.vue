<script setup lang="ts">
import { computed } from 'vue'
import { coverageLabel, daysInMonth, formatDate, parseMonth } from '@/utils/date'

type EntryData = { coverage: number, title: string, description: string, billable: boolean }

const props = defineProps<{
  month: string,
  entries: Record<string, EntryData>,
  holidays: Map<string, string>,
}>()

// Precomputed once per day so the template reads plain fields instead of repeatedly
// re-deriving coverage/status from entries+holidays for each cell.
const days = computed(() => daysInMonth(parseMonth(props.month)).map(day => {
  const entry = props.entries[day.date]
  const coverage = entry?.coverage ?? 0
  const nonBillable = coverage > 0 && entry?.billable === false
  const holidayName = props.holidays.get(day.date)

  // Coverage-based status (billable/non-billable) takes priority over the plain day-off
  // status, same as cellClass below — a worked weekend/holiday reads as "billable"/"non-billable",
  // not "Week-end". The holiday name itself is shown separately (see holidayName),
  // regardless of coverage, since "worked on a holiday" is worth surfacing on its own.
  const status = nonBillable
    ? { label: 'non facturé', class: 'text-violet-600/60 dark:text-violet-400' }
    : coverage > 0
      ? { label: 'facturé', class: 'text-primary' }
      : day.isWeekend
        ? { label: 'Week-end', class: 'text-muted' }
        : null

  const cellClass = nonBillable
    ? 'bg-violet-100 text-violet-600/60 dark:bg-violet-500/10 dark:text-violet-400'
    : coverage >= 100
      ? 'bg-primary/25 text-primary'
      : coverage > 0
        ? 'bg-primary/10 text-primary'
        : (day.isWeekend || holidayName) ? 'bg-elevated/80 text-muted' : 'text-muted'

  return { ...day, entry, coverage, holidayName, status, cellClass }
}))
</script>

<template>
  <div class="flex w-full gap-0.5 overflow-x-auto">
    <div v-for="day in days" :key="day.date" class="flex min-w-6 flex-1 flex-col items-center gap-0.5">
      <span class="text-[9px] leading-none text-muted">{{ day.letter }}</span>
      <span class="text-[10px] font-medium leading-none">{{ day.n }}</span>
      <UTooltip :delayDuration="0" :ui="{ content: 'flex-col items-start h-auto py-2 max-w-64' }">
        <div class="flex h-7 w-full items-center justify-center rounded text-[10px] font-semibold" :class="day.cellClass">
          {{ day.coverage ? coverageLabel(day.coverage) : '' }}
        </div>
        <template #content>
          <p class="text-xs font-medium">
            {{ formatDate(day.date) }}
          </p>
          <p v-if="day.status" class="mt-1 text-xs" :class="day.status.class">
            <template v-if="day.coverage">{{ coverageLabel(day.coverage) }} j </template>{{ day.status.label }}
          </p>
          <p v-if="day.holidayName" class="mt-1 text-xs text-muted">Férié · {{ day.holidayName }}</p>
          <p v-if="day.entry?.title" class="mt-1 text-xs">{{ day.entry.title }}</p>
          <p v-if="day.entry?.description" class="mt-0.5 text-xs text-muted whitespace-pre-line">
            {{ day.entry.description }}
          </p>
        </template>
      </UTooltip>
    </div>
  </div>
</template>
