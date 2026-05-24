<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3'
import { computed, nextTick, ref, watch } from 'vue'
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
}
type EditingEntry = { projectId: string, date: string } & EntryData

type PageProps = {
  current: { year: number, month: number },
  urls: {
    nextMonth: string,
    prevMonth: string,
  },
  projects: Project[],
  holidays: Record<string, string>,
}
const props = defineProps<PageProps>()

const holidays = computed(() => new Map(Object.entries(props.holidays)))

const tableScrollRef = ref<HTMLElement | null>(null)
const editingEntry = ref<EditingEntry | null>(null)

const centerTodayColumn = (behavior: ScrollBehavior = 'auto') => {
  const isCurrentMonth =
    props.current.year === new Date().getFullYear()
    && props.current.month === new Date().getMonth() + 1

  if (!isCurrentMonth || !tableScrollRef.value) return

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

const onCellClick = (project: Project, date: string) => {
  const existing = project.entries[date] ?? null
  const newCoverage = !existing || existing.coverage === 0 ? 100 : existing.coverage > 50 ? 50 : 0

  return router.visit(syncEntries({ project }), {
    data: { [date]: { coverage: newCoverage } },
    only: ['projects'],
    preserveState: true,
    preserveScroll: true,
    // @ts-expect-error Problème avec types inertia
    optimistic: ({ projects }: PageProps) => ({
      projects: projects.map(p => p.id !== project.id ? p : {
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

const openEdit = (project: Project, date: string) => {
  editingEntry.value = { projectId: project.id, date, ...project.entries[date]! }
}
const coverageLabel = (coverage: number): string => {
  if (coverage >= 100) return '1'
  if (coverage >= 80) return '⅘'
  if (coverage >= 75) return '¾'
  if (coverage >= 66) return '⅔'
  if (coverage >= 60) return '⅗'
  if (coverage >= 50) return '½'
  if (coverage >= 40) return '⅖'
  if (coverage >= 33) return '⅓'
  if (coverage >= 25) return '¼'
  if (coverage >= 20) return '⅕'
  return '⅐'
}

function entryDateLabel(date: string): string {
  const d = new Date(`${date}T00:00:00`)
  return `${d.getDate()} ${formatMonthName(d.getFullYear(), d.getMonth() + 1)}`
}
</script>

<template>
  <div class="flex h-[calc(100vh-3.5rem)] flex-col overflow-hidden bg-default">
    <div class="flex flex-1 overflow-hidden">
      <main class="flex flex-1 flex-col overflow-hidden px-3 py-4 md:px-6 md:py-6">
        <div class="mb-4 shrink-0 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <h2 class="text-sm font-semibold text-default">Compte-rendu d'activité</h2>
            <span class="text-xs text-muted">
              {{ formatDays(totalDays) }} ({{ formatCurrency(totalRevenue) }})
            </span>
          </div>
          <div class="flex shrink-0 items-center gap-2">
            <UButton
              :to="urls.prevMonth" icon="i-lucide-chevron-left" color="neutral" variant="ghost"
              size="xs"
            />
            <span class="min-w-35 text-center text-sm font-medium text-default">
              {{ formatMonthName(current.year, current.month) }} {{ current.year }}
            </span>
            <UButton
              :to="urls.nextMonth" icon="i-lucide-chevron-right" color="neutral"
              variant="ghost" size="xs"
            />
          </div>
        </div>

        <div class="flex-1 min-h-0">
          <div ref="tableScrollRef" class="overflow-auto rounded-md border border-default max-h-full">
            <table
              class="border-separate border-spacing-0"
              style="table-layout: fixed; width: max-content; min-width: 100%;"
            >
              <colgroup>
                <col style="width: 220px; min-width: 220px;" />
                <col v-for="day in days" :key="day.n" style="width: 56px; min-width: 56px;" />
              </colgroup>
              <thead>
                <tr>
                  <th class="sticky left-0 top-0 z-30 border-b border-r border-default bg-default px-3 py-2 text-left text-xs font-medium text-muted">
                    Projet
                  </th>
                  <th
                    v-for="day in days" :id="`day-col-${day.date}`" :key="day.n"
                    class="sticky top-0 z-10 border-b border-r border-default px-0 py-1.5 text-center"
                    :class="[
                      holidays.has(day.date) || day.isWeekend ? 'bg-elevated' : 'bg-default',
                      day.date === TODAY ? 'bg-primary/15!' : '',
                    ]"
                    :title="holidays.get(day.date)"
                  >
                    <div
                      class="text-xs font-semibold leading-none"
                      :class="day.date === TODAY ? 'text-primary' : 'text-default'"
                    >
                      {{ day.n }}
                    </div>
                    <div
                      class="mt-0.5 text-[10px] leading-none"
                      :class="day.date === TODAY ? 'text-primary' : 'text-muted'"
                    >
                      {{ day.letter }}
                    </div>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!projectsWithStats.length">
                  <td :colspan="days.length + 1" class="px-4 py-8 text-center text-sm text-muted">
                    Aucun projet disponible.
                  </td>
                </tr>
                <tr v-for="project in projectsWithStats" :key="project.id" class="group/row">
                  <td class="sticky left-0 z-10 border-b border-r border-default bg-default px-3 py-2">
                    <div class="truncate text-sm font-medium text-default">{{ project.name }}</div>
                    <div class="truncate text-xs text-muted flex items-center gap-1">
                      <span class="flex-1">{{ project.client.name }}</span>
                      <span>
                        {{ formatDays(project.days ?? 0) }}
                        ({{ formatCurrency(project.revenue ?? 0) }})
                      </span>
                    </div>
                  </td>
                  <td
                    v-for="day in days" :key="day.date"
                    class="group/cell h-13 relative border-b border-r border-default transition-colors select-none overflow-hidden cursor-pointer"
                    :class="{
                      'bg-elevated': !(project.entries[day.date]?.coverage) && (holidays.has(day.date) || day.isWeekend),
                      'bg-primary/25 hover:bg-primary/30': (project.entries[day.date]?.coverage ?? 0) >= 100,
                      'bg-primary/10 hover:bg-primary/15': (project.entries[day.date]?.coverage ?? 0) > 0 && (project.entries[day.date]?.coverage ?? 0) < 100,
                      'hover:bg-elevated/60': !(project.entries[day.date]?.coverage),
                      'ring-1 ring-inset ring-primary/50': day.date === TODAY && !(project.entries[day.date]?.coverage),
                    }"
                    @click="onCellClick(project, day.date)"
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
                    <button
                      type="button"
                      class="absolute right-0.5 top-0.5 flex h-5 w-5 items-center justify-center rounded opacity-0 transition-opacity hover:bg-primary/25 group-hover/cell:opacity-100"
                      @click.stop="openEdit(project, day.date)"
                    >
                      <UIcon name="i-lucide-pencil" class="h-3 w-3 text-primary" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="mt-auto shrink-0">
          <div class="pt-3 hidden items-center gap-5 sm:flex">
            <div class="flex items-center gap-1.5">
              <span class="h-3 w-3 rounded-sm border border-default bg-elevated" />
              <span class="text-xs text-muted">Week-ends et jours fériés</span>
            </div>
          </div>
          <div class="pt-2 flex items-center justify-between border-t border-default md:hidden">
            <span class="text-sm text-muted">{{ formatDays(totalDays) }} saisis</span>
            <span class="text-sm font-semibold text-default">{{ formatCurrency(totalRevenue) }}</span>
          </div>
        </div>
      </main>
    </div>
  </div>

  <UModal v-model:open="editingEntry" :title="editingEntry ? entryDateLabel(editingEntry.date) : ''">
    <template #body>
      <Form
        v-if="editingEntry"
        id="entry-form"
        :key="`${editingEntry.projectId}:${editingEntry.date}`"
        :action="syncEntries(editingEntry.projectId).url"
        method="patch"
        :only="['projects']"
        :preserveState="true"
        :preserveScroll="true"
        :optimistic="(page: { projects: Project[] }, data: Record<string, any>) => ({
          projects: page.projects.map(p => p.id !== editingEntry!.projectId ? p : {
            ...p,
            entries: {
              ...p.entries,
              [editingEntry!.date]: {
                coverage: Number(data[editingEntry!.date].coverage),
                title: data[editingEntry!.date].title ?? '',
                description: data[editingEntry!.date].description ?? '',
              },
            },
          }),
        })"
        @success="editingEntry = null"
      >
        <div class="space-y-4">
          <UFormField label="Couverture (%)">
            <UInput
              :name="`${editingEntry.date}[coverage]`"
              type="number" min="0" max="100"
              :defaultValue="editingEntry.coverage"
              class="w-full"
            />
          </UFormField>
          <UFormField label="Titre">
            <UInput
              :name="`${editingEntry.date}[title]`"
              type="text" placeholder="Ex : Développement feature X"
              :defaultValue="editingEntry.title"
              class="w-full"
            />
          </UFormField>
          <UFormField label="Description">
            <UTextarea
              :name="`${editingEntry.date}[description]`"
              :rows="3" placeholder="Détails de l'activité…"
              :defaultValue="editingEntry.description"
              class="w-full"
            />
          </UFormField>
        </div>
      </Form>
    </template>
    <template #footer="{ close }">
      <div class="flex justify-end gap-2">
        <UButton label="Annuler" color="neutral" variant="outline" @click="close" />
        <UButton type="submit" form="entry-form" label="Enregistrer" />
      </div>
    </template>
  </UModal>
</template>
