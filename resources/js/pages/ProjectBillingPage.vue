<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import DateInput from '@/components/DateInput.vue'
import { coverageLabel, formatDate, formatDays, formatDuration, formatMonthName, parseMonth, today } from '@/utils/date'
import { formatCurrency } from '@/utils/number'
import { timesheet } from '@/wayfinder/routes'
import { edit as editClient } from '@/wayfinder/routes/clients'
import { destroy as destroyInvoice, store as storeInvoice, update as updateInvoice } from '@/wayfinder/routes/invoices'
import { edit as editProject, index as projectsIndex } from '@/wayfinder/routes/projects'

type EntryData = { coverage: number, title: string, description: string }

type MonthInvoice = {
  id: string,
  amount: number,
  paid_at: string | null,
  created_at: string,
  notes: string | null,
}

type MonthRow = {
  month: string,
  days_worked: number,
  entries: Record<string, EntryData>,
  invoices: MonthInvoice[],
}

type ProjectWithBilling = {
  id: string,
  name: string,
  daily_rate: number,
  max_month_budget: number | null,
  max_total_budget: number | null,
  deleted_at: string | null,
  client: { id: string, name: string },
  months: MonthRow[],
  months_elapsed: number,
  months_with_entries_count: number,
}

const props = defineProps<{
  projects: ProjectWithBilling[],
  is_shared: boolean,
  shared_by?: string,
}>()

const archivedProjects = computed(() => props.projects.filter(p => p.deleted_at))
const showArchived = ref(archivedProjects.value.length === props.projects.length)

const visibleProjects = computed(() =>
  showArchived.value ? props.projects : props.projects.filter(p => !p.deleted_at),
)

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
  const totalDays = project.months.reduce((sum, m) => sum + m.days_worked, 0)
  const totalWorked = project.months.reduce((sum, m) => sum + m.days_worked * project.daily_rate, 0)
  const allInvoices = project.months.flatMap(m => m.invoices)
  const totalInvoiced = allInvoices.reduce((sum, inv) => sum + inv.amount, 0)
  const toPay = allInvoices.filter(inv => !inv.paid_at).reduce((sum, inv) => sum + inv.amount, 0)

  const totalBudgetAllocated = project.max_total_budget !== null
    ? project.max_total_budget
    : project.max_month_budget !== null
      ? project.max_month_budget * project.months_elapsed
      : null

  return {
    totalDays,
    totalWorked,
    totalInvoiced,
    totalBudgetAllocated,
    toPay,
    toInvoice: totalWorked - totalInvoiced,
    remainingToConsume: totalBudgetAllocated !== null ? totalBudgetAllocated - totalWorked : null,
  }
}

const monthWorked = (m: MonthRow, dailyRate: number) => m.days_worked * dailyRate
const monthInvoiced = (m: MonthRow) => m.invoices.reduce((s, i) => s + i.amount, 0)

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
  return budget !== null && getCumulativeWorked(project, monthIndex) > budget
}

const cumulativeRemainingToConsume = (project: ProjectWithBilling, monthIndex: number): number | null => {
  const budget = cumulativeBudgetAmount(project, monthIndex)
  if (budget === null) return null
  return budget - getCumulativeWorked(project, monthIndex)
}

const cumulativeConsumptionPercent = (project: ProjectWithBilling, monthIndex: number): number | null => {
  const budget = cumulativeBudgetAmount(project, monthIndex)
  if (budget === null || budget === 0) return null
  return Math.round(getCumulativeWorked(project, monthIndex) / budget * 100)
}

