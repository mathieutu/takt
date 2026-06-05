<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import { coverageLabel, formatDate, formatDays, formatMonthName, parseMonth } from '@/utils/date'
import { formatCurrency } from '@/utils/number'
import { destroy as destroyInvoice, update as updateInvoice } from '@/wayfinder/routes/invoices'
import { index as projectsIndex } from '@/wayfinder/routes/projects'
import { store as storeInvoice } from '@/wayfinder/routes/projects/billing'

type EntryData = { coverage: number, title: string, description: string }

type MonthInvoice = {
  id: string,
  amount: number,
  paid_at: string,
  notes: string | null,
}

type OutstandingInvoice = {
  id: string,
  amount: number,
  paid_at: null,
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
  client: { name: string },
  months: MonthRow[],
  outstanding: OutstandingInvoice[],
}

defineProps<{
  projects: ProjectWithBilling[],
  is_shared: boolean,
  shared_by?: string,
}>()

// ── Expand state ───────────────────────────────────────────────────────────────

const expandedMonths = ref<Set<string>>(new Set())

function toggleMonth(projectId: string, month: string) {
  const key = `${projectId}:${month}`
  if (expandedMonths.value.has(key)) {
    expandedMonths.value.delete(key)
  } else {
    expandedMonths.value.add(key)
  }
}

const isExpanded = (projectId: string, month: string) => expandedMonths.value.has(`${projectId}:${month}`)

// ── Totals ─────────────────────────────────────────────────────────────────────

function projectTotals(project: ProjectWithBilling) {
  let totalDays = 0
  let totalWorked = 0
  let totalInvoiced = 0

  for (const m of project.months) {
    totalDays += m.days_worked
    totalWorked += m.days_worked * project.daily_rate
    for (const inv of m.invoices) {
      totalInvoiced += inv.amount
    }
  }

  for (const inv of project.outstanding) {
    totalInvoiced += inv.amount
  }

  const totalBudgetAllocated = project.max_month_budget !== null
    ? project.max_month_budget * project.months.length
    : null

  return {
    totalDays,
    totalWorked,
    totalInvoiced,
    toInvoice: totalWorked - totalInvoiced,
    remainingToConsume: totalBudgetAllocated !== null ? totalBudgetAllocated - totalWorked : null,
  }
}

const monthWorked = (m: MonthRow, dailyRate: number) => m.days_worked * dailyRate
const monthInvoiced = (m: MonthRow) => m.invoices.reduce((s, i) => s + i.amount, 0)

// ── Invoice form ───────────────────────────────────────────────────────────────

const invoiceOpen = ref(false)
const editingInvoiceId = ref<string | null>(null)
const invoiceProjectId = ref<string | null>(null)
const form = useForm({ amount: '', paid_at: '', notes: '' })

function openAddInvoice(project: ProjectWithBilling) {
  invoiceProjectId.value = project.id
  editingInvoiceId.value = null
  form.reset()
  form.clearErrors()
  invoiceOpen.value = true
}

function openEditInvoice(inv: MonthInvoice | OutstandingInvoice) {
  editingInvoiceId.value = inv.id
  form.amount = String(inv.amount / 100)
  form.paid_at = inv.paid_at ?? ''
  form.notes = inv.notes ?? ''
  form.clearErrors()
  invoiceOpen.value = true
}

function submitInvoice() {
  const route = editingInvoiceId.value
    ? updateInvoice(editingInvoiceId.value)
    : storeInvoice(invoiceProjectId.value!)

  form
    .transform(data => ({
      amount: Math.round(Number.parseFloat(data.amount) * 100) || 0,
      paid_at: data.paid_at || null,
      notes: data.notes || null,
    }))
    .submit(route, {
      preserveScroll: true,
      onSuccess: () => {
        invoiceOpen.value = false
      },
    })
}

// ── Labels ─────────────────────────────────────────────────────────────────────

const monthLabel = (ym: string): string => {
  const { year, month } = parseMonth(ym)
  return `${formatMonthName(year, month)} ${year}`
}

const dayLabel = (date: string): string => {
  const d = new Date(`${date}T00:00:00`)
  const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
  return `${days[d.getDay()]} ${d.getDate()} ${formatMonthName(d.getFullYear(), d.getMonth() + 1)}`
}
</script>

