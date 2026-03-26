<script setup lang="ts">
import { ref, computed } from 'vue'
import { Bar, Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, ArcElement, Tooltip, Legend } from 'chart.js'
import { ChevronLeft, ChevronRight, TrendingUp, Calendar, Users, DollarSign } from 'lucide-vue-next'
import type { CRAEntry, Client, Project } from '../types'
import Card from '../components/ui/Card.vue'
import CardHeader from '../components/ui/CardHeader.vue'
import CardTitle from '../components/ui/CardTitle.vue'
import CardContent from '../components/ui/CardContent.vue'
import Tabs from '../components/ui/Tabs.vue'

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Tooltip, Legend)

const props = defineProps<{
    entries: CRAEntry[]
    clients: Client[]
    projects: Project[]
    defaultTjm: number
}>()

const now = new Date()
const viewMode = ref<'month' | 'year'>('month')
const selectedYear = ref(now.getFullYear())
const selectedMonth = ref(now.getMonth() + 1)

const VIEW_TABS = [
    { value: 'month', label: 'Mensuel' },
    { value: 'year', label: 'Annuel' },
]

const MONTHS_FR = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']
const MONTHS_SHORT = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc']

function getClient(id: string) { return props.clients.find(c => c.id === id) }
function getProject(id: string) { return props.projects.find(p => p.id === id) }
function getProjectTjm(projectId: string) { return getProject(projectId)?.tjm ?? props.defaultTjm }
function sumDays(ents: CRAEntry[]) { return ents.reduce((acc, e) => acc + e.value, 0) }
function sumRevenue(ents: CRAEntry[]) { return ents.reduce((acc, e) => acc + e.value * getProjectTjm(e.projectId), 0) }
function getWorkingDays(year: number, month: number) {
    let count = 0
    const d = new Date(year, month - 1, 1)
    while (d.getMonth() === month - 1) { if (d.getDay() !== 0 && d.getDay() !== 6) count++; d.setDate(d.getDate() + 1) }
    return count
}
function getEntriesForMonth(year: number, month: number) {
    return props.entries.filter(e => { const d = new Date(e.date); return d.getFullYear() === year && d.getMonth() + 1 === month })
}

function prevPeriod() {
    if (viewMode.value === 'month') {
        if (selectedMonth.value === 1) { selectedMonth.value = 12; selectedYear.value-- }
        else selectedMonth.value--
    } else {
        selectedYear.value--
    }
}

function nextPeriod() {
    if (viewMode.value === 'month') {
        if (selectedMonth.value === 12) { selectedMonth.value = 1; selectedYear.value++ }
        else selectedMonth.value++
    } else {
        selectedYear.value++
    }
}

const periodLabel = computed(() =>
    viewMode.value === 'month' ? `${MONTHS_FR[selectedMonth.value - 1]} ${selectedYear.value}` : `${selectedYear.value}`
)

const periodEntries = computed(() => {
    if (viewMode.value === 'month') return getEntriesForMonth(selectedYear.value, selectedMonth.value)
    return props.entries.filter(e => new Date(e.date).getFullYear() === selectedYear.value)
})

const totalDays = computed(() => sumDays(periodEntries.value))
const totalRevenue = computed(() => sumRevenue(periodEntries.value))
const workingDays = computed(() => {
    if (viewMode.value === 'month') return getWorkingDays(selectedYear.value, selectedMonth.value)
    let total = 0
    for (let m = 1; m <= 12; m++) total += getWorkingDays(selectedYear.value, m)
    return total
})
const avgTjm = computed(() => totalDays.value === 0 ? 0 : Math.round(totalRevenue.value / totalDays.value))
const fillRate = computed(() => workingDays.value > 0 ? Math.round((totalDays.value / workingDays.value) * 100) : 0)

const clientRecap = computed(() =>
    props.clients
        .map(client => {
            const clientProjects = props.projects.filter(p => p.clientId === client.id)
            const clientEntries = periodEntries.value.filter(e => clientProjects.some(p => p.id === e.projectId))
            return { ...client, days: sumDays(clientEntries), revenue: sumRevenue(clientEntries) }
        })
        .filter(c => c.days > 0)
        .sort((a, b) => b.revenue - a.revenue)
)

const projectRecap = computed(() =>
    props.projects
        .map(project => {
            const ents = periodEntries.value.filter(e => e.projectId === project.id)
            return { ...project, days: sumDays(ents), revenue: sumRevenue(ents), client: getClient(project.clientId) }
        })
        .filter(p => p.days > 0)
        .sort((a, b) => b.revenue - a.revenue)
)

const barChartData = computed(() => {
    if (viewMode.value === 'year') {
        const data = MONTHS_SHORT.map((_, i) => sumDays(getEntriesForMonth(selectedYear.value, i + 1)))
        return {
            labels: MONTHS_SHORT,
            datasets: [{
                label: 'Jours facturés',
                data,
                backgroundColor: data.map((_, i) => i === selectedMonth.value - 1 ? 'var(--primary)' : 'var(--muted)'),
                borderRadius: 4,
                borderSkipped: false,
            }],
        }
    }
    const year = selectedYear.value
    const month = selectedMonth.value
    const daysInMonth = new Date(year, month, 0).getDate()
    const labels: string[] = []
    const dataByDay: number[] = []
    for (let d = 1; d <= daysInMonth; d++) {
        const dateObj = new Date(year, month - 1, d)
        if (dateObj.getDay() !== 0 && dateObj.getDay() !== 6) {
            const mm = String(month).padStart(2, '0')
            const dd = String(d).padStart(2, '0')
            labels.push(`${d}`)
            dataByDay.push(props.entries.filter(e => e.date === `${year}-${mm}-${dd}`).reduce((s, e) => s + e.value, 0))
        }
    }
    return {
        labels,
        datasets: [{ label: 'Jours', data: dataByDay, backgroundColor: 'var(--primary)', borderRadius: 4, borderSkipped: false }],
    }
})

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: (ctx: { parsed: { y: number } }) => `${ctx.parsed.y} j` } },
    },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 10 } } },
    },
}

