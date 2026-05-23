<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, useHttp } from '@inertiajs/vue3'
import { store as storeBilling } from '@/wayfinder/routes/projects/billing'
import { update as updateBilling } from '@/wayfinder/routes/billing'
import { budget as updateBudget } from '@/wayfinder/routes/projects'
import { index as trackingIndex } from '@/wayfinder/routes/tracking'

const billingHttp = useHttp({ month: '', amount_billed: 0, payment_date: null as string | null, notes: null as string | null })
const budgetHttp = useHttp({ max_budget: null as number | null })

type ClientOption = { id: number; name: string; daily_rate: number }

type MonthRow = {
    month: string
    days_worked: number
    billing_entry_id: number | null
    amount_billed: number
    payment_date: string | null
    notes: string | null
}

type ProjectData = {
    id: number
    name: string
    daily_rate: number
    max_budget: number | null
    client_name: string
    months: MonthRow[]
}

const props = withDefaults(defineProps<{
    clients?: ClientOption[]
    selectedClientId?: number | null
    projects?: ProjectData[]
}>(), {
    clients:          () => [],
    selectedClientId: null,
    projects:         () => [],
})

const localProjects = ref<ProjectData[]>(
    props.projects.map(p => ({ ...p, months: p.months.map(m => ({ ...m })) }))
)

// ── Billing dialog ────────────────────────────────────────────────────────────

const billingOpen    = ref(false)
const editingBilling = ref<{ project: ProjectData; month: MonthRow } | null>(null)
const billingForm    = ref({ amount_billed: '', payment_date: '', notes: '' })
const billingError   = ref<string | null>(null)

function openBillingDialog(project: ProjectData, month: MonthRow) {
    editingBilling.value = { project, month }
    billingForm.value = {
        amount_billed: String(month.amount_billed),
        payment_date:  month.payment_date ?? '',
        notes:         month.notes ?? '',
    }
    billingError.value = null
    billingOpen.value = true
}

async function saveBilling() {
    if (!editingBilling.value) return
    billingError.value = null

    const { project, month } = editingBilling.value
    const isNew = month.billing_entry_id === null
    const url   = isNew
        ? storeBilling(project.id).url
        : updateBilling(month.billing_entry_id!).url

    billingHttp.month         = month.month
    billingHttp.amount_billed = parseFloat(billingForm.value.amount_billed) || 0
    billingHttp.payment_date  = billingForm.value.payment_date || null
    billingHttp.notes         = billingForm.value.notes || null

    const data = isNew
        ? await billingHttp.post(url)
        : await billingHttp.put(url)

    if (!data) {
        billingError.value = 'Une erreur est survenue, veuillez réessayer.'
        return
    }

    const lp = localProjects.value.find(p => p.id === project.id)
    const lm = lp?.months.find(m => m.month === month.month)
    if (lm) {
        lm.billing_entry_id = data.id
        lm.amount_billed    = data.amount_billed
        lm.payment_date     = data.payment_date
        lm.notes            = data.notes
    }

    editingBilling.value = null
    billingOpen.value = false
}

// ── Budget dialog ─────────────────────────────────────────────────────────────

const budgetOpen    = ref(false)
const editingBudget = ref<{ project: ProjectData; value: string } | null>(null)

function openBudgetEdit(project: ProjectData) {
    editingBudget.value = {
        project,
        value: project.max_budget !== null ? String(project.max_budget) : '',
    }
    budgetOpen.value = true
}

async function saveBudget() {
    if (!editingBudget.value) return

    const { project, value } = editingBudget.value
    budgetHttp.max_budget = value !== '' ? parseFloat(value) : null

    const data = await budgetHttp.put(updateBudget(project.id).url)
    if (data) {
        const lp = localProjects.value.find(p => p.id === project.id)
        if (lp) lp.max_budget = data.max_budget
        editingBudget.value = null
        budgetOpen.value = false
    }
}

const clientOptions = computed(() =>
    props.clients.map(c => ({ value: c.id, label: `${c.name} — ${c.daily_rate} €/j` }))
)

function selectClient(id: string | number | null) {
    if (!id) return
    router.visit(trackingIndex.url(), { data: { client_id: id } })
}

// ── Helpers ───────────────────────────────────────────────────────────────────

const MONTHS_FR = ['Janv.','Févr.','Mars','Avr.','Mai','Juin',
                   'Juil.','Août','Sept.','Oct.','Nov.','Déc.']

