<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { CalendarDate } from '@internationalized/date'
import {
  type ActiveElement,
  BarController,
  BarElement,
  CategoryScale,
  type ChartData,
  type ChartDataset,
  type ChartEvent,
  Chart as ChartJS,
  LinearScale,
  LineController,
  LineElement,
  PointElement,
  type ScriptableContext,
  Tooltip,
  type TooltipItem,
} from 'chart.js'
import { computed, type ComputedRef } from 'vue'
import { Bar } from 'vue-chartjs'
import { useMonthRangePicker } from '@/composables/useMonthRangePicker'
import { formatDays } from '@/utils/date.ts'
import { formatCurrency } from '@/utils/number.ts'
import { dashboard, timesheet } from '@/wayfinder/routes'
import { show as showBilling } from '@/wayfinder/routes/clients/billing'
import { edit as editProject } from '@/wayfinder/routes/projects'

const props = defineProps<DashboardProps>()

ChartJS.register(
  BarController,
  CategoryScale,
  LinearScale,
  BarElement,
  LineController,
  LineElement,
  PointElement,
  Tooltip,
)

type DashboardProps = {
  from: string,
  to: string,
  kpis: {
    monthDays: number,
    workingDays: number,
    fillRate: number,
    monthRevenue: number,
    projectedRevenue: number,
    periodRevenue: number,
    outstandingAmount: number,
    outstandingCount: number,
    overdueCount: number,
    periodWorkingDays: number,
    trendDays: number,
    trendRevenue: number,
    trendPeriod: number,
    prevPeriodRevenue: number,
    prevMonthRevenue: number,
  },
  chart: {
    labels: string[],
    projects: Array<{ name: string, data: number[] }>,
    billed: number[],
    workingDays: number[],
  },
  projects: Array<{
    id: string,
    clientId: string,
    name: string,
    clientName: string,
    dailyRate: number,
    maxMonthBudget: number | null,
    maxTotalBudget: number | null,
    theoreticalBudget: number,
    workedDaysCount: number,
    periodDaysCount: number,
    monthDaysCount: number,
    isInactive: boolean,
    lastActivity: string | null,
    unbilled: number,
  }>,
  monthAdvancement: number,
  outstandingInvoices: Array<{
    clientName: string,
    amount: number,
    daysWaiting: number,
  }>,
  periodRevenueByClient: Array<{
    clientName: string,
    revenue: number,
  }>,
  firstEntryMonth: string | null,
}

const parseYearMonth = (yearMonth: string) => {
  const [year, month] = yearMonth.split('-').map(Number)
  return { year: year!, month: month! }
}

const toYearMonth = (date: CalendarDate) => date.toString().slice(0, 7)

const periodMonths = computed(() => {
  const { year: fromYear, month: fromMonth } = parseYearMonth(props.from)
  const { year: toYear, month: toMonth } = parseYearMonth(props.to)
  return (toYear - fromYear) * 12 + (toMonth - fromMonth) + 1
})

const endMonthDate = computed(() => {
  const { year, month } = parseYearMonth(props.to)
  return new CalendarDate(year, month, 1)
})

const {
  pickerOpen,
  calendarValue,
  onRangeSelect,
  formatMonthLabel,
  periodLabel,
} = useMonthRangePicker(
  computed(() => props.from),
  computed(() => props.to),
  range => router.visit(dashboard({ query: range }), { preserveScroll: true }),
)

const selectedMonthLabel = computed(() => formatMonthLabel(props.to, 'long'))

const prevPeriod = computed(() => {
  const { year: fromYear, month: fromMonth } = parseYearMonth(props.from)
  const { year: toYear, month: toMonth } = parseYearMonth(props.to)
  return {
    from: toYearMonth(new CalendarDate(fromYear, fromMonth, 1).subtract({ months: 1 })),
    to: toYearMonth(new CalendarDate(toYear, toMonth, 1).subtract({ months: 1 })),
  }
})

const nextPeriod = computed(() => {
  const { year: fromYear, month: fromMonth } = parseYearMonth(props.from)
  const { year: toYear, month: toMonth } = parseYearMonth(props.to)
  return {
    from: toYearMonth(new CalendarDate(fromYear, fromMonth, 1).add({ months: 1 })),
    to: toYearMonth(new CalendarDate(toYear, toMonth, 1).add({ months: 1 })),
  }
})

