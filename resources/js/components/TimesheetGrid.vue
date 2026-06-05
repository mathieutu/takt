<script setup lang="ts">
import type { Day } from '@/utils/date.ts'
import { Link } from '@inertiajs/vue3'
import { coverageLabel, formatDays, TODAY } from '@/utils/date.ts'
import { formatCurrency } from '@/utils/number.ts'
import { show as billingShow } from '@/wayfinder/routes/projects/billing'

type EntryData = { coverage: number, title: string, description: string }

export type GridProject = {
  id: string,
  name: string,
  client: { name: string },
  entries: Record<string, EntryData>,
  days?: number,
  revenue?: number,
  deleted_at?: string | null,
}

const { holidays } = defineProps<{
  days: Day[],
  holidays: Map<string, string>,
  projects: GridProject[],
}>()

const emit = defineEmits<{
  cellClick: [projectId: string, date: string],
  actionClick: [projectId: string, date: string],
}>()

function getCellClasses(project: GridProject, day: Day): string[] {
  const coverage = project.entries[day.date]?.coverage ?? 0
  const isDeleted = !!project.deleted_at
  const isHolidayOrWeekend = holidays.has(day.date) || day.isWeekend
  const isToday = day.date === TODAY
  const classes = []

  // Cursor
  classes.push(isDeleted ? 'cursor-disabled' : 'cursor-pointer')

  if (isToday) {
    classes.push('border-primary/50')
  }

  if (isDeleted) {
    classes.push('pointer-events-none')
  }

  // Background
  if (coverage >= 100) {
    return [...classes, 'bg-primary/25 hover:bg-primary/30']
  }

  if (coverage > 0 && coverage < 100) {
    return [...classes, 'bg-primary/10 hover:bg-primary/15']
  }

  if (isHolidayOrWeekend) {
    classes.push('bg-elevated')
  }

  if (coverage === 0) {
    classes.push('hover:bg-elevated/60')
  }

  return classes
}
</script>

<template>
  <table class="border-separate border-spacing-0" style="table-layout: fixed; width: max-content; min-width: 100%;">
    <colgroup>
      <col style="width: 220px; min-width: 220px;" />
      <col v-for="day in days" :key="day.n" style="width: 56px; min-width: 56px;" />
    </colgroup>
    <thead>
      <tr>
        <th class="sticky left-0 top-0 z-30 border border-default bg-default px-3 py-2 text-left text-xs font-medium text-muted">
          Project
        </th>
        <th
          v-for="(day, index) in days"
          :id="`day-col-${day.date}`"
          :key="day.n"
          class="sticky top-0 z-10 border-b border-r border-t border-default px-0 py-1.5 text-center"
          :class="[
            holidays.has(day.date) || day.isWeekend ? 'bg-elevated' : 'bg-default',
            day.date === TODAY ? 'border-primary/50' : '',
            days[index + 1]?.date === TODAY ? 'border-r-primary/50' : '',
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
      <tr v-if="!projects.length">
        <td :colspan="days.length + 1" class="px-4 py-8 text-center text-sm text-muted">
          No projects available.
        </td>
      </tr>
      <tr v-for="project in projects" :key="project.id" class="group/row">
        <td class="sticky left-0 z-10 border-b border-r border-l border-default bg-default px-3 py-2">
          <div class="flex items-center gap-1 min-w-0">
            <UTooltip v-if="project.deleted_at" text="Archived">
              <UIcon name="i-lucide-archive" class="w-3.5 h-3.5 shrink-0 text-muted" />
            </UTooltip>
            <div class="truncate text-sm font-medium flex-1" :class="project.deleted_at ? 'text-muted' : 'text-default'">{{ project.name }}</div>
            <Link
              :href="billingShow(project)"
              class="shrink-0 text-muted hover:text-default transition-colors"
              title="Billing"
            >
              <UIcon name="i-lucide-receipt-text" class="w-3.5 h-3.5" />
            </Link>
          </div>
          <div class="truncate text-xs text-muted flex items-center gap-1">
            <span class="flex-1">{{ project.client.name }}</span>
            <span v-if="project.days !== undefined">
              {{ formatDays(project.days) }}
              <template v-if="project.revenue !== undefined">({{ formatCurrency(project.revenue) }})</template>
            </span>
          </div>
        </td>
        <td
          v-for="(day, index) in days"
          :key="day.date"
          class="group/cell h-13 relative border-b border-r border-default transition-colors select-none overflow-hidden"
          :class="[getCellClasses(project, day), days[index + 1]?.date === TODAY ? 'border-r-primary/50' : '']"
          @click="!project.deleted_at ? emit('cellClick', project.id, day.date) : undefined"
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
            v-if="!project.deleted_at || (project.entries[day.date]?.title || project.entries[day.date]?.description)"
            :text="project.deleted_at ? 'View details' : 'Edit entry'"
          >
            <button
              type="button"
              class="absolute right-0.5 top-0.5 flex h-5 w-5 items-center justify-center rounded opacity-0 transition-opacity hover:bg-primary/25 group-hover/cell:opacity-100"
              @click.stop="emit('actionClick', project.id, day.date)"
            >
              <UIcon :name="project.deleted_at ? 'i-lucide-eye' : 'i-lucide-pencil'" class="h-3 w-3 text-primary" />
            </button>
          </UTooltip>
        </td>
      </tr>
    </tbody>
  </table>
</template>
