<script setup lang="ts">
import { ref, computed } from 'vue'
import { Download, Copy, FileText, Check } from 'lucide-vue-next'
import { useToast } from '../composables/useToast'
import type { CRAEntry, Client, Project, User } from '../types'
import Card from '../components/ui/Card.vue'
import CardHeader from '../components/ui/CardHeader.vue'
import CardTitle from '../components/ui/CardTitle.vue'
import CardContent from '../components/ui/CardContent.vue'
import ToastContainer from '../components/ui/ToastContainer.vue'

const props = defineProps<{
    entries: CRAEntry[]
    clients: Client[]
    projects: Project[]
    user: User
    defaultTjm: number
}>()

const { toast } = useToast()

const now = new Date()
const selectedYear = ref(now.getFullYear())
const selectedMonth = ref(now.getMonth() + 1)
const selectedClientId = ref('all')
const copied = ref(false)

const MONTHS_FR = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']

function getClient(id: string) { return props.clients.find(c => c.id === id) }
function getProject(id: string) { return props.projects.find(p => p.id === id) }
function getProjectTjm(projectId: string) { return getProject(projectId)?.tjm ?? props.defaultTjm }
function sumDays(ents: CRAEntry[]) { return ents.reduce((acc, e) => acc + e.value, 0) }
function sumRevenue(ents: CRAEntry[]) { return ents.reduce((acc, e) => acc + e.value * getProjectTjm(e.projectId), 0) }

const filteredEntries = computed(() =>
    props.entries.filter(e => {
        const d = new Date(e.date)
        const matchYear = d.getFullYear() === selectedYear.value
        const matchMonth = d.getMonth() + 1 === selectedMonth.value
        const project = getProject(e.projectId)
        const matchClient = selectedClientId.value === 'all' || project?.clientId === selectedClientId.value
        return matchYear && matchMonth && matchClient
    })
)

const totalDays = computed(() => sumDays(filteredEntries.value))
const totalRevenue = computed(() => sumRevenue(filteredEntries.value))

const previewData = computed(() =>
    filteredEntries.value
        .map(e => {
            const project = getProject(e.projectId)
            const client = project ? getClient(project.clientId) : undefined
            const tjm = getProjectTjm(e.projectId)
            return {
                date: e.date,
                clientName: client?.name ?? '—',
                clientColor: client?.color ?? '#888',
                projectName: project?.name ?? '—',
                value: e.value,
                tjm,
                revenue: e.value * tjm,
                note: e.note ?? '',
            }
        })
        .sort((a, b) => a.date.localeCompare(b.date))
)

const periodLabel = computed(() => `${MONTHS_FR[selectedMonth.value - 1]} ${selectedYear.value}`)

function formatDate(date: string) {
    return new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(amount)
}

function exportCSV() {
    if (previewData.value.length === 0) {
        toast('Aucune donnée à exporter', 'error')
        return
    }
    const headers = ['Date', 'Client', 'Projet', 'Jours', 'TJM (€)', 'Montant (€)', 'Note']
    const rows = previewData.value.map(row => [
        formatDate(row.date),
        row.clientName,
        row.projectName,
        row.value.toString().replace('.', ','),
        row.tjm.toString(),
        row.revenue.toString(),
        row.note,
    ])
    const csvContent = [headers, ...rows]
        .map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(';'))
        .join('\n')
    const BOM = '\uFEFF'
    const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `CRA_${MONTHS_FR[selectedMonth.value - 1]}_${selectedYear.value}.csv`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
    toast('Export CSV téléchargé', 'success')
}