const currentYear = new Date().getFullYear()
const currentMonth = new Date().getMonth() + 1
const quarterStartMonth = Math.floor((currentMonth - 1) / 3) * 3 + 1
const schoolYearStartYear = currentMonth >= 9 ? currentYear : currentYear - 1

const periodPresets = computed(() => [
  { label: '12 derniers mois', from: undefined, to: undefined },
  { label: 'Trimestre courant', from: toYearMonth(new CalendarDate(currentYear, quarterStartMonth, 1)), to: toYearMonth(new CalendarDate(currentYear, quarterStartMonth + 2, 1)) },
  { label: 'Année civile courante', from: toYearMonth(new CalendarDate(currentYear, 1, 1)), to: toYearMonth(new CalendarDate(currentYear, 12, 1)) },
  { label: 'Année scolaire courante', from: toYearMonth(new CalendarDate(schoolYearStartYear, 9, 1)), to: toYearMonth(new CalendarDate(schoolYearStartYear + 1, 8, 1)) },
  ...(props.firstEntryMonth ? [{ label: 'Tout depuis le début !', from: props.firstEntryMonth, to: toYearMonth(new CalendarDate(currentYear, currentMonth, 1)) }] : []),
])

// ── Chart colors ──────────────────────────────────────────────────────────────

const PROJECT_PALETTE = [
  '--color-indigo-500',
  '--color-rose-500',
  '--color-amber-500',
  '--color-teal-500',
  '--color-purple-500',
  '--color-orange-500',
  '--color-cyan-500',
  '--color-emerald-500',
  '--color-pink-500',
  '--color-sky-500',
  '--color-lime-500',
  '--color-violet-500',
]

const getCssColor = (varName: string): string => (
  window.getComputedStyle(document.documentElement).getPropertyValue(varName).trim()
)

const withAlpha = (color: string, alpha: number): string => color.replace(/\)$/, ` / ${alpha})`)

// ── Computed ──────────────────────────────────────────────────────────────────

const projectsWithStats = computed(() => {
  const now = new Date()
  const totalDays = props.projects.reduce((sum, p) => sum + p.periodDaysCount, 0)

  return props.projects
    .map(p => {
      const workedAmount = p.dailyRate * p.workedDaysCount
      const monthAmount = p.dailyRate * p.monthDaysCount
      const cumulativePercent = p.theoreticalBudget
        ? Math.round((workedAmount / p.theoreticalBudget) * 100)
        : 0
      const monthlyPercent = p.maxMonthBudget
        ? Math.round((monthAmount / p.maxMonthBudget) * 100)
        : 0
      const isMonthOverrun = monthlyPercent > 100
      const isMonthWarning = !isMonthOverrun && monthlyPercent > props.monthAdvancement * 100 * 1.1
      const timeShare = totalDays ? Math.round((p.periodDaysCount / totalDays) * 100) : 0
      const daysSince = p.lastActivity
        ? Math.floor((now.getTime() - new Date(p.lastActivity).getTime()) / 86_400_000)
        : null
      return {
        ...p,
        workedAmount,
        monthAmount,
        cumulativePercent,
        monthlyPercent,
        isMonthOverrun,
        isMonthWarning,
        timeShare,
        daysSince,
      }
    })
    .toSorted((a, b) =>
      a.clientName.localeCompare(b.clientName, 'fr') || a.name.localeCompare(b.name, 'fr'),
    )
})

const tooltipUi = {
  content: 'flex-col items-start h-auto py-2',
}

const monthRevenueBreakdown = computed(() =>
  props.projects
    .filter(p => p.monthDaysCount > 0 && p.dailyRate > 0)
    .map(p => ({
      label: `${p.clientName} / ${p.name}`,
      revenue: Math.round(p.monthDaysCount * p.dailyRate),
      pct: props.kpis.monthRevenue > 0
        ? Math.round(p.monthDaysCount * p.dailyRate / props.kpis.monthRevenue * 100)
        : 0,
    }))
    .sort((a, b) => b.revenue - a.revenue),
)

const monthProjectsBreakdown = computed(() =>
  props.projects
    .filter(p => p.monthDaysCount > 0)
    .map(p => ({
      label: `${p.clientName} / ${p.name}`,
      days: p.monthDaysCount,
      pct: props.kpis.monthDays > 0
        ? Math.round(p.monthDaysCount / props.kpis.monthDays * 100)
        : 0,
    }))
    .sort((a, b) => b.days - a.days),
)

