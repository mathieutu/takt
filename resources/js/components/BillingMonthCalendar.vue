<script setup lang="ts">
import { computed } from 'vue'
import { coverageLabel, daysInMonth, formatDate, parseMonth } from '@/utils/date'

type EntryData = { coverage: number, title: string, description: string }

const props = defineProps<{
  month: string,
  entries: Record<string, EntryData>,
  holidays: Map<string, string>,
}>()

const days = computed(() => daysInMonth(parseMonth(props.month)))

const isOff = (date: string, isWeekend: boolean): boolean => isWeekend || props.holidays.has(date)

const cellClasses = (date: string, isWeekend: boolean): string => {
  const coverage = props.entries[date]?.coverage ?? 0
  if (coverage >= 100) return 'bg-primary/25'
  if (coverage > 0) return 'bg-primary/10'
  return isOff(date, isWeekend) ? 'bg-elevated/80' : ''
}

const hasTooltip = (date: string): boolean =>
  Boolean(props.entries[date]?.title || props.entries[date]?.description || props.holidays.has(date))
</script>

<template>
  <div class="flex w-full gap-0.5 overflow-x-auto">
    <div v-for="day in days" :key="day.date" class="flex min-w-6 flex-1 flex-col items-center gap-0.5">
      <span class="text-[9px] leading-none text-muted">{{ day.letter }}</span>
      <span class="text-[10px] font-medium leading-none">{{ day.n }}</span>
      <UTooltip :disabled="!hasTooltip(day.date)" :ui="{ content: 'flex-col items-start h-auto py-2 max-w-64' }">
        <div
          class="flex h-7 w-full items-center justify-center rounded text-[10px] font-semibold text-primary"
          :class="cellClasses(day.date, day.isWeekend)"
        >
          {{ entries[day.date]?.coverage ? coverageLabel(entries[day.date]!.coverage) : '' }}
        </div>
        <template #content>
          <p class="text-xs font-medium">
            {{ formatDate(day.date) }}
            <template v-if="entries[day.date]?.coverage">
              · {{ coverageLabel(entries[day.date]!.coverage) }} j
            </template>
          </p>
          <p v-if="holidays.get(day.date)" class="mt-1 text-xs text-muted">{{ holidays.get(day.date) }}</p>
          <p v-if="entries[day.date]?.title" class="mt-1 text-xs">{{ entries[day.date]!.title }}</p>
          <p v-if="entries[day.date]?.description" class="mt-0.5 text-xs text-muted whitespace-pre-line">
            {{ entries[day.date]!.description }}
          </p>
        </template>
      </UTooltip>
    </div>
  </div>
</template>
