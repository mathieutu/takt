<script setup lang="ts">
import type { MonthInvoice, MonthRow, ProjectWithBilling } from '@/types/billing'
import { Head, useForm } from '@inertiajs/vue3'
import {
  type ActiveElement,
  BarController,
  BarElement,
  CategoryScale,
  type ChartData,
  type ChartDataset,
  type ChartEvent,
  Chart as ChartJS,
  Tooltip as ChartTooltip,
  LinearScale,
  LineController,
  LineElement,
  PointElement,
  type TooltipItem,
} from 'chart.js'
import { computed, type ComputedRef, ref } from 'vue'
import { Bar } from 'vue-chartjs'
import BillingMonthCalendar from '@/components/BillingMonthCalendar.vue'
import DateInput from '@/components/DateInput.vue'
import ExportBillingModal from '@/components/ExportBillingModal.vue'
import { formatDate, formatDays, formatDuration, formatMonthName, parseMonth, today } from '@/utils/date'
import { formatCurrency } from '@/utils/number'
import { timesheet } from '@/wayfinder/routes'
import { edit as editClient } from '@/wayfinder/routes/clients'
import { destroy as destroyInvoice, store as storeInvoice, update as updateInvoice } from '@/wayfinder/routes/invoices'
import { edit as editProject, index as projectsIndex } from '@/wayfinder/routes/projects'

const props = defineProps<{
  projects: ProjectWithBilling[],
  holidays: Record<string, string>,
  is_shared: boolean,
  shared_by?: string,
  token?: string,
}>()

const holidays = computed(() => new Map(Object.entries(props.holidays)))

ChartJS.register(
  BarController,
  CategoryScale,
  LinearScale,
  BarElement,
  LineController,
  LineElement,
  PointElement,
  ChartTooltip,
)

const inactiveProjects = computed(() => props.projects.filter(p => p.is_inactive))
const showInactive = ref(inactiveProjects.value.length === props.projects.length)

const visibleProjects = computed(() =>
  showInactive.value ? props.projects : props.projects.filter(p => !p.is_inactive),
)

const exportModalOpen = ref(false)

// ── Expand state ───────────────────────────────────────────────────────────────

const expandedMonths = ref<Set<string>>(new Set())

const toggleMonth = (projectId: string, month: string) => {
  const key = `${projectId}:${month}`
  if (expandedMonths.value.has(key)) {
    expandedMonths.value.delete(key)
    return
  }
  expandedMonths.value.add(key)
}

const isExpanded = (projectId: string, month: string) => expandedMonths.value.has(`${projectId}:${month}`)

// ── Totals ─────────────────────────────────────────────────────────────────────

const projectTotals = (project: ProjectWithBilling) => {
  const totalBudgetAllocated = project.max_total_budget !== null
    ? project.max_total_budget
    : project.max_month_budget !== null
      ? project.max_month_budget * project.months_elapsed
      : null

  const unpaidDiscount = project.months
    .flatMap(m => m.invoices)
    .filter(inv => !inv.paid_at)
    .reduce((sum, inv) => sum + inv.discount_amount, 0)

  return {
    totalDays: project.total_days,
    totalWorked: project.total_worked,
    totalInvoiced: project.total_invoiced,
    totalDiscount: project.total_discount,
    unpaidDiscount,
    totalBudgetAllocated,
    toPay: project.to_pay,
    toInvoice: project.to_invoice,
    remainingToConsume: totalBudgetAllocated !== null
      ? totalBudgetAllocated - (project.total_worked - project.total_discount)
      : null,
  }
}

const monthWorked = (m: MonthRow, dailyRate: number) => m.days_worked * dailyRate
const monthInvoiced = (m: MonthRow) => m.invoices.reduce((s, i) => s + i.amount, 0)

const projectMaxDays = (project: ProjectWithBilling): number =>
  Math.max(...project.months.map(m => m.days_worked), 1)

const monthIntensityClass = (m: MonthRow, project: ProjectWithBilling): string => {
  if (m.days_worked <= 0) return 'bg-muted'
  const ratio = m.days_worked / projectMaxDays(project)
  if (ratio <= 0.25) return 'bg-primary/25'
  if (ratio <= 0.5) return 'bg-primary/45'
  if (ratio <= 0.75) return 'bg-primary/70'
  return 'bg-primary'
}

const daysSince = (dateStr: string): number => {
  const d = new Date(`${dateStr}T00:00:00`)
  const now = new Date()
  return Math.floor((now.getTime() - d.getTime()) / (1000 * 60 * 60 * 24))
}

const lastInvoiceDate = (project: ProjectWithBilling): string | null => {
  const dates = project.months
    .flatMap(m => m.invoices)
    .map(inv => inv.created_at)
    .toSorted()
  return dates.at(-1) ?? null
}