function formatMonth(ym: string): string {
    const [year, month] = ym.split('-')
    return `${MONTHS_FR[parseInt(month, 10) - 1]} ${year}`
}

function fmt(n: number): string {
    return n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €'
}

function fmtDays(n: number): string {
    return n.toLocaleString('fr-FR', { minimumFractionDigits: 1, maximumFractionDigits: 2 }) + ' j'
}

function projectSummary(project: ProjectData) {
    const months = localProjects.value.find(p => p.id === project.id)?.months ?? []
    const totalDays      = months.reduce((s, m) => s + m.days_worked, 0)
    const totalTheorique = months.reduce((s, m) => s + m.days_worked * project.daily_rate, 0)
    const totalEffectif  = months.reduce((s, m) => s + m.amount_billed, 0)
    const maxBudget      = localProjects.value.find(p => p.id === project.id)?.max_budget ?? null
    return {
        totalDays,
        totalTheorique,
        totalEffectif,
        maxBudget,
        resteAFacturer:  totalTheorique - totalEffectif,
        resteAConsommer: maxBudget !== null ? maxBudget - totalTheorique : null,
    }
}

function localMonths(projectId: number): MonthRow[] {
    return localProjects.value.find(p => p.id === projectId)?.months ?? []
}

function localMaxBudget(projectId: number): number | null {
    return localProjects.value.find(p => p.id === projectId)?.max_budget ?? null
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-default">
        <main class="flex-1 px-6 py-8">
            <div class="mx-auto max-w-5xl space-y-6">

                <div>
                    <h1 class="text-lg font-semibold">Suivi facturation</h1>
                    <p class="text-sm text-muted">Sélectionnez un client pour voir le détail de facturation par projet.</p>
                </div>

                <div class="flex items-center gap-3">
                    <label class="text-sm font-medium">Client</label>
                    <USelect
                        :model-value="selectedClientId"
                        :items="clientOptions"
                        placeholder="Choisir un client…"
                        class="w-72"
                        @update:model-value="selectClient"
                    />
                </div>

                <p v-if="!selectedClientId" class="text-sm text-muted">
                    Aucun client sélectionné.
                </p>
                <p v-else-if="localProjects.length === 0" class="text-sm text-muted">
                    Ce client n'a aucun projet.
                </p>

                <div v-else class="space-y-6">
                    <div
                        v-for="project in localProjects"
                        :key="project.id"
                        class="rounded-lg border border-default bg-elevated"
                    >
                        <div class="flex items-center justify-between border-b border-default px-5 py-4">
                            <div>
                                <h2 class="text-sm font-semibold">{{ project.name }}</h2>
                                <p class="text-xs text-muted">TJ : {{ project.daily_rate.toLocaleString('fr-FR') }} €/j</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-muted">Budget max :</span>
                                <span class="text-sm font-medium">
                                    {{ localMaxBudget(project.id) !== null ? fmt(localMaxBudget(project.id)!) : '—' }}
                                </span>
                                <UButton
                                    icon="i-lucide-pencil"
                                    color="neutral"
                                    variant="ghost"
                                    size="xs"
                                    @click="openBudgetEdit(project)"
                                />
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-default bg-muted/40">
                                        <th class="px-4 py-2 text-left text-xs font-medium text-muted">Mois</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-muted">Jours</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-muted">Fact. théorique</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-muted">Fact. effectif</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-muted">Max théorique</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-muted">Écart eff/théo</th>
                                        <th class="px-4 py-2 w-10"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="month in localMonths(project.id)"
                                        :key="month.month"
                                        class="border-b border-default last:border-0 hover:bg-muted/20"
                                    >
                                        <td class="px-4 py-2.5 font-medium">{{ formatMonth(month.month) }}</td>
                                        <td class="px-4 py-2.5 text-right">{{ fmtDays(month.days_worked) }}</td>
                                        <td class="px-4 py-2.5 text-right">{{ fmt(month.days_worked * project.daily_rate) }}</td>
                                        <td class="px-4 py-2.5 text-right font-medium"
                                            :class="month.amount_billed > 0 ? 'text-emerald-600' : 'text-muted'">
                                            {{ month.amount_billed > 0 ? fmt(month.amount_billed) : '—' }}
                                        </td>
                                        <td class="px-4 py-2.5 text-right">
                                            {{ localMaxBudget(project.id) !== null ? fmt(localMaxBudget(project.id)!) : '—' }}
                                        </td>
                                        <td class="px-4 py-2.5 text-right"
                                            :class="month.amount_billed - (month.days_worked * project.daily_rate) < 0 ? 'text-amber-600' : 'text-emerald-600'">
                                            {{ fmt(month.amount_billed - (month.days_worked * project.daily_rate)) }}
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <UButton
                                                icon="i-lucide-pencil"
                                                color="neutral"
                                                variant="ghost"
                                                size="xs"
                                                @click="openBillingDialog(project, month)"
                                            />
                                        </td>
                                    </tr>
                                    <tr v-if="localMonths(project.id).length === 0">
                                        <td colspan="7" class="px-4 py-6 text-center text-sm text-muted">
                                            Aucune entrée CRA pour ce projet.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="border-t border-default bg-muted/30 px-5 py-4">
                            <div class="grid grid-cols-2 gap-x-8 gap-y-3 sm:grid-cols-4">
                                <div>
                                    <p class="text-xs text-muted">Total jours</p>
                                    <p class="text-sm font-semibold">{{ fmtDays(projectSummary(project).totalDays) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-muted">Fact. théorique</p>
                                    <p class="text-sm font-semibold">{{ fmt(projectSummary(project).totalTheorique) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-muted">Fact. effectif</p>
                                    <p class="text-sm font-semibold text-emerald-600">{{ fmt(projectSummary(project).totalEffectif) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-muted">Reste à facturer</p>
                                    <p class="text-sm font-semibold text-amber-600">{{ fmt(projectSummary(project).resteAFacturer) }}</p>
                                </div>
                                <template v-if="projectSummary(project).maxBudget !== null">
                                    <div>
                                        <p class="text-xs text-muted">Budget max</p>
                                        <p class="text-sm font-semibold">{{ fmt(projectSummary(project).maxBudget!) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-muted">Reste à consommer</p>
                                        <p class="text-sm font-semibold"
                                           :class="projectSummary(project).resteAConsommer! < 0 ? 'text-error' : ''">
                                            {{ fmt(projectSummary(project).resteAConsommer!) }}
                                        </p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <UModal v-model:open="billingOpen" title="Saisir la facturation">
            <template #body>
                <div v-if="editingBilling" class="space-y-4">
                    <p class="text-sm text-muted">
                        <span class="font-medium text-default">{{ editingBilling.project.name }}</span>
                        — {{ formatMonth(editingBilling.month.month) }}
                    </p>
                    <UFormField label="Montant facturé (€)" required>
                        <UInput
                            v-model="billingForm.amount_billed"
                            type="number"
                            min="0"
                            step="0.01"
                            class="w-full"
                        />
                    </UFormField>
                    <UFormField label="Date de paiement">
                        <UInput
                            v-model="billingForm.payment_date"
                            type="date"
                            class="w-full"
                        />
                    </UFormField>
                    <UFormField label="Notes">
                        <UTextarea
                            v-model="billingForm.notes"
                            :rows="3"
                            class="w-full"
                        />
                    </UFormField>
                    <p v-if="billingError" class="text-xs text-error">{{ billingError }}</p>
                </div>
            </template>
            <template #footer="{ close }">
                <div class="flex justify-end gap-2">
                    <UButton label="Annuler" color="neutral" variant="outline" @click="close" />
                    <UButton label="Enregistrer" :loading="billingHttp.processing" @click="saveBilling" />
                </div>
            </template>
        </UModal>

        <UModal v-model:open="budgetOpen" title="Modifier le budget max">
            <template #body>
                <div v-if="editingBudget" class="space-y-4">
                    <UFormField label="Budget max (€)">
                        <template #hint>Laisser vide pour ne pas définir</template>
                        <UInput
                            v-model="editingBudget.value"
                            type="number"
                            min="0"
                            step="0.01"
                            class="w-full"
                        />
                    </UFormField>
                </div>
            </template>
            <template #footer="{ close }">
                <div class="flex justify-end gap-2">
                    <UButton label="Annuler" color="neutral" variant="outline" @click="close" />
                    <UButton label="Enregistrer" :loading="budgetHttp.processing" @click="saveBudget" />
                </div>
            </template>
        </UModal>
    </div>
</template>