const totalConsumptionPercent = (project: ProjectWithBilling): number | null => {
  const { totalWorked, totalBudgetAllocated } = projectTotals(project)
  if (totalBudgetAllocated === null || totalBudgetAllocated === 0) return null
  return Math.round(totalWorked / totalBudgetAllocated * 100)
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

// ── Helpers ────────────────────────────────────────────────────────────────────

const fmtDays = (amount: number, dailyRate: number): string | null =>
  dailyRate > 0 ? formatDays(amount / dailyRate) : null

// ── Invoice form ───────────────────────────────────────────────────────────────

const invoiceOpen = ref(false)
const editingInvoiceId = ref<string | null>(null)
const invoiceProjectId = ref<string | null>(null)
const form = useForm({ amount: '', paid_at: '', notes: '', created_at: '' })

const openAddInvoice = (project: ProjectWithBilling) => {
  invoiceProjectId.value = project.id
  editingInvoiceId.value = null
  form.reset()
  form.created_at = today.toString()
  form.clearErrors()
  invoiceOpen.value = true
}

const openEditInvoice = (inv: MonthInvoice) => {
  editingInvoiceId.value = inv.id
  form.amount = String(inv.amount / 100)
  form.paid_at = inv.paid_at ?? ''
  form.created_at = inv.created_at
  form.notes = inv.notes ?? ''
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
      paid_at: data.paid_at || null,
      created_at: data.created_at || null,
      notes: data.notes || null,
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

const dayLabel = (date: string): string => {
  const d = new Date(`${date}T00:00:00`)
  const days = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam']
  return `${days[d.getDay()]} ${d.getDate()} ${formatMonthName(d.getMonth() + 1)}`
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
          <UButton
            v-if="archivedProjects.length > 0 && archivedProjects.length < projects.length"
            :label="showArchived ? 'Masquer les archivés' : 'Afficher les projets archivés'"
            :icon="showArchived ? 'i-lucide-eye-off' : 'i-lucide-archive'"
            color="neutral"
            variant="outline"
            size="sm"
            @click="showArchived = !showArchived"
          />
        </div>

        <!-- Client totals -->
        <div v-if="visibleProjects.length > 1" class="rounded-lg border border-default px-4 py-3">
          <p class="text-xs font-medium text-muted mb-2 uppercase tracking-wide">Total {{ visibleProjects[0]?.client.name }}</p>
          <div class="flex flex-wrap gap-6 text-sm">
            <div>
              <span class="text-muted">Jours travaillés</span>
              <span class="ml-2 font-semibold tabular-nums">{{ formatDays(clientTotals.totalDays) }}</span>
            </div>
            <div>
              <span class="text-muted">Rémunération</span>
              <span class="ml-2 font-semibold tabular-nums">{{ formatCurrency(clientTotals.totalWorked) }}</span>
            </div>
            <div v-if="clientTotals.averageDailyRate !== null">
              <span class="text-muted">TJ moyen</span>
              <span class="ml-2 font-semibold tabular-nums">{{ formatCurrency(clientTotals.averageDailyRate) }}/j</span>
            </div>
          </div>
        </div>

        <template v-for="(project, index) in visibleProjects" :key="project.id">
          <div v-if="index > 0" class="border-t-2 border-default" />

          <div class="space-y-6" :class="[project.deleted_at && ' rounded-xl bg-muted p-4']">
            <!-- Project header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <h2 class="text-base font-semibold truncate">{{ project.name }}</h2>
                  <UBadge v-if="project.deleted_at" label="Archivé" color="neutral" variant="subtle" size="sm" class="shrink-0" />
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
                              <div class="space-y-1">
                                <div
                                  v-for="(entry, date) in m.entries"
                                  :key="date"
                                  class="flex items-center gap-3 text-xs"
                                >
                                  <span class="w-32 shrink-0 text-muted">{{ dayLabel(date) }}</span>
                                  <span class="w-8 shrink-0 font-medium tabular-nums">{{ coverageLabel(entry.coverage) }}j</span>
                                  <span v-if="entry.title" class="text-muted truncate">{{ entry.title }}</span>
                                </div>
                              </div>
                            </div>
                            <p v-else class="text-xs text-muted italic">Aucune entrée pour ce mois.</p>

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
                                  <span
                                    class="font-medium tabular-nums"
                                    :class="inv.paid_at ? 'text-success' : 'text-amber-500'"
                                  >{{ formatCurrency(inv.amount) }}</span>
                                  <span v-if="inv.notes" class="text-muted truncate flex-1">{{ inv.notes }}</span>
                                  <div v-if="!is_shared" class="ml-auto flex items-center gap-1" @click.stop>
                                    <UButton icon="i-lucide-pencil" color="neutral" variant="ghost" size="2xs" @click="openEditInvoice(inv)" />
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
                      <td class="px-4 py-3 text-right tabular-nums text-success">
                        {{ formatCurrency(projectTotals(project).totalInvoiced) }}
                        <span v-if="fmtDays(projectTotals(project).totalInvoiced, project.daily_rate)" class="font-normal text-muted">({{ fmtDays(projectTotals(project).totalInvoiced, project.daily_rate) }})</span>
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
                    <p class="text-base font-semibold tabular-nums text-amber-500">
                      {{ formatCurrency(projectTotals(project).toPay) }}
                      <span v-if="fmtDays(projectTotals(project).toPay, project.daily_rate)" class="text-sm font-normal text-muted">({{ fmtDays(projectTotals(project).toPay, project.daily_rate) }})</span>
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
  </div>
</template>