const oldestUnpaidInvoiceDate = (project: ProjectWithBilling): string | null => {
  const dates = project.months
    .flatMap(m => m.invoices)
    .filter(inv => !inv.paid_at)
    .map(inv => inv.created_at)
    .toSorted()
  return dates[0] ?? null
}

const calendarMonthsBetween = (from: string, to: string): number => {
  const [fromYear, fromMonth] = from.split('-').map(Number)
  const [toYear, toMonth] = to.split('-').map(Number)
  return (toYear! - fromYear!) * 12 + (toMonth! - fromMonth!) + 1
}

const getCumulativeWorked = (project: ProjectWithBilling, monthIndex: number): number =>
  project.months
    .slice(0, monthIndex + 1)
    .reduce((sum, m) => sum + m.days_worked * project.daily_rate, 0)

// Discounts write off part of an already-invoiced amount, so they reduce how much of the
// budget envelope is actually consumed — even though "worked" itself stays théorique.
const getCumulativeDiscount = (project: ProjectWithBilling, monthIndex: number): number =>
  project.months
    .slice(0, monthIndex + 1)
    .reduce((sum, m) => sum + m.invoices.reduce((s, i) => s + i.discount_amount, 0), 0)

const getCumulativeConsumed = (project: ProjectWithBilling, monthIndex: number): number =>
  getCumulativeWorked(project, monthIndex) - getCumulativeDiscount(project, monthIndex)

const cumulativeBudgetAmount = (project: ProjectWithBilling, monthIndex: number): number | null => {
  if (project.max_total_budget !== null) return project.max_total_budget
  if (project.max_month_budget !== null) {
    const elapsed = calendarMonthsBetween(project.months[0]!.month, project.months[monthIndex]!.month)
    return project.max_month_budget * elapsed
  }
  return null
}

const isCumulativeOverBudget = (project: ProjectWithBilling, monthIndex: number): boolean => {
  const budget = cumulativeBudgetAmount(project, monthIndex)
  return budget !== null && getCumulativeConsumed(project, monthIndex) > budget
}

const cumulativeRemainingToConsume = (project: ProjectWithBilling, monthIndex: number): number | null => {
  const budget = cumulativeBudgetAmount(project, monthIndex)
  if (budget === null) return null
  return budget - getCumulativeConsumed(project, monthIndex)
}

const cumulativeConsumptionPercent = (project: ProjectWithBilling, monthIndex: number): number | null => {
  const budget = cumulativeBudgetAmount(project, monthIndex)
  if (budget === null || budget === 0) return null
  return Math.round(getCumulativeConsumed(project, monthIndex) / budget * 100)
}

const totalConsumptionPercent = (project: ProjectWithBilling): number | null => {
  const { totalWorked, totalDiscount, totalBudgetAllocated } = projectTotals(project)
  if (totalBudgetAllocated === null || totalBudgetAllocated === 0) return null
  return Math.round((totalWorked - totalDiscount) / totalBudgetAllocated * 100)
}

const consumptionColorClass = (percent: number) => {
  if (percent >= 100) return 'text-error'
  if (percent >= 80) return 'text-amber-500'
  return 'text-success'
}

const consumptionBarClass = (percent: number) => {
  if (percent >= 100) return 'bg-error'
  if (percent >= 80) return 'bg-amber-500'
  return 'bg-success'
}

// ── Client totals ──────────────────────────────────────────────────────────────

const clientTotals = computed(() => {
  const totalDays = visibleProjects.value.reduce((sum, p) => sum + projectTotals(p).totalDays, 0)
  const totalWorked = visibleProjects.value.reduce((sum, p) => sum + projectTotals(p).totalWorked, 0)
  const averageDailyRate = totalDays > 0 ? Math.round(totalWorked / totalDays) : null
  return { totalDays, totalWorked, averageDailyRate }
})

// ── Activity chart ─────────────────────────────────────────────────────────────

const PROJECT_PALETTE = [
  '--color-indigo-500',
  '--color-rose-500',
  '--color-amber-500',
  '--color-teal-500',
  '--color-purple-500',
  '--color-orange-500',
  '--color-cyan-500',
  '--color-emerald-500',
]

const getCssColor = (varName: string): string =>
  window.getComputedStyle(document.documentElement).getPropertyValue(varName).trim()

const chartMonths = computed(() => {
  const months = new Set<string>()
  visibleProjects.value.forEach(p => p.months.forEach(m => months.add(m.month)))
  return [...months].sort()
})

const chartMonthLabel = (ym: string): string => {
  const { year, month } = parseMonth(ym)
  return new Date(year, month - 1).toLocaleDateString('fr-FR', { month: 'short', year: '2-digit' })
}

const monthNetInvoiced = (m: MonthRow) => m.invoices.reduce((s, i) => s + i.net_amount, 0)

