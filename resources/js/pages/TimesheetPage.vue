<script setup lang="ts">
import type { ErrorBag, Errors, FormComponentOptimisticCallback, PageProps as InertiaPageProps } from '@inertiajs/core'
import { Form, Head, router } from '@inertiajs/vue3'
import { CalendarDate, type DateValue } from '@internationalized/date'
import { computed, nextTick, ref, watch } from 'vue'
import TimesheetGrid from '@/components/TimesheetGrid.vue'
import { useToday } from '@/composables/useToday.ts'
import {
  daysInMonth,
  formatDate,
  formatDays,
  formatMonthName,
  isInMonth,
} from '@/utils/date.ts'
import { formatCurrency } from '@/utils/number.ts'
import { timesheet } from '@/wayfinder/routes'
import { show as showBilling } from '@/wayfinder/routes/clients/billing'
import { sync as syncEntries } from '@/wayfinder/routes/projects/entries'

type EntryData = { coverage: number, title: string, description: string }
type Project = {
  id: string,
  name: string,
  client: { id: string, name: string },
  daily_rate: number,
  entries: Record<string, EntryData>,
  is_inactive?: boolean,
  start_date: string,
  end_date?: string | null,
}
type ActiveEntry = { projectId: string, date: string, isReadOnly: boolean } & EntryData

type Invoice = {
  id: string,
  amount: number,
  created_at: string,
  paid_at: string | null,
  notes: string | null,
  project_name: string,
  client_name: string,
  client_id: string,
  daily_rate: number,
}

type Props = {
  current: { year: number, month: number },
  urls: { nextMonth: string, prevMonth: string },
  projects: Project[],
  holidays: Record<string, string>,
  invoices: Invoice[],
}
const props = defineProps<Props>()

const holidays = computed(() => new Map(Object.entries(props.holidays)))

const { today } = useToday()

const tableScrollRef = ref<HTMLElement | null>(null)
const activeEntry = ref<ActiveEntry | null>(null)
const monthPickerOpen = ref(false)

const currentMonthCalendarDate = computed(() => new CalendarDate(props.current.year, props.current.month, 1))

const onMonthSelect = (value: DateValue | { start?: DateValue, end?: DateValue } | DateValue[] | null | undefined) => {
  if (!value || Array.isArray(value) || 'start' in value) return
  monthPickerOpen.value = false
  router.visit(timesheet({ query: { month: value.toString().slice(0, 7) } }))
}

