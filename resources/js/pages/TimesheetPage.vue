<script setup lang="ts">
import type { ErrorBag, Errors, FormComponentOptimisticCallback, PageProps as InertiaPageProps } from '@inertiajs/core'
import { Form, router } from '@inertiajs/vue3'
import { computed, nextTick, ref, watch } from 'vue'
import TimesheetGrid from '@/components/TimesheetGrid.vue'
import {
  daysInMonth,
  formatDays,
  formatMonthName,
  TODAY,
} from '@/utils/date.ts'
import { formatCurrency } from '@/utils/number.ts'
import { sync as syncEntries } from '@/wayfinder/routes/projects/entries'

type EntryData = { coverage: number, title: string, description: string }
type Project = {
  id: string,
  name: string,
  client: { name: string },
  daily_rate: number,
  entries: Record<string, EntryData>,
  deleted_at?: string | null,
}
type ActiveEntry = { projectId: string, date: string, isArchived: boolean } & EntryData

type Props = {
  current: { year: number, month: number },
  urls: { nextMonth: string, prevMonth: string },
  projects: Project[],
  holidays: Record<string, string>,
}
const props = defineProps<Props>()

const holidays = computed(() => new Map(Object.entries(props.holidays)))

const tableScrollRef = ref<HTMLElement | null>(null)
const activeEntry = ref<ActiveEntry | null>(null)

const centerTodayColumn = (behavior: ScrollBehavior = 'auto') => {
  const isCurrentMonth =
    props.current.year === new Date().getFullYear()
    && props.current.month === new Date().getMonth() + 1

  if (!isCurrentMonth || !tableScrollRef.value) {
    return
  }

  tableScrollRef.value.querySelector<HTMLElement>(`#day-col-${TODAY}`)
    ?.scrollIntoView({ behavior, block: 'nearest', inline: 'center' })
}

watch(props.current, async () => {
  await nextTick()
  centerTodayColumn('smooth')
}, { immediate: true })

const days = computed(() => daysInMonth(props.current))

const projectsWithStats = computed(() =>
  props.projects.map(p => {
    const days = Object.values(p.entries).reduce((sum, { coverage }) => sum + coverage / 100, 0)
    return { ...p, days, revenue: days * p.daily_rate }
  }),
)

const totalDays = computed(() => projectsWithStats.value.reduce((sum, { days }) => sum + days, 0))
const totalRevenue = computed(() => projectsWithStats.value.reduce((sum, { revenue }) => sum + revenue, 0))

const onCellClick = (projectId: string, date: string) => {
  const project = props.projects.find(p => p.id === projectId)!
  const existing = project.entries[date] ?? null
  const newCoverage = !existing || existing.coverage === 0 ? 100 : existing.coverage > 50 ? 50 : 0

  return router.visit(syncEntries({ project }), {
    data: { [date]: { coverage: newCoverage } },
    only: ['projects'],
    preserveState: true,
    preserveScroll: true,
    // @ts-expect-error issue with Inertia types
    optimistic: ({ projects }: Props) => ({
      projects: projects.map(p => p.id !== projectId ? p : {
        ...p,
        entries: {
          ...p.entries,
          [date]: {
            coverage: newCoverage,
            title: existing?.title ?? '',
            description: existing?.description ?? '',
          },
        },
      }),
    }),
  })
}

const openEntry = (projectId: string, date: string) => {
  const project = props.projects.find(p => p.id === projectId)!
  activeEntry.value = { projectId, date, isArchived: !!project.deleted_at, ...project.entries[date]! }
}

function entryDateLabel(date: string): string {
  const d = new Date(`${date}T00:00:00`)
  return `${d.getDate()} ${formatMonthName(d.getFullYear(), d.getMonth() + 1)}`
}

type InertiaOptimisticPage = InertiaPageProps & {
  errors: Errors & ErrorBag,
  deferred?: Record<string, string[] | undefined>,
}
const entryFormOptimistic: FormComponentOptimisticCallback<InertiaOptimisticPage> = (rawPage, rawData) => {
  const page = rawPage as InertiaOptimisticPage & { projects: Project[] }
  const data = rawData as Record<string, EntryData>
  return {
    projects: page.projects.map(p => p.id !== activeEntry.value?.projectId ? p : {
      ...p,
      entries: {
        ...p.entries,
        [activeEntry.value!.date]: {
          coverage: Number(data[activeEntry.value!.date].coverage),
          title: data[activeEntry.value!.date].title ?? '',
          description: data[activeEntry.value!.date].description ?? '',
        },
      },
    }),
  }
}
</script>