// Non-cumulative, one value per chartMonths entry — the tooltip needs this exact source
// (not the cumulative line data below) to report a given month's actual net invoiced amount.
const monthlyNetInvoiced = computed(() => chartMonths.value.map(ym => visibleProjects.value.reduce((sum, project) => {
  const m = project.months.find(mm => mm.month === ym)
  return m ? sum + monthNetInvoiced(m) : sum
}, 0)))

const barChartData = computed(() => ({
  labels: chartMonths.value.map(chartMonthLabel),
  datasets: [
    ...visibleProjects.value.map((project, pi) => {
      const daysByMonth = new Map(project.months.map(m => [m.month, m.days_worked]))
      return {
        type: 'bar',
        label: project.name,
        data: chartMonths.value.map(ym => daysByMonth.get(ym) ?? 0),
        backgroundColor: getCssColor(PROJECT_PALETTE[pi % PROJECT_PALETTE.length]!),
        stack: 'worked',
        order: 2,
      } satisfies ChartDataset<'bar', number[]>
    }),
    {
      type: 'line',
      label: 'Facturé',
      data: monthlyNetInvoiced.value.reduce<number[]>((acc, v) => [...acc, (acc.at(-1) ?? 0) + v / 100], []),
      borderColor: getCssColor('--color-green-500'),
      backgroundColor: 'transparent',
      cubicInterpolationMode: 'monotone' as const,
      pointRadius: monthlyNetInvoiced.value.map(v => v > 0 ? 3 : 0),
      pointHoverRadius: monthlyNetInvoiced.value.map(v => v > 0 ? 5 : 0),
      borderWidth: 2,
      yAxisID: 'y1',
      order: 1,
    } satisfies ChartDataset<'line', number[]>,
  ],
})) as ComputedRef<ChartData<'bar', number[]>>

const projectsInvoicedInMonth = (month: string): ProjectWithBilling[] =>
  visibleProjects.value.filter(project => {
    const m = project.months.find(mm => mm.month === month)
    return m ? monthInvoiced(m) > 0 : false
  })

