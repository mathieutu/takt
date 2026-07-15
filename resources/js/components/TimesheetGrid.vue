<script setup lang="ts">
import type { Day } from '@/utils/date.ts'
import { useTemplateRef } from 'vue'
import { useTimesheetKeyboard } from '@/composables/useTimesheetKeyboard.ts'
import { useToday } from '@/composables/useToday.ts'
import { coverageLabel, formatDays } from '@/utils/date.ts'
import { formatCurrency } from '@/utils/number.ts'
import { show as billingShow } from '@/wayfinder/routes/clients/billing'

type EntryData = { coverage: number, title: string, description: string }

export type GridProject = {
  id: string,
  name: string,
  client: { id: string, name: string },
  entries: Record<string, EntryData>,
  days?: number,
  revenue?: number,
  is_archived?: boolean,
}

const { holidays, days, projects } = defineProps<{
  days: Day[],
  holidays: Map<string, string>,
  projects: GridProject[],
}>()

const emit = defineEmits<{
  cellClick: [projectId: string, date: string],
  actionClick: [projectId: string, date: string],
  setCoverage: [projectId: string, date: string, coverage: number],
  nextMonth: [],
  prevMonth: [],
}>()

const tableRef = useTemplateRef<HTMLTableElement>('table-ref')
useTimesheetKeyboard(tableRef, () => days, () => projects, emit)
const { isToday } = useToday()

const getCellClasses = (project: GridProject, day: Day): Array<string | boolean> => {
  const coverage = project.entries[day.date]?.coverage ?? 0
  const isDeleted = !!project.is_archived
  const isHolidayOrWeekend = holidays.has(day.date) || day.isWeekend
  const isTodayDay = isToday(day.date)

  const base = [
    isDeleted ? 'cursor-disabled' : 'cursor-pointer',
    isTodayDay && 'border-primary/50',
  ]

  if (coverage >= 100) return [...base, 'bg-primary/25 hover:bg-primary/30']
  if (coverage > 0) return [...base, 'bg-primary/10 hover:bg-primary/15']

  return [
    ...base,
    isHolidayOrWeekend && 'bg-elevated',
    'hover:bg-elevated/60',
  ]
}
</script>

<template>
  <table ref="table-ref" class="border-separate border-spacing-0" style="table-layout: fixed; width: max-content; min-width: 100%;">
    <colgroup>
      <col style="width: 160px; min-width: 160px;" />
      <col v-for="day in days" :key="day.n" style="width: 56px; min-width: 56px;" />
    </colgroup>
    <thead>
      <tr>
        <th class="sticky top-0 z-10 sm:left-0 sm:z-30 border border-default bg-default px-3 py-2 text-left text-xs font-medium text-muted">
          Projet
        </th>
        <th
          v-for="(day, index) in days"
          :id="`day-col-${day.date}`"
          :key="day.n"
          class="sticky top-0 z-10 border-b border-r border-t border-default px-0 py-1.5 text-center"
          :class="[
            holidays.has(day.date) || day.isWeekend ? 'bg-elevated' : 'bg-default',
            isToday(day.date) ? 'border-primary/50' : '',
            isToday(days[index + 1]?.date) ? 'border-r-primary/50' : '',
          ]"
          :title="holidays.get(day.date)"
        >
          <div class="text-xs font-semibold leading-none" :class="isToday(day.date) ? 'text-primary' : 'text-default'">
            {{ day.n }}
          </div>
          <div class="mt-0.5 text-[10px] leading-none" :class="isToday(day.date) ? 'text-primary' : 'text-muted'">
            {{ day.letter }}
          </div>
        </th>
      </tr>
    </thead>
    <tbody>
      <tr v-if="!projects.length">
        <td :colspan="days.length + 1" class="px-4 py-8 text-center text-sm text-muted">
          Aucun projet disponible.
        </td>
      </tr>
      <tr v-for="(project, projectIndex) in projects" :key="project.id" class="group/row">
        <td class="sm:sticky sm:left-0 sm:z-10 overflow-hidden border-b border-r border-l border-default bg-default px-3 py-2">
          <div class="flex items-center gap-1 min-w-0">
            <UTooltip v-if="project.is_archived" text="Archivé">
              <UIcon name="i-lucide-archive" class="w-3.5 h-3.5 shrink-0 text-muted" />
            </UTooltip>
            <div class="truncate text-sm font-medium flex-1" :class="project.is_archived ? 'text-muted' : 'text-default'">{{ project.name }}</div>
          </div>
          <div class="flex items-center gap-1 min-w-0 text-xs text-muted">
            <span class="truncate flex-1 min-w-0">{{ project.client.name }}</span>
            <span v-if="project.days" class="hidden sm:inline shrink-0">
              {{ formatDays(project.days) }}
              <template v-if="project.revenue">({{ formatCurrency(project.revenue) }})</template>
            </span>
            <UTooltip text="Facturation" class="shrink-0">
              <UButton
                :href="billingShow(project.client)"
                icon="i-lucide-receipt-text"
                color="neutral"
                variant="ghost"
                size="xs"
              />
            </UTooltip>
          </div>
        </td>
        <td
          v-for="(day, index) in days"
          :key="day.date"
          tabindex="0"
          :data-row="projectIndex"
          :data-col="index"
          :data-project-id="project.id"
          :data-date="day.date"
          :data-deleted="project.is_archived ? true : undefined"
          class="group/cell h-13 relative border-b border-r border-default transition-colors select-none overflow-hidden focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary/50"
          :class="[getCellClasses(project, day), isToday(days[index + 1]?.date) ? 'border-r-primary/50' : '']"
          @mouseenter="($event.target as HTMLElement).focus()"
          @click="!project.is_archived ? emit('cellClick', project.id, day.date) : undefined"
          @contextmenu.prevent="!project.is_archived ? emit('setCoverage', project.id, day.date, 0) : undefined"
        >
          <span
            v-if="project.entries[day.date]?.coverage"
            class="absolute inset-0 flex justify-center items-center text-sm font-bold text-primary"
          >
            {{ coverageLabel(project.entries[day.date]!.coverage) }}
          </span>
          <span
            v-if="project.entries[day.date]?.title || project.entries[day.date]?.description"
            class="absolute right-1.5 top-1.5 h-1.5 w-1.5 rounded-full bg-primary/70 transition-opacity group-hover/cell:opacity-0"
          />
          <UTooltip
            v-if="!project.is_archived || (project.entries[day.date]?.title || project.entries[day.date]?.description)"
            :text="project.is_archived ? 'Voir les détails' : 'Modifier l\'entrée'"
          >
            <button
              type="button"
              class="absolute right-0.5 top-0.5 flex h-5 w-5 items-center justify-center rounded opacity-0 transition-opacity hover:bg-primary/25 group-hover/cell:opacity-100"
              @click.stop="emit('actionClick', project.id, day.date)"
            >
              <UIcon :name="project.is_archived ? 'i-lucide-eye' : 'i-lucide-pencil'" class="h-3 w-3 text-primary" />
            </button>
          </UTooltip>
        </td>
      </tr>
    </tbody>
  </table>
</template>