<template>
  <div class="flex flex-col overflow-hidden bg-default">
    <div class="flex flex-1 flex-col overflow-hidden px-3 py-4 md:px-6 md:py-6">
      <div class="mb-4 shrink-0 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <h2 class="text-sm font-semibold text-default">Activity report</h2>
          <span class="text-xs text-muted">
            {{ formatDays(totalDays) }} ({{ formatCurrency(totalRevenue) }})
          </span>
        </div>
        <div class="flex shrink-0 items-center gap-2">
          <UTooltip text="Previous month">
            <UButton :to="urls.prevMonth" icon="i-lucide-chevron-left" color="neutral" variant="ghost" size="xs" />
          </UTooltip>
          <span class="min-w-35 text-center text-sm font-medium text-default">
            {{ formatMonthName(current.year, current.month) }} {{ current.year }}
          </span>
          <UTooltip text="Next month">
            <UButton :to="urls.nextMonth" icon="i-lucide-chevron-right" color="neutral" variant="ghost" size="xs" />
          </UTooltip>
        </div>
      </div>

      <div class="min-h-0">
        <div ref="tableScrollRef" class="overflow-auto max-h-full">
          <TimesheetGrid
            :days="days"
            :holidays="holidays"
            :projects="projectsWithStats"
            @cellClick="onCellClick"
            @actionClick="openEntry"
          />
        </div>
      </div>

      <div class="">
        <div class="pt-3 hidden items-center gap-5 sm:flex">
          <div class="flex items-center gap-1.5">
            <span class="h-3 w-3 rounded-sm border border-default bg-elevated" />
            <span class="text-xs text-muted">Weekends and holidays</span>
          </div>
        </div>
        <div class="pt-2 flex items-center justify-between border-t border-default md:hidden">
          <span class="text-sm text-muted">{{ formatDays(totalDays) }} logged</span>
          <span class="text-sm font-semibold text-default">{{ formatCurrency(totalRevenue) }}</span>
        </div>
      </div>
    </div>
  </div>

  <UModal :open="!!activeEntry" :title="activeEntry ? entryDateLabel(activeEntry.date) : ''" @update:open="(val: boolean) => val || (activeEntry = null)">
    <template #body>
      <template v-if="activeEntry?.isArchived">
        <div class="space-y-4">
          <div v-if="activeEntry.title" class="flex flex-col gap-1">
            <p class="text-xs font-medium text-muted">Title</p>
            <p class="text-sm">{{ activeEntry.title }}</p>
          </div>
          <div v-if="activeEntry.description" class="flex flex-col gap-1">
            <p class="text-xs font-medium text-muted">Description</p>
            <p class="text-sm whitespace-pre-wrap">{{ activeEntry.description }}</p>
          </div>
        </div>
      </template>
      <Form
        v-else-if="activeEntry"
        id="entry-form"
        :key="`${activeEntry.projectId}:${activeEntry.date}`"
        :action="syncEntries(activeEntry.projectId)"
        method="patch"
        :only="['projects']"
        :preserveState="true"
        :preserveScroll="true"
        :optimistic="entryFormOptimistic"
        @success="activeEntry = null"
      >
        <div class="space-y-4">
          <UFormField label="Coverage (%)">
            <UInput
              :name="`${activeEntry.date}[coverage]`"
              type="number" min="0" max="100"
              :defaultValue="activeEntry.coverage"
              class="w-full"
            />
          </UFormField>
          <UFormField label="Title">
            <UInput
              :name="`${activeEntry.date}[title]`"
              type="text" placeholder="E.g. Feature X development"
              :defaultValue="activeEntry.title"
              class="w-full"
            />
          </UFormField>
          <UFormField label="Description">
            <UTextarea
              :name="`${activeEntry.date}[description]`"
              :rows="3" placeholder="Activity details..."
              :defaultValue="activeEntry.description"
              class="w-full"
            />
          </UFormField>
        </div>
      </Form>
    </template>
    <template #footer="{ close }">
      <div class="flex justify-end gap-2">
        <UButton v-if="activeEntry?.isArchived" label="Close" color="neutral" variant="outline" @click="close" />
        <template v-else>
          <UButton label="Cancel" color="neutral" variant="outline" @click="close" />
          <UButton type="submit" form="entry-form" label="Save" />
        </template>
      </div>
    </template>
  </UModal>
</template>