const donutChartData = computed(() => ({
    labels: clientRecap.value.map(c => c.name),
    datasets: [{
        data: clientRecap.value.map(c => c.days),
        backgroundColor: clientRecap.value.map(c => c.color),
        borderWidth: 2,
        borderColor: 'transparent',
    }],
}))

const donutChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '65%',
    plugins: {
        legend: { position: 'bottom' as const, labels: { font: { size: 11 }, padding: 12 } },
        tooltip: { callbacks: { label: (ctx: { label: string; parsed: number }) => `${ctx.label}: ${ctx.parsed} j` } },
    },
}

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(amount)
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-lg font-semibold text-foreground">Récapitulatif</h1>
            <div class="flex items-center gap-3">
                <Tabs v-model="viewMode" :tabs="VIEW_TABS" />
                <div class="flex items-center gap-1">
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-md border border-border hover:bg-accent transition-colors" @click="prevPeriod">
                        <ChevronLeft class="h-4 w-4" />
                    </button>
                    <span class="min-w-[130px] text-center text-sm font-medium">{{ periodLabel }}</span>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-md border border-border hover:bg-accent transition-colors" @click="nextPeriod">
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>Jours facturés</CardTitle>
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold text-foreground">{{ totalDays }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">/ {{ workingDays }} jours ouvrés</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>Chiffre d'affaires</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold text-foreground">{{ formatCurrency(totalRevenue) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ periodLabel }}</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>Taux de remplissage</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold text-foreground">{{ fillRate }}%</p>
                    <div class="mt-2 h-1.5 w-full rounded-full bg-secondary">
                        <div class="h-1.5 rounded-full bg-primary transition-all" :style="{ width: `${Math.min(fillRate, 100)}%` }" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>TJM moyen</CardTitle>
                        <DollarSign class="h-4 w-4 text-muted-foreground" />
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold text-foreground">{{ avgTjm ? formatCurrency(avgTjm) : '—' }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">par jour facturé</p>
                </CardContent>
            </Card>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Activité {{ viewMode === 'year' ? 'annuelle' : 'mensuelle' }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="h-56"><Bar :data="barChartData" :options="barChartOptions" /></div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>Répartition par client</CardTitle></CardHeader>
                <CardContent>
                    <div v-if="clientRecap.length > 0" class="h-56">
                        <Doughnut :data="donutChartData" :options="donutChartOptions" />
                    </div>
                    <div v-else class="flex h-56 items-center justify-center">
                        <p class="text-sm text-muted-foreground">Aucune donnée</p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader><CardTitle>Par client</CardTitle></CardHeader>
                <CardContent>
                    <div v-if="clientRecap.length > 0">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-border">
                                    <th class="pb-2 text-left text-xs font-medium text-muted-foreground">Client</th>
                                    <th class="pb-2 text-right text-xs font-medium text-muted-foreground">Jours</th>
                                    <th class="pb-2 text-right text-xs font-medium text-muted-foreground">CA</th>
                                    <th class="pb-2 text-right text-xs font-medium text-muted-foreground">Part</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="client in clientRecap" :key="client.id" class="border-b border-border/50 last:border-0">
                                    <td class="py-2">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: client.color }" />
                                            <span class="text-foreground">{{ client.name }}</span>
                                        </div>
                                    </td>
                                    <td class="py-2 text-right text-foreground">{{ client.days }}</td>
                                    <td class="py-2 text-right text-foreground">{{ formatCurrency(client.revenue) }}</td>
                                    <td class="py-2 text-right text-muted-foreground">{{ totalRevenue > 0 ? Math.round((client.revenue / totalRevenue) * 100) : 0 }}%</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="border-t border-border">
                                    <td class="pt-2 font-medium text-foreground">Total</td>
                                    <td class="pt-2 text-right font-medium text-foreground">{{ totalDays }}</td>
                                    <td class="pt-2 text-right font-medium text-foreground">{{ formatCurrency(totalRevenue) }}</td>
                                    <td class="pt-2 text-right text-muted-foreground">100%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">Aucune donnée pour cette période</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>Par projet</CardTitle></CardHeader>
                <CardContent>
                    <div v-if="projectRecap.length > 0">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-border">
                                    <th class="pb-2 text-left text-xs font-medium text-muted-foreground">Projet</th>
                                    <th class="pb-2 text-right text-xs font-medium text-muted-foreground">Jours</th>
                                    <th class="pb-2 text-right text-xs font-medium text-muted-foreground">CA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="project in projectRecap" :key="project.id" class="border-b border-border/50 last:border-0">
                                    <td class="py-2">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: project.client?.color ?? '#888' }" />
                                            <div class="min-w-0">
                                                <p class="truncate text-foreground">{{ project.name }}</p>
                                                <p class="text-xs text-muted-foreground">{{ project.client?.name ?? '—' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-2 text-right text-foreground">{{ project.days }}</td>
                                    <td class="py-2 text-right text-foreground">{{ formatCurrency(project.revenue) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">Aucune donnée pour cette période</p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
