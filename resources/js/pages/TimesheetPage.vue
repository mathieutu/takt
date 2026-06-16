<script setup lang="ts">
import type { ErrorBag, Errors, FormComponentOptimisticCallback, PageProps as InertiaPageProps } from '@inertiajs/core'
import { Form, Head, router } from '@inertiajs/vue3'
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
  client: { id: string, name: string },
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

const syncCoverage = (projectId: string, date: string, coverage: number) => {
  const project = props.projects.find(p => p.id === projectId)!
  const existing = project.entries[date] ?? null

  return router.visit(syncEntries({ project }), {
    data: { [date]: { coverage } },
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
            coverage,
            title: existing?.title ?? '',
            description: existing?.description ?? '',
          },
        },
      }),
    }),
  })
}

const onCellClick = (projectId: string, date: string) => {
  const project = props.projects.find(p => p.id === projectId)!
  const existing = project.entries[date] ?? null
  const newCoverage = !existing || existing.coverage === 0 ? 100 : existing.coverage > 50 ? 50 : 0
  return syncCoverage(projectId, date, newCoverage)
}

const openEntry = (projectId: string, date: string) => {
  const project = props.projects.find(p => p.id === projectId)!
  activeEntry.value = { projectId, date, isArchived: !!project.deleted_at, ...project.entries[date]! }
}

const entryDateLabel = (date: string): string => {
  const d = new Date(`${date}T00:00:00`)
  return `${d.getDate()} ${formatMonthName(d.getMonth() + 1)}`
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
  <Head :title="`${formatMonthName(current.month)} ${current.year}`" />
  <div class="flex flex-col overflow-hidden bg-default">
    <div class="flex flex-1 flex-col overflow-hidden px-3 py-4 md:px-6 md:py-6">
      <div class="mb-4 shrink-0 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <h2 class="text-sm font-semibold text-default">Rapport d'activité</h2>
          <span class="hidden md:inline text-xs text-muted">
            {{ formatDays(totalDays) }} ({{ formatCurrency(totalRevenue) }})
          </span>
        </div>
        <div class="flex shrink-0 items-center gap-2">
          <UTooltip text="Mois précédent" :kbds="['p']">
            <UButton :to="urls.prevMonth" icon="i-lucide-chevron-left" color="neutral" variant="ghost" size="xs" />
          </UTooltip>
          <span class="sm:min-w-35 text-center text-sm font-medium text-default">
            {{ formatMonthName(current.month) }} {{ current.year }}
          </span>
          <UTooltip text="Mois suivant" :kbds="['n']">
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
            @setCoverage="syncCoverage"
            @nextMonth="router.get(urls.nextMonth)"
            @prevMonth="router.get(urls.prevMonth)"
          />
        </div>
      </div>

      <div class="">
        <div class="pt-3 hidden items-center justify-between sm:flex">
          <div class="flex items-center gap-5">
            <div class="flex items-center gap-1.5">
              <span class="h-3 w-3 rounded-sm border border-default bg-elevated" />
              <span class="text-xs text-muted">Week-ends et jours fériés</span>
            </div>
          </div>
          <UPopover :content="{ align: 'end' }">
            <div class="flex items-center gap-1.5 text-xs text-muted cursor-pointer select-none">
              <UButton icon="i-lucide-keyboard" label="Raccourcis clavier" size="sm" variant="ghost" color="neutral" />
            </div>
            <template #content>
              <div class="p-3 flex flex-col gap-2 text-xs min-w-56">
                <div class="flex items-center justify-between gap-6"><span class="text-muted">Naviguer</span><span class="flex gap-1 items-center"><UKbd value="←" />|<UKbd value="→" />|<UKbd value="↑" />|<UKbd value="↓" /></span></div>
                <div class="flex items-center justify-between gap-6"><span class="text-muted">Mois précédent / suivant</span><span class="flex gap-1 items-center"><UKbd value="p" />|<UKbd value="n" /></span></div>
                <div class="flex items-center justify-between gap-6"><span class="text-muted">Semaine préc. / suiv.</span><span class="flex gap-1 items-center"><UKbd value="⌥" />+ [<UKbd value="←" />|<UKbd value="→" />]</span></div>
                <div class="flex items-center justify-between gap-6"><span class="text-muted">Aller au bord</span><span class="flex gap-1 items-center"><UKbd value="meta" />+[<UKbd value="←" />|<UKbd value="→" />|<UKbd value="↑" />|<UKbd value="↓" />]</span></div>
                <div class="flex items-center justify-between gap-6"><span class="text-muted">Début / fin de ligne</span><span class="flex gap-1 items-center"><UKbd value="Home" />|<UKbd value="End" /></span></div>
                <div class="flex items-center justify-between gap-6"><span class="text-muted">Aller au coin</span><span class="flex gap-1 items-center"><UKbd value="meta" />+[<UKbd value="Home" />|<UKbd value="End" />]</span></div>
                <div class="flex items-center justify-between gap-6"><span class="text-muted">Basculer la couverture</span><UKbd value="Space" /></div>
                <div class="flex items-center justify-between gap-6"><span class="text-muted">Définir la couverture (1/n)</span><span class="flex gap-1 items-center"><UKbd>0</UKbd><span class="text-muted">–</span><UKbd>9</UKbd></span></div>
                <div class="flex items-center justify-between gap-6"><span class="text-muted">Modifier l'entrée</span><UKbd>⏎</UKbd></div>
              </div>
            </template>
          </UPopover>
        </div>
        <div class="pt-2 flex items-center justify-between border-t border-default md:hidden">
          <span class="text-sm text-muted">{{ formatDays(totalDays) }} enregistré</span>
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
            <p class="text-xs font-medium text-muted">Titre</p>
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
          <UFormField label="Couverture (%)">
            <UInput
              :name="`${activeEntry.date}[coverage]`"
              type="number" min="0" max="100"
              :defaultValue="activeEntry.coverage"
              class="w-full"
            />
          </UFormField>
          <UFormField label="Titre">
            <UInput
              :name="`${activeEntry.date}[title]`"
              type="text" placeholder="Ex. Développement de la feature X"
              :defaultValue="activeEntry.title"
              class="w-full"
            />
          </UFormField>
          <UFormField label="Description">
            <UTextarea
              :name="`${activeEntry.date}[description]`"
              :rows="3" placeholder="Détails de l'activité..."
              :defaultValue="activeEntry.description"
              class="w-full"
            />
          </UFormField>
        </div>
      </Form>
    </template>
    <template #footer="{ close }">
      <div class="flex justify-end gap-2">
        <UButton v-if="activeEntry?.isArchived" label="Fermer" color="neutral" variant="outline" @click="close" />
        <template v-else>
          <UButton label="Annuler" color="neutral" variant="outline" @click="close" />
          <UButton type="submit" form="entry-form" label="Enregistrer" />
        </template>
      </div>
    </template>
  </UModal>
</template>
