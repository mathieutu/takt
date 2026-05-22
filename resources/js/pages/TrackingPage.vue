<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, useHttp } from '@inertiajs/vue3'
import { Pencil } from 'lucide-vue-next'
import Dialog from '../components/ui/Dialog.vue'
import Select from '../components/ui/Select.vue'
import { store as storeBilling } from '@/routes/projects/billing'
import { update as updateBilling } from '@/routes/billing'
import { budget as updateBudget } from '@/routes/projects'
import { index as trackingIndex } from '@/routes/tracking'

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
}

// ── Budget dialog ─────────────────────────────────────────────────────────────

const editingBudget = ref<{ project: ProjectData; value: string } | null>(null)

function openBudgetEdit(project: ProjectData) {
    editingBudget.value = {
        project,
        value: project.max_budget !== null ? String(project.max_budget) : '',
    }
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
    <div class="flex min-h-screen flex-col bg-background">
        <main class="flex-1 px-6 py-8">
            <div class="mx-auto max-w-5xl space-y-6">

                <!-- Header -->
                <div>
                    <h1 class="text-lg font-semibold text-foreground">Suivi facturation</h1>
                    <p class="text-sm text-muted-foreground">Sélectionnez un client pour voir le détail de facturation par projet.</p>
                </div>

                <!-- Client dropdown -->
                <div class="flex items-center gap-3">
                    <label class="text-sm font-medium text-foreground">Client</label>
                    <div class="w-72">
                        <Select
                            :model-value="selectedClientId"
                            :options="clientOptions"
                            placeholder="Choisir un client…"
                            @change="selectClient"
                        />
                    </div>
                </div>

                <!-- Empty states -->
                <p v-if="!selectedClientId" class="text-sm text-muted-foreground">
                    Aucun client sélectionné.
                </p>
                <p v-else-if="localProjects.length === 0" class="text-sm text-muted-foreground">
                    Ce client n'a aucun projet.
                </p>

                <!-- Project cards -->
                <div v-else class="space-y-6">
                    <div
                        v-for="project in localProjects"
                        :key="project.id"
                        class="rounded-lg border border-border bg-card"
                    >
                        <!-- Card header -->
                        <div class="flex items-center justify-between border-b border-border px-5 py-4">
                            <div>
                                <h2 class="text-sm font-semibold text-foreground">{{ project.name }}</h2>
                                <p class="text-xs text-muted-foreground">TJ : {{ project.daily_rate.toLocaleString('fr-FR') }} €/j</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-muted-foreground">Budget max :</span>
                                <span class="text-sm font-medium text-foreground">
                                    {{ localMaxBudget(project.id) !== null ? fmt(localMaxBudget(project.id)!) : '—' }}
                                </span>
                                <button
                                    type="button"
                                    class="flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground"
                                    @click="openBudgetEdit(project)"
                                >
                                    <Pencil class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>

                        <!-- Monthly table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-border bg-muted/40">
                                        <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground">Mois</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-muted-foreground">Jours</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-muted-foreground">Fact. théorique</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-muted-foreground">Fact. effectif</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-muted-foreground">Max théorique</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-muted-foreground">Écart eff/théo</th>
                                        <th class="px-4 py-2 w-10"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="month in localMonths(project.id)"
                                        :key="month.month"
                                        class="border-b border-border last:border-0 hover:bg-muted/20"
                                    >
                                        <td class="px-4 py-2.5 font-medium text-foreground">{{ formatMonth(month.month) }}</td>
                                        <td class="px-4 py-2.5 text-right text-foreground">{{ fmtDays(month.days_worked) }}</td>
                                        <td class="px-4 py-2.5 text-right text-foreground">{{ fmt(month.days_worked * project.daily_rate) }}</td>
                                        <td class="px-4 py-2.5 text-right font-medium"
                                            :class="month.amount_billed > 0 ? 'text-emerald-600' : 'text-muted-foreground'">
                                            {{ month.amount_billed > 0 ? fmt(month.amount_billed) : '—' }}
                                        </td>
                                        <td class="px-4 py-2.5 text-right text-foreground">
                                            {{ localMaxBudget(project.id) !== null ? fmt(localMaxBudget(project.id)!) : '—' }}
                                        </td>
                                        <td class="px-4 py-2.5 text-right"
                                            :class="month.amount_billed - (month.days_worked * project.daily_rate) < 0 ? 'text-amber-600' : 'text-emerald-600'">
                                            {{ fmt(month.amount_billed - (month.days_worked * project.daily_rate)) }}
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <button
                                                type="button"
                                                class="flex h-6 w-6 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground"
                                                @click="openBillingDialog(project, month)"
                                            >
                                                <Pencil class="h-3 w-3" />
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="localMonths(project.id).length === 0">
                                        <td colspan="7" class="px-4 py-6 text-center text-sm text-muted-foreground">
                                            Aucune entrée CRA pour ce projet.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Summary footer -->
                        <div class="border-t border-border bg-muted/30 px-5 py-4">
                            <div class="grid grid-cols-2 gap-x-8 gap-y-3 sm:grid-cols-4">
                                <div>
                                    <p class="text-xs text-muted-foreground">Total jours</p>
                                    <p class="text-sm font-semibold text-foreground">{{ fmtDays(projectSummary(project).totalDays) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Fact. théorique</p>
                                    <p class="text-sm font-semibold text-foreground">{{ fmt(projectSummary(project).totalTheorique) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Fact. effectif</p>
                                    <p class="text-sm font-semibold text-emerald-600">{{ fmt(projectSummary(project).totalEffectif) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Reste à facturer</p>
                                    <p class="text-sm font-semibold text-amber-600">{{ fmt(projectSummary(project).resteAFacturer) }}</p>
                                </div>
                                <template v-if="projectSummary(project).maxBudget !== null">
                                    <div>
                                        <p class="text-xs text-muted-foreground">Budget max</p>
                                        <p class="text-sm font-semibold text-foreground">{{ fmt(projectSummary(project).maxBudget!) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-muted-foreground">Reste à consommer</p>
                                        <p class="text-sm font-semibold"
                                           :class="projectSummary(project).resteAConsommer! < 0 ? 'text-destructive' : 'text-foreground'">
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

        <!-- Billing dialog -->
        <Dialog
            :open="editingBilling !== null"
            title="Saisir la facturation"
            @close="editingBilling = null"
        >
            <div v-if="editingBilling" class="space-y-4">
                <p class="text-sm text-muted-foreground">
                    <span class="font-medium text-foreground">{{ editingBilling.project.name }}</span>
                    — {{ formatMonth(editingBilling.month.month) }}
                </p>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Montant facturé (€)<span class="text-destructive ml-0.5">*</span></label>
                    <input
                        v-model="billingForm.amount_billed"
                        type="number"
                        min="0"
                        step="0.01"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                    />
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Date de paiement</label>
                    <input
                        v-model="billingForm.payment_date"
                        type="date"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                    />
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Notes</label>
                    <textarea
                        v-model="billingForm.notes"
                        rows="3"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                    />
                </div>

                <p v-if="billingError" class="text-xs text-destructive">{{ billingError }}</p>

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent"
                        @click="editingBilling = null"
                    >Annuler</button>
                    <button
                        type="button"
                        :disabled="billingHttp.processing"
                        class="h-9 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50"
                        @click="saveBilling"
                    >{{ billingHttp.processing ? 'Enregistrement…' : 'Enregistrer' }}</button>
                </div>
            </div>
        </Dialog>

        <!-- Budget dialog -->
        <Dialog
            :open="editingBudget !== null"
            title="Modifier le budget max"
            @close="editingBudget = null"
        >
            <div v-if="editingBudget" class="space-y-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">
                        Budget max (€)
                        <span class="font-normal text-muted-foreground">— laisser vide pour ne pas définir</span>
                    </label>
                    <input
                        v-model="editingBudget.value"
                        type="number"
                        min="0"
                        step="0.01"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                    />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent"
                        @click="editingBudget = null"
                    >Annuler</button>
                    <button
                        type="button"
                        :disabled="budgetHttp.processing"
                        class="h-9 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50"
                        @click="saveBudget"
                    >{{ budgetHttp.processing ? 'Enregistrement…' : 'Enregistrer' }}</button>
                </div>
            </div>
        </Dialog>
    </div>
</template>
