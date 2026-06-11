<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import {
  type ActiveElement,
  BarController,
  BarElement,
  CategoryScale,
  type ChartData,
  type ChartDataset,
  type ChartEvent,
  Chart as ChartJS,
  Filler,
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
import { formatDays } from '@/utils/date.ts'
import { formatCurrency } from '@/utils/number.ts'
import { timesheet } from '@/wayfinder/routes'
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
  Filler,
)

type DashboardProps = {
  kpis: {
    monthDays: number,
    workingDays: number,
    fillRate: number,
    monthRevenue: number,
    projectedRevenue: number,
    yearRevenue: number,
    outstandingAmount: number,
    outstandingCount: number,
    overdueCount: number,
    weightedRate: number,
    trendDays: number,
    trendRevenue: number,
    trendYear: number,
  },
  chart: {
    labels: string[],
    projects: Array<{ name: string, data: number[] }>,
    billed: number[],
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
    monthDaysCount: number,
    deletedAt: string | null,
    lastActivity: string | null,
    unbilled: number,
  }>,
  monthAdvancement: number,
}

const now = new Date()
const currentMonthLabel = now.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })

// ── Chart colors ──────────────────────────────────────────────────────────────

const PROJECT_PALETTE = [
  '--color-indigo-500',
  '--color-rose-500',
  '--color-amber-500',
  '--color-teal-500',
  '--color-purple-500',
]

const getCssColor = (varName: string): string => (
  window.getComputedStyle(document.documentElement).getPropertyValue(varName).trim()
)

const withAlpha = (color: string, alpha: number): string => color.replace(/\)$/, ` / ${alpha})`)

// ── Computed ──────────────────────────────────────────────────────────────────

const projectsWithStats = computed(() => {
  const totalDays = props.projects.reduce((sum, p) => sum + p.workedDaysCount, 0)

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
      const timeShare = totalDays ? Math.round((p.workedDaysCount / totalDays) * 100) : 0
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
    .toSorted((a, b) => b.dailyRate - a.dailyRate)
})

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

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index' as const, intersect: false },
  onClick: (_event: ChartEvent, elements: ActiveElement[]) => {
    if (!elements.length) return
    const offset = props.chart.labels.length - 1 - elements[0]!.index
    const d = new Date(now)
    d.setDate(1)
    d.setMonth(d.getMonth() - offset)
    const month = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
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
        label: (ctx: TooltipItem<'bar'>) => {
          if (ctx.dataset.label === 'Facturé') {
            return ` Facturé : ${formatCurrency(props.chart.billed[ctx.dataIndex]!)}`
          }
          return ` ${ctx.dataset.label} : ${formatDays(ctx.parsed.y ?? 0)}`
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

const progressBarClass = (percent: number) => {
  if (percent >= 100) return 'bg-error'
  if (percent >= 80) return 'bg-warning'
  return 'bg-success'
}

const progressTextClass = (percent: number) => {
  if (percent >= 100) return 'text-error font-semibold'
  if (percent >= 80) return 'text-warning font-medium'
  return 'text-muted'
}
</script>

<template>
  <div class="flex min-h-screen flex-col bg-default">
    <main class="flex-1 px-6 py-8">
      <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-lg font-semibold">Tableau de bord</h1>
            <p class="text-sm text-muted">Aperçu de votre activité</p>
          </div>
          <UButton :label="currentMonthLabel" :href="timesheet()" icon="i-lucide-calendar-days" />
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
          <UCard>
            <template #header>
              <div class="flex items-center justify-between">
                <p class="text-sm font-semibold">Jours ce mois</p>
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
              <p class="text-xs text-muted">{{ kpis.fillRate }}% remplis</p>
              <span
                class="flex items-center gap-0.5 text-xs"
                :class="kpis.trendDays >= 0 ? 'text-success' : 'text-error'"
              >
                <UIcon :name="kpis.trendDays >= 0 ? 'i-lucide-trending-up' : 'i-lucide-trending-down'" class="size-3" />
                {{ kpis.trendDays >= 0 ? '+' : '' }}{{ kpis.trendDays }}%
              </span>
            </div>
          </UCard>

          <UCard>
            <template #header>
              <div class="flex items-center justify-between">
                <p class="text-sm font-semibold">Revenus ce mois</p>
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
              <UIcon :name="kpis.trendRevenue >= 0 ? 'i-lucide-trending-up' : 'i-lucide-trending-down'" class="size-3" />
              {{ kpis.trendRevenue >= 0 ? '+' : '' }}{{ kpis.trendRevenue }}% vs mois précédent
            </p>
          </UCard>

          <UCard>
            <template #header>
              <div class="flex items-center justify-between">
                <p class="text-sm font-semibold">Factures impayées</p>
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

          <UCard>
            <template #header>
              <div class="flex items-center justify-between">
                <p class="text-sm font-semibold">12 derniers mois</p>
                <UIcon name="i-lucide-bar-chart-2" class="text-muted" />
              </div>
            </template>
            <p class="text-2xl font-bold">{{ formatCurrency(kpis.yearRevenue) }}</p>
            <p class="mt-1 text-xs text-muted">
              Taux moyen <span class="font-medium">{{ formatCurrency(kpis.weightedRate) }}/j</span>
            </p>
            <p
              class="mt-1 flex items-center gap-0.5 text-xs"
              :class="kpis.trendYear >= 0 ? 'text-success' : 'text-error'"
            >
              <UIcon :name="kpis.trendYear >= 0 ? 'i-lucide-trending-up' : 'i-lucide-trending-down'" class="size-3" />
              {{ kpis.trendYear >= 0 ? '+' : '' }}{{ kpis.trendYear }}% vs 12 mois précédents
            </p>
          </UCard>
        </div>

        <!-- Chart -->
        <UCard>
          <template #header>
            <p class="text-sm font-semibold">Activité sur 12 mois</p>
          </template>
          <div class="h-72 cursor-pointer">
            <Bar :data="barChartData" :options="barChartOptions" />
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
                    {{ p.timeShare }}% du temps travaillé
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
                      <span class="font-semibold" :class="!p.isMonthOverrun && !p.isMonthWarning ? 'text-default' : ''">{{ formatCurrency(p.monthAmount) }}</span> ce mois
                      <span v-if="p.isMonthOverrun || p.isMonthWarning">({{ p.monthlyPercent }}%)</span>
                    </span>
                  </div>
                </div>

                <div
                  class="text-xs"
                  :class="!p.deletedAt && p.daysSince && p.daysSince > 10 ? 'text-warning' : 'text-muted'"
                >
                  <template v-if="p.deletedAt">Archivé</template>
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