const openMonthForProjects = (month: string, projects: ProjectWithBilling[], scroll: boolean) => {
  expandedMonths.value.clear()
  projects.forEach(project => expandedMonths.value.add(`${project.id}:${month}`))
  const firstProject = projects[0]
  if (!scroll || !firstProject) return
  requestAnimationFrame(() => {
    document.getElementById(`month-row-${firstProject.id}-${month}`)
      ?.scrollIntoView({ behavior: 'smooth', block: 'center' })
  })
}

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index' as const, intersect: false },
  onClick: (event: ChartEvent, _elements: ActiveElement[], chart: ChartJS) => {
    if (!event.native) return
    const points = chart.getElementsAtEventForMode(event.native, 'nearest', { intersect: true }, true)
    if (!points.length) return
    const { index, datasetIndex } = points[0]!
    const month = chartMonths.value[index]
    if (!month) return

    const clickedProject = visibleProjects.value[datasetIndex]
    if (clickedProject) {
      openMonthForProjects(month, [clickedProject], true)
      return
    }

    const invoicedProjects = projectsInvoicedInMonth(month)
    openMonthForProjects(month, invoicedProjects, invoicedProjects.length === 1)
  },
  plugins: {
    legend: {
      display: true,
      position: 'bottom' as const,
      labels: { boxWidth: 12, boxHeight: 12, font: { size: 11 }, padding: 16, color: 'rgb(107,114,128)' },
    },
    tooltip: {
      callbacks: {
        label: (ctx: TooltipItem<'bar'>) => ctx.dataset.label === 'Facturé'
          ? ` Facturé (net) : ${formatCurrency(monthlyNetInvoiced.value[ctx.dataIndex] ?? 0)}`
          : ` ${ctx.dataset.label} : ${formatDays(ctx.parsed.y ?? 0)}`,
      },
    },
  },
  scales: {
    x: { stacked: true, grid: { display: false }, ticks: { font: { size: 11 } } },
    y: {
      stacked: true,
      beginAtZero: true,
      grid: { color: 'rgba(0,0,0,0.05)' },
      ticks: { font: { size: 11 }, callback: (v: number | string) => `${Number(v)}j` },
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

// ── Helpers ────────────────────────────────────────────────────────────────────

const discountPercent = (grossTotal: number, discountTotal: number): number =>
  grossTotal > 0 ? Math.round(discountTotal / grossTotal * 100) : 0

const totalDiscountPercent = (project: ProjectWithBilling): number =>
  discountPercent(projectTotals(project).totalInvoiced, projectTotals(project).totalDiscount)

const unpaidDiscountPercent = (project: ProjectWithBilling): number => {
  const { toPay, unpaidDiscount } = projectTotals(project)
  return discountPercent(toPay + unpaidDiscount, unpaidDiscount)
}

const fmtDays = (amount: number, dailyRate: number): string | null =>
  dailyRate > 0 ? formatDays(amount / dailyRate) : null

// ── Invoice form ───────────────────────────────────────────────────────────────

const invoiceOpen = ref(false)
const editingInvoiceId = ref<string | null>(null)
const invoiceProjectId = ref<string | null>(null)
const form = useForm({ amount: '', discount_amount: '0', paid_at: '', notes: '', created_at: '', project_id: '' })

// Local-only convenience field: lets the discount be entered as a percentage.
// Not submitted — kept in sync with form.discount_amount via two dedicated
// handlers (never a watch, to avoid a feedback loop between the two fields).
const discountPercentInput = ref('0')

const amountCents = (value: string): number => Math.round((Number.parseFloat(value) || 0) * 100)

const syncDiscountPercentFromAmount = () => {
  const grossCents = amountCents(form.amount)
  const discountCents = amountCents(form.discount_amount)
  discountPercentInput.value = grossCents > 0 ? String(Math.round(discountCents / grossCents * 100)) : '0'
}

const syncDiscountAmountFromPercent = () => {
  const grossCents = amountCents(form.amount)
  const percent = Number.parseFloat(discountPercentInput.value) || 0
  form.discount_amount = String(Math.round(grossCents * percent / 100) / 100)
}

const discountAmountModel = computed<string>({
  get: () => form.discount_amount,
  set: value => {
    form.discount_amount = value
    syncDiscountPercentFromAmount()
  },
})

const discountPercentModel = computed<string>({
  get: () => discountPercentInput.value,
  set: value => {
    discountPercentInput.value = value
    syncDiscountAmountFromPercent()
  },
})

const netAmountPreview = computed(() => amountCents(form.amount) - amountCents(form.discount_amount))

const projectSelectItems = computed(() =>
  props.projects.map(p => ({
    value: p.id,
    label: p.is_inactive ? `${p.name} (inactif)` : p.name,
  })),
)

const openAddInvoice = (project: ProjectWithBilling) => {
  invoiceProjectId.value = project.id
  editingInvoiceId.value = null
  form.reset()
  form.discount_amount = '0'
  discountPercentInput.value = '0'
  form.created_at = today.toString()
  form.project_id = project.id
  form.clearErrors()
  invoiceOpen.value = true
}

const openEditInvoice = (inv: MonthInvoice, project: ProjectWithBilling) => {
  editingInvoiceId.value = inv.id
  form.amount = String(inv.amount / 100)
  form.discount_amount = String(inv.discount_amount / 100)
  discountPercentInput.value = inv.discount_percent !== null ? String(inv.discount_percent) : '0'
  form.paid_at = inv.paid_at ?? ''
  form.created_at = inv.created_at
  form.notes = inv.notes ?? ''
  form.project_id = project.id
  form.clearErrors()
  invoiceOpen.value = true
}

const submitInvoice = () => {
  const route = editingInvoiceId.value
    ? updateInvoice(editingInvoiceId.value)
    : storeInvoice(invoiceProjectId.value!)

  form
    .transform(data => ({
      amount: Math.round(Number.parseFloat(data.amount) * 100) || 0,
      discount_amount: Math.round(Number.parseFloat(data.discount_amount) * 100) || 0,
      paid_at: data.paid_at || null,
      created_at: data.created_at || null,
      notes: data.notes || null,
      ...(editingInvoiceId.value ? { project_id: data.project_id } : {}),
    }))
    .submit(route, {
      preserveScroll: true,
      onSuccess: () => {
        invoiceOpen.value = false
        form.reset()
      },
    })
}

// ── Labels ─────────────────────────────────────────────────────────────────────

const monthLabel = (ym: string): string => {
  const { year, month } = parseMonth(ym)
  return `${formatMonthName(month)} ${year}`
}
</script>

<template>
  <Head title="Facturation" />
  <div class="flex min-h-screen flex-col bg-default">
    <div
      v-if="shared_by"
      class="shrink-0 flex items-center justify-between gap-4 border-b border-primary/20 bg-primary/5 px-4 py-2.5"
    >
      <p class="text-sm text-muted">
        Partagé par <span class="font-medium text-default">{{ shared_by }}</span>
      </p>
    </div>

    <main class="flex-1 px-4 py-6 md:px-8 md:py-8">
      <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex items-center justify-between">
          <UTooltip v-if="!is_shared" text="Retour aux projets">
            <UButton
              :href="projectsIndex()"
              icon="i-lucide-arrow-left"
              color="neutral"
              variant="ghost"
              size="sm"
            />
          </UTooltip>
          <div class="ms-auto flex items-center gap-2">
            <UButton
              v-if="inactiveProjects.length > 0 && inactiveProjects.length < projects.length"
              :label="showInactive ? 'Masquer les inactifs' : 'Afficher les projets inactifs'"
              :icon="showInactive ? 'i-lucide-eye-off' : 'i-lucide-archive'"
              color="neutral"
              variant="outline"
              size="sm"
              @click="showInactive = !showInactive"
            />
            <UButton
              label="Exporter"
              icon="i-lucide-download"
              color="neutral"
              variant="outline"
              size="sm"
              @click="exportModalOpen = true"
            />
          </div>
        </div>

        <!-- Activity chart -->
        <UCard v-if="visibleProjects.length > 0">
          <template #header>
            <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2">
              <p class="text-sm font-semibold">{{ visibleProjects[0]?.client.name }}</p>
              <div v-if="visibleProjects.length > 1" class="flex flex-wrap gap-x-6 gap-y-1 text-sm">
                <div>
                  <span class="text-muted">Jours travaillés</span>
                  <span class="ml-2 font-semibold tabular-nums">{{ formatDays(clientTotals.totalDays) }}</span>
                </div>
                <div>
                  <span class="text-muted">Montant travaillé</span>
                  <span class="ml-2 font-semibold tabular-nums">{{ formatCurrency(clientTotals.totalWorked) }}</span>
                  <span v-if="clientTotals.averageDailyRate !== null" class="ml-1 text-xs font-normal text-muted">
                    ({{ formatDays(clientTotals.totalDays) }} × {{ formatCurrency(clientTotals.averageDailyRate) }}/j)
                  </span>
                </div>
              </div>
            </div>
          </template>
          <div class="h-64 cursor-pointer">
            <Bar :data="barChartData" :options="barChartOptions" />
          </div>
        </UCard>

        <template v-for="(project, index) in visibleProjects" :key="project.id">
          <div v-if="index > 0" class="border-t-2 border-default" />

          <div class="space-y-6" :class="[project.is_inactive && ' rounded-xl bg-muted p-4']">
            <!-- Project header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <h2 class="text-base font-semibold truncate">{{ project.name }}</h2>
                  <UBadge v-if="project.is_inactive" label="Inactif" color="neutral" variant="subtle" size="sm" class="shrink-0" />
                  <UTooltip v-if="!is_shared" text="Modifier le projet">
                    <UButton :href="editProject(project)" icon="i-lucide-pencil" color="neutral" variant="ghost" size="2xs" class="shrink-0" />
                  </UTooltip>
                </div>
                <div class="flex items-center gap-1">
                  <p class="text-xs text-muted truncate">{{ project.client.name }}</p>
                  <UTooltip v-if="!is_shared" text="Modifier le client">
                    <UButton :href="editClient(project.client)" icon="i-lucide-pencil" color="neutral" variant="ghost" size="2xs" />
                  </UTooltip>
                </div>
              </div>
              <div class="flex flex-col gap-2 sm:items-end">
                <div class="flex flex-wrap items-center gap-1 text-sm text-muted">
                  <span>{{ formatCurrency(project.daily_rate) }}/j.</span>
                  <template v-if="project.max_month_budget">
                    <span>•</span>
                    <span>
                      max {{ formatCurrency(project.max_month_budget) }}/mois
                      <template v-if="fmtDays(project.max_month_budget, project.daily_rate)">
                        ({{ fmtDays(project.max_month_budget, project.daily_rate) }})
                      </template>
                    </span>
                  </template>
                  <template v-if="project.max_total_budget">
                    <span>•</span>
                    <span>
                      enveloppe {{ formatCurrency(project.max_total_budget) }}
                      <template v-if="fmtDays(project.max_total_budget, project.daily_rate)">
                        ({{ fmtDays(project.max_total_budget, project.daily_rate) }})
                      </template>
                    </span>
                  </template>
                </div>
                <UButton
                  v-if="!is_shared"
                  label="Ajouter une facture"
                  icon="i-lucide-plus"
                  size="sm"
                  @click="openAddInvoice(project)"
                />
              </div>
            </div>

            <!-- Main table -->
            <div class="rounded-lg border border-default overflow-hidden">
              <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                  <thead>
                    <tr class="border-b border-default bg-muted/40 text-xs text-muted">
                      <th class="px-4 py-2.5 text-left font-medium">Mois</th>
                      <th class="px-4 py-2.5 text-right font-medium">Travaillé</th>
                      <th class="px-4 py-2.5 text-right font-medium">Facturé</th>
                      <th class="px-4 py-2.5 text-right font-medium">À consommer</th>
                      <th v-if="!is_shared" class="w-8 px-2" />
                    </tr>
                  </thead>
                  <tbody>
                    <template v-for="(m, monthIndex) in project.months" :key="m.month">
                      <tr
                        :id="`month-row-${project.id}-${m.month}`"
                        class="border-b border-default hover:bg-muted/20 cursor-pointer transition-colors"
                        :class="isExpanded(project.id, m.month) ? 'bg-muted/20' : ''"
                        @click="toggleMonth(project.id, m.month)"
                      >
                        <td class="px-4 py-2.5 font-medium">
                          <div class="flex items-center gap-1.5">
                            <UIcon
                              :name="isExpanded(project.id, m.month) ? 'i-lucide-chevron-down' : 'i-lucide-chevron-right'"
                              class="text-muted w-3.5 h-3.5 shrink-0"
                            />
                            <span class="h-3 w-3 rounded-sm shrink-0" :class="monthIntensityClass(m, project)" />
                            {{ monthLabel(m.month) }}
                          </div>
                        </td>
                        <td
                          class="px-4 py-2.5 text-right tabular-nums"
                          :class="isCumulativeOverBudget(project, monthIndex) ? 'text-error font-medium' : 'text-muted'"
                        >
                          {{ formatCurrency(monthWorked(m, project.daily_rate)) }} ({{ formatDays(m.days_worked) }})
                        </td>
                        <td class="px-4 py-2.5 text-right tabular-nums">
                          <template v-if="monthInvoiced(m) > 0">
                            <span class="font-medium" :class="m.invoices.some(inv => !inv.paid_at) ? 'text-amber-500' : 'text-success'">{{ formatCurrency(monthInvoiced(m)) }}</span>
                            <span v-if="fmtDays(monthInvoiced(m), project.daily_rate)" class="text-muted"> ({{ fmtDays(monthInvoiced(m), project.daily_rate) }})</span>
                          </template>
                          <span v-else class="text-muted">—</span>
                        </td>
                        <td class="px-4 py-2.5 text-right tabular-nums">
                          <template v-if="cumulativeRemainingToConsume(project, monthIndex) !== null">
                            <span :class="cumulativeRemainingToConsume(project, monthIndex)! < 0 ? 'text-error' : 'text-default'">
                              {{ formatCurrency(cumulativeRemainingToConsume(project, monthIndex)!) }}
                            </span>
                            <span v-if="fmtDays(cumulativeRemainingToConsume(project, monthIndex)!, project.daily_rate)" class="text-muted"> ({{ fmtDays(cumulativeRemainingToConsume(project, monthIndex)!, project.daily_rate) }})</span>
                            <span class="text-muted"> · {{ 100 - cumulativeConsumptionPercent(project, monthIndex)! }}%</span>
                          </template>
                          <span v-else class="text-muted">—</span>
                        </td>
                        <td v-if="!is_shared" class="px-2 text-right">
                          <UTooltip text="CRA">
                            <UButton
                              :href="timesheet({ query: { month: m.month } })"
                              icon="i-lucide-calendar"
                              color="neutral"
                              variant="ghost"
                              size="2xs"
                            />
                          </UTooltip>
                        </td>
                      </tr>

                      <tr v-if="isExpanded(project.id, m.month)">
                        <td :colspan="is_shared ? 4 : 5" class="px-0 py-0">
                          <div class="border-b border-default bg-muted/10 px-6 py-3 space-y-3">
                            <div v-if="Object.keys(m.entries).length > 0">
                              <p class="text-xs font-medium text-muted mb-1.5 uppercase tracking-wide">Jours travaillés</p>
                              <BillingMonthCalendar :month="m.month" :entries="m.entries" :holidays="holidays" />
                            </div>

                            <div v-if="m.invoices.length > 0">
                              <p class="text-xs font-medium text-muted mb-1.5 uppercase tracking-wide">Factures</p>
                              <div class="space-y-1">
                                <div
                                  v-for="inv in m.invoices"
                                  :key="inv.id"
                                  class="flex items-center gap-3 text-xs"
                                >
                                  <span class="w-28 shrink-0 text-muted">
                                    {{ inv.paid_at ? formatDate(inv.paid_at) : 'Non payé' }}
                                  </span>
                                  <template v-if="inv.discount_percent !== null">
                                    <span class="line-through text-muted font-normal tabular-nums">{{ formatCurrency(inv.amount) }}</span>
                                    <span
                                      class="font-medium tabular-nums"
                                      :class="inv.paid_at ? 'text-success' : 'text-amber-500'"
                                    >{{ formatCurrency(inv.net_amount) }}</span>
                                    <span class="text-xs text-muted">−{{ inv.discount_percent }}%</span>
                                  </template>
                                  <span
                                    v-else
                                    class="font-medium tabular-nums"
                                    :class="inv.paid_at ? 'text-success' : 'text-amber-500'"
                                  >{{ formatCurrency(inv.amount) }}</span>
                                  <span v-if="inv.notes" class="text-muted truncate min-w-0 flex-1">{{ inv.notes }}</span>
                                  <div v-if="!is_shared" class="ml-auto flex items-center gap-1" @click.stop>
                                    <UButton icon="i-lucide-pencil" color="neutral" variant="ghost" size="2xs" @click="openEditInvoice(inv, project)" />
                                    <UButton icon="i-lucide-trash-2" color="error" variant="ghost" size="2xs" :to="destroyInvoice(inv)" preserveScroll />
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </td>
                      </tr>
                    </template>

                    <tr v-if="project.months.length === 0">
                      <td :colspan="is_shared ? 4 : 5" class="px-4 py-8 text-center text-sm text-muted">
                        Aucune activité enregistrée.
                      </td>
                    </tr>
                  </tbody>

                  <tfoot>
                    <tr class="border-t-2 border-default bg-muted/30 text-sm font-semibold">
                      <td class="px-4 py-3">Total</td>
                      <td class="px-4 py-3 text-right tabular-nums text-muted">
                        {{ formatCurrency(projectTotals(project).totalWorked) }}
                        ({{ formatDays(projectTotals(project).totalDays) }})
                      </td>
                      <td class="px-4 py-3 text-right tabular-nums">
                        <span class="inline-flex items-center justify-end gap-1.5">
                          <template v-if="projectTotals(project).totalDiscount > 0">
                            <span class="font-normal text-muted line-through">{{ formatCurrency(projectTotals(project).totalInvoiced) }}</span>
                            <span class="text-success">{{ formatCurrency(projectTotals(project).totalInvoiced - projectTotals(project).totalDiscount) }}</span>
                          </template>
                          <span v-else class="text-success">{{ formatCurrency(projectTotals(project).totalInvoiced) }}</span>
                          <span v-if="fmtDays(projectTotals(project).totalInvoiced, project.daily_rate)" class="font-normal text-muted">({{ fmtDays(projectTotals(project).totalInvoiced, project.daily_rate) }})</span>
                        </span>
                        <p v-if="projectTotals(project).totalDiscount > 0" class="text-xs font-normal text-muted">
                          − {{ totalDiscountPercent(project) }} % de remise
                        </p>
                      </td>
                      <td class="px-4 py-3 text-right tabular-nums">
                        <template v-if="projectTotals(project).remainingToConsume !== null">
                          <span :class="projectTotals(project).remainingToConsume! < 0 ? 'text-error' : 'text-default'">
                            {{ formatCurrency(projectTotals(project).remainingToConsume!) }}
                          </span>
                          <span v-if="fmtDays(projectTotals(project).remainingToConsume!, project.daily_rate)" class="font-normal text-muted"> ({{ fmtDays(projectTotals(project).remainingToConsume!, project.daily_rate) }})</span>
                          <span class="font-normal text-muted"> · {{ 100 - totalConsumptionPercent(project)! }}%</span>
                        </template>
                        <span v-else class="font-normal text-muted">—</span>
                      </td>
                      <td v-if="!is_shared" class="px-2" />
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>

            <!-- Summary indicators -->
            <div class="flex flex-wrap gap-3">
              <div class="flex-1 min-w-48 rounded-lg border border-default px-4 py-3">
                <div class="flex items-start justify-between gap-4">
                  <div>
                    <p class="text-xs text-muted mb-0.5">À facturer</p>
                    <p
                      class="text-base font-semibold tabular-nums"
                      :class="projectTotals(project).toInvoice > 0 ? 'text-amber-500' : 'text-success'"
                    >
                      {{ formatCurrency(projectTotals(project).toInvoice) }}
                      <span v-if="fmtDays(projectTotals(project).toInvoice, project.daily_rate)" class="text-sm font-normal text-muted">({{ fmtDays(projectTotals(project).toInvoice, project.daily_rate) }})</span>
                    </p>
                    <p v-if="projectTotals(project).toInvoice > 0 && lastInvoiceDate(project)" class="text-xs mt-0.5" :class="daysSince(lastInvoiceDate(project)!) > 30 ? 'text-error' : 'text-muted'">
                      Depuis {{ formatDuration(daysSince(lastInvoiceDate(project)!)) }}
                    </p>
                  </div>
                  <div v-if="projectTotals(project).toPay > 0" class="text-right">
                    <p class="text-xs text-muted mb-0.5">À payer</p>
                    <p v-if="projectTotals(project).unpaidDiscount > 0" class="text-sm font-normal text-muted line-through tabular-nums">
                      {{ formatCurrency(projectTotals(project).toPay + projectTotals(project).unpaidDiscount) }}
                    </p>
                    <p class="text-base font-semibold tabular-nums text-amber-500">
                      {{ formatCurrency(projectTotals(project).toPay) }}
                      <span v-if="fmtDays(projectTotals(project).toPay, project.daily_rate)" class="text-sm font-normal text-muted">({{ fmtDays(projectTotals(project).toPay, project.daily_rate) }})</span>
                    </p>
                    <p v-if="projectTotals(project).unpaidDiscount > 0" class="text-xs text-muted">
                      − {{ unpaidDiscountPercent(project) }} %
                      de remise sur facture(s) non payée(s)
                    </p>
                    <p v-if="oldestUnpaidInvoiceDate(project)" class="text-xs mt-0.5" :class="daysSince(oldestUnpaidInvoiceDate(project)!) > 30 ? 'text-error' : 'text-muted'">
                      Depuis {{ formatDuration(daysSince(oldestUnpaidInvoiceDate(project)!)) }}
                    </p>
                  </div>
                </div>
              </div>
              <div v-if="projectTotals(project).remainingToConsume !== null" class="flex-1 min-w-48 rounded-lg border border-default px-4 py-3">
                <div class="flex items-start justify-between gap-4 mb-2">
                  <div>
                    <p
                      class="text-base font-semibold tabular-nums"
                      :class="consumptionColorClass(totalConsumptionPercent(project)!)"
                    >
                      {{ totalConsumptionPercent(project) }}%
                    </p>
                    <p class="text-xs text-muted">consommé</p>
                  </div>
                  <div class="text-right">
                    <p class="text-xs text-muted mb-0.5">Reste à consommer</p>
                    <p
                      class="text-base font-semibold tabular-nums"
                      :class="projectTotals(project).remainingToConsume! < 0 ? 'text-error' : 'text-default'"
                    >
                      {{ formatCurrency(projectTotals(project).remainingToConsume!) }}
                      <span v-if="fmtDays(projectTotals(project).remainingToConsume!, project.daily_rate)" class="text-sm font-normal text-muted">({{ fmtDays(projectTotals(project).remainingToConsume!, project.daily_rate) }})</span>
                    </p>
                  </div>
                </div>
                <div class="h-1.5 w-full rounded-full bg-muted overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all"
                    :class="consumptionBarClass(totalConsumptionPercent(project)!)"
                    :style="{ width: `${Math.min(totalConsumptionPercent(project)!, 100)}%` }"
                  />
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </main>

    <!-- Invoice modal -->
    <UModal v-model:open="invoiceOpen" :title="editingInvoiceId ? 'Modifier la facture' : 'Ajouter une facture'">
      <template #body>
        <form id="invoice-form" class="space-y-4" @submit.prevent="submitInvoice">
          <UFormField v-if="editingInvoiceId && projects.length > 1" label="Projet" :error="form.errors.project_id">
            <USelect
              v-model="form.project_id"
              :items="projectSelectItems"
              class="w-full"
            />
          </UFormField>
          <UFormField label="Montant (€)" required :error="form.errors.amount">
            <UInput
              v-model="form.amount"
              type="number"
              min="0"
              step="0.01"
              placeholder="0.00"
              class="w-full"
            />
          </UFormField>
          <div class="grid grid-cols-2 gap-4">
            <UFormField label="Remise (%)">
              <UInput
                v-model="discountPercentModel"
                type="number"
                min="0"
                max="100"
                step="1"
                placeholder="0"
                class="w-full"
              />
            </UFormField>
            <UFormField label="Remise (€)" :error="form.errors.discount_amount">
              <UInput
                v-model="discountAmountModel"
                type="number"
                min="0"
                step="0.01"
                placeholder="0.00"
                class="w-full"
              />
            </UFormField>
          </div>
          <p class="text-xs text-muted">
            Montant net perçu : <span class="font-medium text-default">{{ formatCurrency(netAmountPreview) }}</span>
          </p>
          <UFormField label="Facturé le" required :error="form.errors.created_at">
            <DateInput v-model="form.created_at" />
          </UFormField>
          <UFormField label="Date de paiement" :error="form.errors.paid_at">
            <DateInput v-model="form.paid_at" />
          </UFormField>
          <UFormField label="Notes" :error="form.errors.notes">
            <UTextarea
              v-model="form.notes"
              :rows="3"
              class="w-full"
            />
          </UFormField>
        </form>
      </template>
      <template #footer="{ close }">
        <div class="flex justify-end gap-2">
          <UButton label="Annuler" color="neutral" variant="outline" @click="close" />
          <UButton label="Enregistrer" type="submit" form="invoice-form" :loading="form.processing" />
        </div>
      </template>
    </UModal>

    <ExportBillingModal
      v-model:open="exportModalOpen"
      :projects="visibleProjects"
      :clientId="visibleProjects[0]?.client.id"
      :shareToken="token"
      :isShared="is_shared"
    />
  </div>
</template>