const barChartData = computed(() => ({
  labels: props.chart.labels,
  datasets: [
    ...props.chart.projects.map((project, pi) => {
      const color = getCssColor(PROJECT_PALETTE[pi % PROJECT_PALETTE.length]!)
      return {
        type: 'bar',
        label: project.name,
        data: project.data.map(v => v / 100),
        backgroundColor: project.data.map((_, mi) =>
          mi === props.chart.labels.length - 1 ? color : withAlpha(color, 0.45),
        ),
        stack: 'worked',
        borderRadius: (ctx: ScriptableContext<'bar'>) => {
          const di = ctx.dataIndex
          if (!project.data[di]) return 0
          const isTop = props.chart.projects.slice(pi + 1).every(p => !(p.data[di] ?? 0))
          return isTop ? { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 } : 0
        },
        borderSkipped: false,
        order: 2,
      } satisfies ChartDataset<'bar', number[]>
    }),
    {
      type: 'line',
      label: 'Facturé',
      data: props.chart.billed.reduce<number[]>((acc, v) => [...acc, (acc.at(-1) ?? 0) + v / 100], []),
      borderColor: getCssColor('--color-green-500'),
      backgroundColor: 'transparent',
      cubicInterpolationMode: 'monotone' as const,
      pointRadius: props.chart.billed.map(v => v > 0 ? 3 : 0),
      pointHoverRadius: props.chart.billed.map(v => v > 0 ? 5 : 0),
      borderWidth: 2,
      yAxisID: 'y1',
      order: 1,
    } satisfies ChartDataset<'line', number[]>,
  ],
})) as ComputedRef<ChartData<'bar', number[]>>

const workingDaysMarkerPlugin = {
  id: 'workingDaysMarker',
  afterDatasetsDraw(chart: ChartJS) {
    const workingDays = props.chart.workingDays
    const yScale = chart.scales.y
    if (!yScale || !workingDays.length) return

    const firstBarMeta = chart.getDatasetMeta(0)
    if (!firstBarMeta?.data?.length) return

    const { ctx } = chart
    ctx.save()
    ctx.strokeStyle = 'rgba(156,163,175,0.6)'
    ctx.lineWidth = 1
    ctx.setLineDash([6, 4])

    firstBarMeta.data.forEach((bar: any, i: number) => {
      const v = workingDays[i]
      if (v === undefined) return
      const y = yScale.getPixelForValue(v)
      ctx.beginPath()
      ctx.moveTo(bar.x - bar.width / 2, y)
      ctx.lineTo(bar.x + bar.width / 2, y)
      ctx.stroke()
    })

    ctx.restore()
  },
}

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index' as const, intersect: false },
  onClick: (_event: ChartEvent, elements: ActiveElement[]) => {
    if (!elements.length) return
    const offset = props.chart.labels.length - 1 - elements[0]!.index
    const month = endMonthDate.value.subtract({ months: offset }).toString().slice(0, 7)
    router.visit(timesheet({ query: { month } }))
  },
  plugins: {
    legend: {
      display: true,
      position: 'bottom' as const,
      labels: {
        boxWidth: 12,
        boxHeight: 12,
        font: { size: 11 },
        padding: 16,
        color: 'rgb(107,114,128)',
      },
    },
    tooltip: {
      filter: (ctx: TooltipItem<'bar'>) => ctx.dataset.label === 'Facturé'
        ? (props.chart.billed[ctx.dataIndex] ?? 0) > 0
        : (ctx.parsed.y ?? 0) > 0,
      callbacks: {
        title: (items: TooltipItem<'bar'>[]) => {
          if (!items.length) return ''
          const i = items[0]!.dataIndex
          const { year: fromYear, month: fromMonth } = parseYearMonth(props.from)
          const date = new CalendarDate(fromYear, fromMonth, 1).add({ months: i })
          const label = new Date(date.year, date.month - 1)
            .toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
            .replace(/^./, c => c.toUpperCase())
          const v = props.chart.workingDays[i]
          return v !== undefined ? `${label} (${formatDays(v)}. ouvrés)` : label
        },
        label: (ctx: TooltipItem<'bar'>) => {
          if (ctx.dataset.label === 'Facturé') {
            return ` Facturé : ${formatCurrency(props.chart.billed[ctx.dataIndex]!)}`
          }
          return ` ${ctx.dataset.label} : ${formatDays(ctx.parsed.y ?? 0)}`
        },
        footer: (items: TooltipItem<'bar'>[]) => {
          if (!items.length) return ''
          const i = items[0]!.dataIndex
          const total = props.chart.projects.reduce((sum, p) => sum + (p.data[i] ?? 0), 0) / 100
          return total > 0 ? `Total : ${formatDays(total)}` : ''
        },
      },
    },
  },
  scales: {
    x: { stacked: true, grid: { display: false }, ticks: { font: { size: 11 } } },
    y: {
      stacked: true,
      beginAtZero: true,
      grid: { color: 'rgba(0,0,0,0.05)' },
      ticks: {
        font: { size: 11 },
        callback: (v: number | string) => {
          const n = Number(v)
          return n === 0 ? '0' : `${n}d`
        },
      },
    },
    y1: {
      position: 'right' as const,
      beginAtZero: true,
      grid: { display: false },
      ticks: {
        font: { size: 11 },
        callback: (v: number | string) => {
          const n = Number(v)
          return n === 0 ? '0' : n >= 1000 ? `${n / 1000}k€` : `${n}€`
        },
      },
    },
  },
}