const centerTodayColumn = (behavior: ScrollBehavior = 'auto') => {
  const isCurrentMonth =
    props.current.year === today.value.year
    && props.current.month === today.value.month

  if (!isCurrentMonth || !tableScrollRef.value) return

  tableScrollRef.value.querySelector<HTMLElement>(`#day-col-${today.value.toString()}`)
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

const inCurrentMonth = (date: string | null | undefined) => isInMonth(date, props.current.year, props.current.month)

const billedTotal = computed(() =>
  props.invoices.filter(i => inCurrentMonth(i.created_at)).reduce((s, i) => s + i.amount, 0),
)
const paidTotal = computed(() =>
  props.invoices.filter(i => inCurrentMonth(i.paid_at)).reduce((s, i) => s + i.amount, 0),
)
const billedDaysTotal = computed(() =>
  props.invoices
    .filter(i => inCurrentMonth(i.created_at) && i.daily_rate > 0)
    .reduce((s, i) => s + i.amount / i.daily_rate, 0),
)
const paidDaysTotal = computed(() =>
  props.invoices
    .filter(i => inCurrentMonth(i.paid_at) && i.daily_rate > 0)
    .reduce((s, i) => s + i.amount / i.daily_rate, 0),
)

const syncCoverage = (projectId: string, date: string, coverage: number) => {
  const project = props.projects.find(p => p.id === projectId)!
  const existing = project.entries[date] ?? null

  return router.visit(syncEntries({ project }), {
    data: { entries: [{ date, coverage }] },
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

const isActiveOn = (project: Project, date: string): boolean =>
  date >= project.start_date && (!project.end_date || date <= project.end_date)

const openEntry = (projectId: string, date: string) => {
  const project = props.projects.find(p => p.id === projectId)!
  const entry = project.entries[date] ?? null
  const isReadOnly = !isActiveOn(project, date)
  if (isReadOnly && !entry?.title && !entry?.description) return
  activeEntry.value = { projectId, date, isReadOnly, ...entry! }
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
  const data = (rawData as { entries: EntryData[] }).entries[0]
  return {
    projects: page.projects.map(p => p.id !== activeEntry.value?.projectId ? p : {
      ...p,
      entries: {
        ...p.entries,
        [activeEntry.value!.date]: {
          coverage: Number(data.coverage),
          title: data.title ?? '',
          description: data.description ?? '',
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
          <UPopover v-model:open="monthPickerOpen">
            <UButton
              :label="`${formatMonthName(current.month)} ${current.year}`"
              icon="i-lucide-calendar-days"
              color="neutral"
              variant="outline"
              size="sm"
            />
            <template #content>
              <UCalendar
                type="month"
                size="sm"
                locale="fr-FR"
                :modelValue="currentMonthCalendarDate"
                class="p-2"
                @update:modelValue="onMonthSelect"
              />
            </template>
          </UPopover>
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

      <div v-if="invoices.length > 0" class="mt-4 px-0">
        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted">Factures</p>
        <div class="rounded-lg border border-default overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[480px]">
              <thead>
                <tr class="border-b border-default bg-muted/40 text-xs text-muted">
                  <th class="px-4 py-2.5 text-left font-medium">Client / Description</th>
                  <th class="px-4 py-2.5 text-right font-medium">Facturé</th>
                  <th class="px-4 py-2.5 text-right font-medium">Reçu</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="inv in invoices"
                  :key="inv.id"
                  class="border-b border-default last:border-0"
                >
                  <td class="px-4 py-2.5">
                    <div class="flex items-center gap-1">
                      <span class="font-medium">{{ inv.project_name }}</span>
                      <span class="text-muted">· {{ inv.client_name }}</span>
                      <UButton :href="showBilling({ id: inv.client_id })" icon="i-lucide-receipt-text" color="neutral" variant="ghost" size="2xs" />
                    </div>
                    <p v-if="inv.notes" class="text-xs text-muted truncate">{{ inv.notes }}</p>
                  </td>
                  <td class="px-4 py-2.5 text-right tabular-nums">
                    <template v-if="inCurrentMonth(inv.created_at)">
                      <div>
                        <span :class="inv.paid_at ? 'text-success' : 'text-amber-500'">{{ formatCurrency(inv.amount) }}</span>
                        <span v-if="inv.daily_rate > 0" class="text-muted"> ({{ formatDays(inv.amount / inv.daily_rate) }})</span>
                      </div>
                      <div class="text-xs text-muted">{{ formatDate(inv.created_at) }}</div>
                    </template>
                    <span v-else class="text-xs text-muted">{{ formatDate(inv.created_at) }}</span>
                  </td>
                  <td class="px-4 py-2.5 text-right tabular-nums">
                    <template v-if="inCurrentMonth(inv.paid_at)">
                      <div>
                        <span class="text-success">{{ formatCurrency(inv.amount) }}</span>
                        <span v-if="inv.daily_rate > 0" class="text-muted"> ({{ formatDays(inv.amount / inv.daily_rate) }})</span>
                      </div>
                      <div class="text-xs text-muted">{{ formatDate(inv.paid_at!) }}</div>
                    </template>
                    <span v-else-if="inv.paid_at" class="text-xs text-muted">{{ formatDate(inv.paid_at) }}</span>
                    <span v-else class="text-muted">—</span>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="border-t-2 border-default bg-muted/30 text-sm font-semibold">
                  <td class="px-4 py-2.5">Total</td>
                  <td class="px-4 py-2.5 text-right tabular-nums">
                    <template v-if="billedTotal > 0">
                      <span>{{ formatCurrency(billedTotal) }}</span>
                      <span v-if="billedDaysTotal > 0" class="font-normal text-muted"> ({{ formatDays(billedDaysTotal) }})</span>
                    </template>
                    <span v-else class="font-normal text-muted">—</span>
                  </td>
                  <td class="px-4 py-2.5 text-right tabular-nums text-success">
                    <template v-if="paidTotal > 0">
                      <span>{{ formatCurrency(paidTotal) }}</span>
                      <span v-if="paidDaysTotal > 0" class="font-normal text-muted"> ({{ formatDays(paidDaysTotal) }})</span>
                    </template>
                    <span v-else class="font-normal text-muted">—</span>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <UModal :open="!!activeEntry" :title="activeEntry ? entryDateLabel(activeEntry.date) : ''" @update:open="(val: boolean) => val || (activeEntry = null)">
    <template #body>
      <template v-if="activeEntry?.isReadOnly">
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
          <input type="hidden" name="entries[0][date]" :value="activeEntry.date" />
          <UFormField label="Couverture (%)">
            <UInput
              name="entries[0][coverage]"
              type="number" min="0" max="100"
              :defaultValue="activeEntry.coverage"
              class="w-full"
            />
          </UFormField>
          <UFormField label="Titre">
            <UInput
              name="entries[0][title]"
              type="text" placeholder="Ex. Développement de la feature X"
              :defaultValue="activeEntry.title"
              class="w-full"
            />
          </UFormField>
          <UFormField label="Description">
            <UTextarea
              name="entries[0][description]"
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
        <UButton v-if="activeEntry?.isReadOnly" label="Fermer" color="neutral" variant="outline" @click="close" />
        <template v-else>
          <UButton label="Annuler" color="neutral" variant="outline" @click="close" />
          <UButton type="submit" form="entry-form" label="Enregistrer" />
        </template>
      </div>
    </template>
  </UModal>
</template>