<template>
  <div class="flex min-h-screen flex-col bg-default">
    <div
      v-if="shared_by"
      class="shrink-0 flex items-center justify-between gap-4 border-b border-primary/20 bg-primary/5 px-4 py-2.5"
    >
      <p class="text-sm text-muted">
        Shared by <span class="font-medium text-default">{{ shared_by }}</span>
      </p>
    </div>

    <main class="flex-1 px-4 py-6 md:px-8 md:py-8">
      <div class="mx-auto max-w-5xl space-y-6">
        <div v-if="!is_shared">
          <UTooltip text="Back to projects">
            <UButton
              :href="projectsIndex()"
              icon="i-lucide-arrow-left"
              color="neutral"
              variant="ghost"
              size="sm"
            />
          </UTooltip>
        </div>

        <template v-for="(project, index) in projects" :key="project.id">
          <div v-if="index > 0" class="border-t-2 border-default" />

          <!-- Project header -->
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <h2 class="text-base font-semibold truncate">{{ project.name }}</h2>
              <p class="text-xs text-muted truncate">{{ project.client.name }}</p>
            </div>
            <div class="shrink-0 flex items-end flex-col gap-3">
              <div class="flex items-center gap-4 text-sm text-muted">
                <span>{{ formatCurrency(project.daily_rate) }}/day</span>
                <span v-if="project.max_month_budget">
                  {{ formatCurrency(project.max_month_budget) }}/mo
                  ({{ formatDays(project.max_month_budget / project.daily_rate) }}) max
                </span>
              </div>
              <UButton
                v-if="!is_shared"
                label="Add invoice"
                icon="i-lucide-plus"
                size="sm"
                @click="openAddInvoice(project)"
              />
            </div>
          </div>

          <!-- Main table -->
          <div class="rounded-lg border border-default overflow-hidden">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-default bg-muted/40 text-xs text-muted">
                  <th class="px-4 py-2.5 text-left font-medium">Month</th>
                  <th class="px-4 py-2.5 text-right font-medium">Worked</th>
                  <th class="px-4 py-2.5 text-right font-medium">Invoiced</th>
                  <th class="px-4 py-2.5 text-right font-medium">Balance</th>
                  <th v-if="!is_shared" class="w-8 px-2" />
                </tr>
              </thead>
              <tbody>
                <template v-for="m in project.months" :key="m.month">
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
                      :class="monthWorked(m, project.daily_rate) > (project.max_month_budget ?? Infinity) ? 'text-error font-medium' : 'text-muted'"
                    >
                      {{ formatCurrency(monthWorked(m, project.daily_rate)) }} ({{ formatDays(m.days_worked) }})
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                      <span :class="monthInvoiced(m) > 0 ? 'text-success font-medium' : 'text-muted'">
                        {{ monthInvoiced(m) > 0 ? formatCurrency(monthInvoiced(m)) : '—' }}
                      </span>
                    </td>
                    <td class="px-4 py-2.5 text-right tabular-nums">
                      <span :class="monthInvoiced(m) >= monthWorked(m, project.daily_rate) ? 'text-success' : 'text-amber-500'">
                        {{ formatCurrency(monthInvoiced(m) - monthWorked(m, project.daily_rate)) }}
                      </span>
                    </td>
                    <td v-if="!is_shared" class="px-2" />
                  </tr>

                  <tr v-if="isExpanded(project.id, m.month)">
                    <td :colspan="is_shared ? 4 : 5" class="px-0 py-0">
                      <div class="border-b border-default bg-muted/10 px-6 py-3 space-y-3">
                        <div v-if="Object.keys(m.entries).length > 0">
                          <p class="text-xs font-medium text-muted mb-1.5 uppercase tracking-wide">Days worked</p>
                          <div class="space-y-1">
                            <div
                              v-for="(entry, date) in m.entries"
                              :key="date"
                              class="flex items-center gap-3 text-xs"
                            >
                              <span class="w-32 shrink-0 text-muted">{{ dayLabel(date) }}</span>
                              <span class="w-8 shrink-0 font-medium tabular-nums">{{ coverageLabel(entry.coverage) }}d</span>
                              <span v-if="entry.title" class="text-muted truncate">{{ entry.title }}</span>
                            </div>
                          </div>
                        </div>
                        <p v-else class="text-xs text-muted italic">No timesheet entries for this month.</p>

                        <div v-if="m.invoices.length > 0">
                          <p class="text-xs font-medium text-muted mb-1.5 uppercase tracking-wide">Invoices</p>
                          <div class="space-y-1">
                            <div
                              v-for="inv in m.invoices"
                              :key="inv.id"
                              class="flex items-center gap-3 text-xs"
                            >
                              <span class="w-28 shrink-0 text-muted">{{ formatDate(inv.paid_at) }}</span>
                              <span class="font-medium tabular-nums text-success">{{ formatCurrency(inv.amount) }}</span>
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

                <tr v-if="project.months.length === 0 && project.outstanding.length === 0">
                  <td :colspan="is_shared ? 4 : 5" class="px-4 py-8 text-center text-sm text-muted">
                    No activity recorded yet.
                  </td>
                </tr>
              </tbody>

              <tbody v-if="project.outstanding.length > 0">
                <tr class="border-t-2 border-default">
                  <td colspan="5" class="px-4 py-2 text-xs font-medium text-muted uppercase tracking-wide bg-muted/20">
                    To be paid
                  </td>
                </tr>
                <tr
                  v-for="inv in project.outstanding"
                  :key="inv.id"
                  class="border-b border-default last:border-0 hover:bg-muted/20"
                >
                  <td class="px-4 py-2.5 text-muted text-xs">—</td>
                  <td class="px-4 py-2.5 text-right" />
                  <td class="px-4 py-2.5 text-right">
                    <span class="font-medium text-amber-500 tabular-nums">{{ formatCurrency(inv.amount) }}</span>
                  </td>
                  <td class="px-4 py-2.5 text-right">
                    <span v-if="inv.notes" class="text-xs text-muted">{{ inv.notes }}</span>
                  </td>
                  <td v-if="!is_shared" class="px-2 py-2.5">
                    <div class="flex items-center gap-1">
                      <UButton icon="i-lucide-pencil" color="neutral" variant="ghost" size="2xs" @click="openEditInvoice(inv)" />
                      <UButton icon="i-lucide-trash-2" color="error" variant="ghost" size="2xs" :to="destroyInvoice(inv)" preserveScroll />
                    </div>
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
                  </td>
                  <td
                    class="px-4 py-3 text-right tabular-nums"
                    :class="projectTotals(project).totalInvoiced >= projectTotals(project).totalWorked ? 'text-success' : 'text-amber-500'"
                  >
                    {{ formatCurrency(projectTotals(project).totalInvoiced - projectTotals(project).totalWorked) }}
                  </td>
                  <td v-if="!is_shared" class="px-2" />
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Summary indicators -->
          <div class="flex flex-wrap gap-3">
            <div class="flex-1 min-w-48 rounded-lg border border-default px-4 py-3">
              <p class="text-xs text-muted mb-0.5">To invoice</p>
              <p
                class="text-base font-semibold tabular-nums"
                :class="projectTotals(project).toInvoice > 0 ? 'text-amber-500' : 'text-success'"
              >
                {{ formatCurrency(projectTotals(project).toInvoice) }}
              </p>
              <p class="text-xs text-muted mt-0.5">Worked − Invoiced</p>
            </div>
            <div v-if="projectTotals(project).remainingToConsume !== null" class="flex-1 min-w-48 rounded-lg border border-default px-4 py-3">
              <p class="text-xs text-muted mb-0.5">Remaining to consume</p>
              <p
                class="text-base font-semibold tabular-nums"
                :class="projectTotals(project).remainingToConsume! < 0 ? 'text-error' : 'text-default'"
              >
                {{ formatCurrency(projectTotals(project).remainingToConsume!) }}
              </p>
              <p class="text-xs text-muted mt-0.5">Budget allocated − Worked</p>
            </div>
          </div>
        </template>
      </div>
    </main>

    <!-- Invoice modal -->
    <UModal v-model:open="invoiceOpen" :title="editingInvoiceId ? 'Edit invoice' : 'Add invoice'">
      <template #body>
        <form id="invoice-form" class="space-y-4" @submit.prevent="submitInvoice">
          <UFormField label="Amount (€)" required :error="form.errors.amount">
            <UInput
              v-model="form.amount"
              type="number"
              min="0"
              step="0.01"
              placeholder="0.00"
              class="w-full"
            />
          </UFormField>
          <UFormField label="Payment date" :error="form.errors.paid_at">
            <UInput
              v-model="form.paid_at"
              type="date"
              class="w-full"
            />
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
          <UButton label="Cancel" color="neutral" variant="outline" @click="close" />
          <UButton label="Save" type="submit" form="invoice-form" :loading="form.processing" />
        </div>
      </template>
    </UModal>
  </div>
</template>