const totalPeriodDays = computed(() =>
  props.projects.reduce((sum, p) => sum + p.periodDaysCount, 0),
)

const periodDailyRate = computed(() =>
  totalPeriodDays.value > 0 ? Math.round(props.kpis.periodRevenue / totalPeriodDays.value) : 0,
)

const periodFillRate = computed(() =>
  props.kpis.periodWorkingDays > 0 ? Math.round((totalPeriodDays.value / props.kpis.periodWorkingDays) * 100) : 0,
)

const progressBarClass = (percent: number) => {
  if (percent > 100) return 'bg-error'
  if (percent >= 80) return 'bg-warning'
  return 'bg-success'
}

const progressTextClass = (percent: number) => {
  if (percent > 100) return 'text-error font-semibold'
  if (percent >= 80) return 'text-warning font-medium'
  return 'text-muted'
}
</script>

<template>
  <Head title="Tableau de bord" />
  <div class="flex min-h-screen flex-col bg-default">
    <main class="flex-1 px-4 py-6 md:px-6 md:py-8">
      <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-y-3">
          <div>
            <h1 class="text-lg font-semibold">Tableau de bord</h1>
            <p class="hidden text-sm text-muted sm:block">Aperçu de votre activité</p>
          </div>
          <div class="flex items-center gap-1">
            <UButton
              icon="i-lucide-chevron-left"
              color="neutral"
              variant="ghost"
              size="sm"
              :href="dashboard({ query: prevPeriod })"
              preserveScroll
            />
            <UPopover v-model:open="pickerOpen">
              <UButton
                :label="periodLabel"
                icon="i-lucide-calendar-days"
                color="neutral"
                variant="outline"
                size="sm"
              />
              <template #content>
                <div class="flex items-center">
                  <div class="flex flex-col gap-0.5 border-r border-default p-2">
                    <UButton
                      v-for="preset in periodPresets"
                      :key="preset.label"
                      :label="preset.label"
                      :href="dashboard({ query: { from: preset.from, to: preset.to } })"
                      color="neutral"
                      :variant="preset.from === props.from && preset.to === props.to ? 'soft' : 'ghost'"
                      size="sm"
                      class="justify-start"
                      preserveScroll
                    />
                  </div>
                  <UCalendar
                    type="month"
                    range
                    size="sm"
                    locale="fr-FR"
                    :modelValue="calendarValue"
                    class="p-2"
                    @update:modelValue="onRangeSelect"
                  />
                </div>
              </template>
            </UPopover>
            <UButton
              icon="i-lucide-chevron-right"
              color="neutral"
              variant="ghost"
              size="sm"
              :href="dashboard({ query: nextPeriod })"
              preserveScroll
            />
          </div>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
          <UTooltip :disabled="monthProjectsBreakdown.length === 0" :delayDuration="300" :ui="tooltipUi">
            <UCard>
              <template #header>
                <div class="flex items-center justify-between">
                  <p class="truncate text-sm font-semibold">Jours en {{ selectedMonthLabel }}</p>
                  <UIcon name="i-lucide-calendar-days" class="text-muted" />
                </div>
              </template>
              <p class="text-2xl font-bold">{{ formatDays(kpis.monthDays) }}</p>
              <p class="mt-1 text-xs text-muted">sur {{ kpis.workingDays }} jours ouvrés</p>
              <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-elevated">
                <div
                  class="h-1.5 rounded-full bg-primary transition-all"
                  :style="{ width: `${Math.min(kpis.fillRate, 100)}%` }"
                />
              </div>
              <div class="mt-1 flex items-center justify-between">
                <p class="text-xs text-muted">{{ kpis.fillRate }}% d'occupation</p>
                <span
                  class="flex items-center gap-0.5 text-xs"
                  :class="kpis.trendDays >= 0 ? 'text-success' : 'text-error'"
                >
                  <UIcon :name="kpis.trendDays >= 0 ? 'i-lucide-trending-up' : 'i-lucide-trending-down'" class="size-3" />
                  {{ kpis.trendDays >= 0 ? '+' : '' }}{{ kpis.trendDays }}%
                </span>
              </div>
            </UCard>
            <template #content>
              <div class="min-w-52 space-y-1 text-xs">
                <div v-for="item in monthProjectsBreakdown" :key="item.label" class="flex justify-between gap-4">
                  <span class="truncate text-muted">{{ item.label }}</span>
                  <span class="shrink-0 font-medium">{{ formatDays(item.days) }} <span class="font-normal text-muted">({{ item.pct }}%)</span></span>
                </div>
              </div>
            </template>
          </UTooltip>

          <UTooltip :disabled="monthRevenueBreakdown.length === 0" :delayDuration="300" :ui="tooltipUi">
            <UCard>
              <template #header>
                <div class="flex items-center justify-between">
                  <p class="truncate text-sm font-semibold">Revenus en {{ selectedMonthLabel }}</p>
                  <UIcon name="i-lucide-euro" class="text-muted" />
                </div>
              </template>
              <p class="text-2xl font-bold">{{ formatCurrency(kpis.monthRevenue) }}</p>
              <p class="mt-1 text-xs text-muted">
                proj. <span class="font-medium text-primary">{{ formatCurrency(kpis.projectedRevenue) }}</span> fin de mois
              </p>
              <p
                class="mt-1 flex items-center gap-0.5 text-xs"
                :class="kpis.trendRevenue >= 0 ? 'text-success' : 'text-error'"
              >
                <UIcon :name="kpis.trendRevenue >= 0 ? 'i-lucide-trending-up' : 'i-lucide-trending-down'" class="size-3 shrink-0" />
                <span>{{ kpis.trendRevenue >= 0 ? '+' : '' }}{{ kpis.trendRevenue }}% vs mois précédent ({{ formatCurrency(kpis.prevMonthRevenue) }})</span>
              </p>
            </UCard>
            <template #content>
              <div class="min-w-52 space-y-1.5 text-xs">
                <div v-for="item in monthRevenueBreakdown" :key="item.label" class="flex justify-between gap-4">
                  <span class="truncate text-muted">{{ item.label }}</span>
                  <span class="shrink-0 font-medium">{{ formatCurrency(item.revenue) }} <span class="font-normal text-muted">({{ item.pct }}%)</span></span>
                </div>
                <div class="border-t border-default pt-1.5 text-muted">
                  Projeté : {{ formatCurrency(kpis.projectedRevenue) }} —
                  basé sur {{ Math.round(monthAdvancement * 100) }}% du mois écoulé
                </div>
              </div>
            </template>
          </UTooltip>

          <UTooltip :disabled="outstandingInvoices.length === 0" :delayDuration="300" :ui="tooltipUi">
            <UCard>
              <template #header>
                <div class="flex items-center justify-between">
                  <p class="truncate text-sm font-semibold">Factures impayées</p>
                  <UIcon name="i-lucide-clock" class="text-muted" />
                </div>
              </template>
              <p class="text-2xl font-bold">{{ formatCurrency(kpis.outstandingAmount) }}</p>
              <p class="mt-1 text-xs text-muted">
                {{ kpis.outstandingCount }} facture{{ kpis.outstandingCount > 1 ? 's' : '' }} impayée{{ kpis.outstandingCount > 1 ? 's' : '' }}
              </p>
              <p v-if="kpis.overdueCount > 0" class="mt-1 flex items-center gap-1 text-xs text-error">
                <UIcon name="i-lucide-alert-circle" class="size-3" />
                {{ kpis.overdueCount }} en retard
              </p>
              <p v-else class="mt-1 flex items-center gap-1 text-xs text-success">
                <UIcon name="i-lucide-check-circle" class="size-3" />
                Aucun retard
              </p>
            </UCard>
            <template #content>
              <div class="min-w-56 space-y-1 text-xs">
                <div v-for="(inv, i) in outstandingInvoices" :key="i" class="flex justify-between gap-4">
                  <span class="truncate" :class="inv.daysWaiting > 30 ? 'text-error' : 'text-muted'">{{ inv.clientName }}</span>
                  <span class="shrink-0 font-medium" :class="inv.daysWaiting > 30 ? 'text-error' : ''">
                    {{ formatCurrency(inv.amount) }}
                    <span class="font-normal text-muted"> depuis {{ formatDays(inv.daysWaiting) }}</span>
                  </span>
                </div>
              </div>
            </template>
          </UTooltip>

          <UTooltip :disabled="periodRevenueByClient.length === 0" :delayDuration="300" :ui="tooltipUi">
            <UCard>
              <template #header>
                <div class="flex items-center justify-between">
                  <p class="truncate text-sm font-semibold">{{ periodMonths > 1 ? `Sur les ${periodMonths} mois` : 'Sur le mois' }}</p>
                  <UIcon name="i-lucide-bar-chart-2" class="text-muted" />
                </div>
              </template>
              <p class="text-2xl font-bold">{{ formatCurrency(kpis.periodRevenue) }}</p>
              <p class="mt-1 text-xs text-muted">
                <span class="font-medium">{{ formatDays(totalPeriodDays) }}</span> · <span class="font-medium">{{ formatCurrency(periodDailyRate) }}/j</span> · <span class="font-medium">{{ formatCurrency(Math.round(kpis.periodRevenue / periodMonths)) }}/mois</span>
              </p>
              <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-elevated">
                <div
                  class="h-1.5 rounded-full bg-primary transition-all"
                  :style="{ width: `${Math.min(periodFillRate, 100)}%` }"
                />
              </div>
              <div class="mt-1 flex items-center justify-between">
                <p class="text-xs text-muted">{{ periodFillRate }}% d'occupation</p>
                <span
                  class="flex items-center gap-0.5 text-xs"
                  :class="kpis.trendPeriod >= 0 ? 'text-success' : 'text-error'"
                >
                  <UIcon :name="kpis.trendPeriod >= 0 ? 'i-lucide-trending-up' : 'i-lucide-trending-down'" class="size-3" />
                  {{ kpis.trendPeriod >= 0 ? '+' : '' }}{{ kpis.trendPeriod }}%
                </span>
              </div>
            </UCard>
            <template #content>
              <div class="min-w-52 space-y-1 text-xs">
                <div v-for="item in periodRevenueByClient" :key="item.clientName" class="flex justify-between gap-4">
                  <span class="truncate text-muted">{{ item.clientName }}</span>
                  <span class="shrink-0 font-medium">{{ formatCurrency(item.revenue) }}</span>
                </div>
                <div class="border-t border-default pt-1.5 text-muted">
                  <div>{{ periodFillRate }}% d'occupation sur {{ kpis.periodWorkingDays }} j. ouvrés</div>
                  <div>{{ kpis.trendPeriod >= 0 ? '+' : '' }}{{ kpis.trendPeriod }}% {{ periodMonths > 1 ? `vs les ${periodMonths} précédents` : 'vs le mois précédent' }} ({{ formatCurrency(kpis.prevPeriodRevenue) }})</div>
                </div>
              </div>
            </template>
          </UTooltip>
        </div>

        <!-- Chart -->
        <UCard>
          <template #header>
            <p class="text-sm font-semibold">Activité sur la période</p>
          </template>
          <div class="h-72 cursor-pointer">
            <Bar :data="barChartData" :options="barChartOptions" :plugins="[workingDaysMarkerPlugin]" />
          </div>
        </UCard>

        <!-- Projects -->
        <UCard>
          <template #header>
            <p class="text-sm font-semibold">Projets</p>
          </template>

          <div class="overflow-x-auto">
            <div class="min-w-240 grid grid-cols-[minmax(0,3fr)_minmax(0,2fr)_minmax(0,3fr)_auto_auto] gap-x-4 lg:gap-x-6">
              <span class="pb-2 text-xs text-muted">Projet</span>
              <span class="pb-2 text-xs text-muted">Taux <span class="opacity-60">(taux jour. · temps)</span></span>
              <span class="pb-2 text-xs text-muted">Budget <span class="opacity-60">(total · ce mois)</span></span>
              <span class="pb-2 text-xs text-muted">Dern. activité</span>
              <span class="pb-2 text-center text-xs text-muted">À facturer</span>

              <div
                v-for="p in projectsWithStats"
                :key="p.id"
                class="col-span-full grid grid-cols-subgrid items-center border-t border-default py-3.5"
              >
                <div class="flex justify-between items-start">
                  <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium">{{ p.name }}</p>
                    <p class="text-xs text-muted">{{ p.clientName }}</p>
                  </div>
                  <UButton :href="editProject(p)" icon="i-lucide-pencil" color="neutral" variant="ghost" size="xs" />
                </div>

                <div class="grid gap-1.5">
                  <div class="flex items-center justify-between text-xs">
                    <div class="flex items-baseline gap-1">
                      <template v-if="p.dailyRate > 0">
                        <span class="text-muted">{{ formatCurrency(p.dailyRate) }}/j ×</span>
                      </template>
                      <span class="font-semibold">{{ formatDays(p.workedDaysCount) }}</span>
                    </div>
                    <span v-if="p.dailyRate > 0">=</span>
                  </div>
                  <div class="h-1.5 w-full overflow-hidden rounded-full bg-elevated">
                    <div
                      class="h-1.5 rounded-full bg-primary transition-all duration-500"
                      :style="{ width: `${p.timeShare}%` }"
                    />
                  </div>
                  <div class="text-xs text-muted">
                    {{ p.timeShare }}% du temps travaillé sur la période
                  </div>
                </div>

                <div class="grid gap-1.5">
                  <div class="flex justify-between text-xs">
                    <span v-if="p.workedAmount > 0" class="flex items-baseline gap-1">
                      <span class="font-semibold">{{ formatCurrency(p.workedAmount) }}</span>
                      <span class="text-muted">travaillé</span>
                    </span>
                    <span v-if="p.cumulativePercent" :class="progressTextClass(p.cumulativePercent)">
                      {{ p.cumulativePercent }}% of {{ formatCurrency(p.theoreticalBudget) }}
                    </span>
                  </div>
                  <div
                    v-if="p.cumulativePercent"
                    class="h-2 w-full overflow-hidden rounded-full bg-elevated"
                  >
                    <div
                      class="h-full rounded-full transition-all duration-500"
                      :class="progressBarClass(p.cumulativePercent)"
                      :style="{ width: `${Math.min(p.cumulativePercent, 100)}%` }"
                    />
                  </div>
                  <div
                    v-if="p.monthAmount > 0"
                    class="flex items-center gap-1 text-xs"
                    :class="p.isMonthOverrun ? 'text-error' : p.isMonthWarning ? 'text-warning' : 'text-muted'"
                  >
                    <UIcon
                      v-if="p.isMonthOverrun || p.isMonthWarning"
                      name="i-lucide-triangle-alert"
                      class="size-3 shrink-0"
                    />
                    <span>
                      <span class="font-semibold" :class="!p.isMonthOverrun && !p.isMonthWarning ? 'text-default' : ''">{{ formatCurrency(p.monthAmount) }}</span> en {{ selectedMonthLabel }}
                      <span v-if="p.isMonthOverrun || p.isMonthWarning">({{ p.monthlyPercent }}%)</span>
                    </span>
                  </div>
                </div>

                <div
                  class="text-xs"
                  :class="!p.isInactive && p.daysSince && p.daysSince > 10 ? 'text-warning' : 'text-muted'"
                >
                  <template v-if="p.isInactive">Inactif</template>
                  <template v-else-if="p.daysSince === 0">aujourd'hui</template>
                  <template v-else-if="!p.daysSince">jamais</template>
                  <template v-else>{{ formatDays(p.daysSince) }}</template>
                </div>

                <div class="flex items-center gap-2 justify-end">
                  <UBadge v-if="p.unbilled > 0" :color="p.unbilled > 10_000_00 ? 'error' : 'warning'" variant="subtle" size="sm">
                    {{ formatCurrency(p.unbilled) }}
                  </UBadge>
                  <UButton :href="showBilling({ id: p.clientId })" icon="i-lucide-receipt-text" color="neutral" variant="ghost" size="xs" />
                </div>
              </div>
            </div>
          </div>
        </UCard>
      </div>
    </main>
  </div>
</template>