function copyText() {
    if (previewData.value.length === 0) {
        toast('Aucune donnée à copier', 'error')
        return
    }
    const lines = [
        `CRA — ${MONTHS_FR[selectedMonth.value - 1]} ${selectedYear.value}`,
        `Freelance : ${props.user.name}`,
        '',
        'Date       | Client              | Projet                     | Jours | Montant',
        '-----------|---------------------|----------------------------|-------|----------',
        ...previewData.value.map(row =>
            `${formatDate(row.date).padEnd(10)} | ${row.clientName.padEnd(19)} | ${row.projectName.padEnd(26)} | ${String(row.value).padEnd(5)} | ${formatCurrency(row.revenue)}`
        ),
        '',
        `Total : ${totalDays.value} jours — ${formatCurrency(totalRevenue.value)}`,
    ]
    navigator.clipboard.writeText(lines.join('\n')).then(() => {
        copied.value = true
        toast('Copié dans le presse-papier', 'success')
        setTimeout(() => { copied.value = false }, 2000)
    })
}
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1 class="text-lg font-semibold text-foreground">Export</h1>
            <p class="text-sm text-muted-foreground">Exportez vos saisies CRA en CSV ou texte</p>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-4">
                <Card>
                    <CardHeader><CardTitle>Configuration</CardTitle></CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground">Année</label>
                                <select v-model="selectedYear" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                                    <option v-for="y in [2024, 2025, 2026, 2027]" :key="y" :value="y">{{ y }}</option>
                                </select>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground">Mois</label>
                                <select v-model="selectedMonth" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                                    <option v-for="(name, i) in MONTHS_FR" :key="i" :value="i + 1">{{ name }}</option>
                                </select>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground">Client</label>
                                <select v-model="selectedClientId" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                                    <option value="all">Tous les clients</option>
                                    <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                                </select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Aperçu rapide</CardTitle></CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Période</span>
                                <span class="font-medium text-foreground">{{ periodLabel }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Entrées</span>
                                <span class="font-medium text-foreground">{{ filteredEntries.length }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Jours</span>
                                <span class="font-medium text-foreground">{{ totalDays }}</span>
                            </div>
                            <div class="flex justify-between text-sm border-t border-border pt-2">
                                <span class="text-muted-foreground">CA total</span>
                                <span class="font-semibold text-foreground">{{ formatCurrency(totalRevenue) }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex flex-col gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 h-9 w-full rounded-md bg-primary text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
                        @click="exportCSV"
                    >
                        <Download class="h-4 w-4" />
                        Télécharger CSV
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 h-9 w-full rounded-md border border-border bg-background text-sm font-medium text-foreground hover:bg-accent transition-colors"
                        @click="copyText"
                    >
                        <component :is="copied ? Check : Copy" class="h-4 w-4" :class="{ 'text-emerald-500': copied }" />
                        {{ copied ? 'Copié !' : 'Copier en texte' }}
                    </button>
                </div>
            </div>

            <div class="lg:col-span-2">
                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <FileText class="h-4 w-4 text-muted-foreground" />
                            <CardTitle>Prévisualisation — {{ periodLabel }}</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div v-if="previewData.length > 0" class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-border">
                                        <th class="pb-2 text-left text-xs font-medium text-muted-foreground">Date</th>
                                        <th class="pb-2 text-left text-xs font-medium text-muted-foreground">Client</th>
                                        <th class="pb-2 text-left text-xs font-medium text-muted-foreground">Projet</th>
                                        <th class="pb-2 text-right text-xs font-medium text-muted-foreground">Jours</th>
                                        <th class="pb-2 text-right text-xs font-medium text-muted-foreground">TJM</th>
                                        <th class="pb-2 text-right text-xs font-medium text-muted-foreground">Montant</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, idx) in previewData" :key="idx" class="border-b border-border/50 last:border-0">
                                        <td class="py-2 text-foreground whitespace-nowrap">{{ formatDate(row.date) }}</td>
                                        <td class="py-2">
                                            <div class="flex items-center gap-1.5">
                                                <div class="h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: row.clientColor }" />
                                                <span class="text-foreground">{{ row.clientName }}</span>
                                            </div>
                                        </td>
                                        <td class="py-2 text-foreground">{{ row.projectName }}</td>
                                        <td class="py-2 text-right text-foreground">{{ row.value }}</td>
                                        <td class="py-2 text-right text-muted-foreground">{{ formatCurrency(row.tjm) }}</td>
                                        <td class="py-2 text-right font-medium text-foreground">{{ formatCurrency(row.revenue) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="border-t-2 border-border">
                                        <td colspan="3" class="pt-2 font-semibold text-foreground">Total</td>
                                        <td class="pt-2 text-right font-semibold text-foreground">{{ totalDays }}</td>
                                        <td />
                                        <td class="pt-2 text-right font-semibold text-foreground">{{ formatCurrency(totalRevenue) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center py-12 text-center">
                            <FileText class="mb-2 h-8 w-8 text-muted-foreground/50" />
                            <p class="text-sm text-muted-foreground">Aucune saisie pour cette période</p>
                            <p class="mt-1 text-xs text-muted-foreground">Modifiez les filtres ou saisissez des données dans le CRA</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>

    <ToastContainer />
</template>
