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
}

defineProps<{
  days: Day[],
  holidays: Map<string, string>,
  projects: GridProject[],
  readonly?: boolean,
}>()

const emit = defineEmits<{
  cellClick: [projectId: string, date: string],
  actionClick: [projectId: string, date: string],
}>()
</script>

<template>
  <table class="border-separate border-spacing-0" style="table-layout: fixed; width: max-content; min-width: 100%;">
    <colgroup>
      <col style="width: 220px; min-width: 220px;" />
      <col v-for="day in days" :key="day.n" style="width: 56px; min-width: 56px;" />
    </colgroup>
    <thead>
      <tr>
        <th class="sticky left-0 top-0 z-30 border-b border-r border-default bg-default px-3 py-2 text-left text-xs font-medium text-muted">
          Project
        </th>
        <th
          v-for="day in days"
          :id="`day-col-${day.date}`"
          :key="day.n"
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
      <tr v-if="!projects.length">
        <td :colspan="days.length + 1" class="px-4 py-8 text-center text-sm text-muted">
          No projects available.
        </td>
      </tr>
      <tr v-for="project in projects" :key="project.id" class="group/row">
        <td class="sticky left-0 z-10 border-b border-r border-default bg-default px-3 py-2">
          <div class="flex items-center gap-1 min-w-0">
            <div class="truncate text-sm font-medium text-default flex-1">{{ project.name }}</div>
            <Link
              v-if="!readonly"
              :href="billingShow(project).url"
              class="shrink-0 text-muted hover:text-default transition-colors"
              title="Billing"
              @click.stop
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
          v-for="day in days"
          :key="day.date"
          class="group/cell h-13 relative border-b border-r border-default transition-colors select-none overflow-hidden"
          :class="{
            'cursor-pointer': !readonly,
            'cursor-default': readonly,
            'bg-elevated': !(project.entries[day.date]?.coverage) && (holidays.has(day.date) || day.isWeekend),
            'bg-primary/25': (project.entries[day.date]?.coverage ?? 0) >= 100,
            'hover:bg-primary/30': (project.entries[day.date]?.coverage ?? 0) >= 100 && !readonly,
            'bg-primary/10': (project.entries[day.date]?.coverage ?? 0) > 0 && (project.entries[day.date]?.coverage ?? 0) < 100,
            'hover:bg-primary/15': (project.entries[day.date]?.coverage ?? 0) > 0 && (project.entries[day.date]?.coverage ?? 0) < 100 && !readonly,
            'hover:bg-elevated/60': !(project.entries[day.date]?.coverage) && !readonly,
            'ring-1 ring-inset ring-primary/50': day.date === TODAY && !(project.entries[day.date]?.coverage),
          }"
          @click="!readonly ? emit('cellClick', project.id, day.date) : undefined"
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
            v-if="!readonly || (project.entries[day.date]?.title || project.entries[day.date]?.description)"
            :text="readonly ? 'View details' : 'Edit entry'"
          >
            <button
              type="button"
              class="absolute right-0.5 top-0.5 flex h-5 w-5 items-center justify-center rounded opacity-0 transition-opacity hover:bg-primary/25 group-hover/cell:opacity-100"
              @click.stop="emit('actionClick', project.id, day.date)"
            >
              <UIcon :name="readonly ? 'i-lucide-eye' : 'i-lucide-pencil'" class="h-3 w-3 text-primary" />
            </button>
          </UTooltip>
        </td>
      </tr>
    </tbody>
  </table>
</template>
