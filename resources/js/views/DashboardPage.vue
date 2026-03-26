<script setup lang="ts">
import { computed } from 'vue'
import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Tooltip } from 'chart.js'
import { TrendingUp, Calendar, Users, FolderKanban } from 'lucide-vue-next'
import type { User, Client, Project, CRAEntry } from '../types'
import Card from '../components/ui/Card.vue'
import CardHeader from '../components/ui/CardHeader.vue'
import CardTitle from '../components/ui/CardTitle.vue'
import CardContent from '../components/ui/CardContent.vue'
import Badge from '../components/ui/Badge.vue'

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip)

const props = defineProps<{
    user: User
    entries: CRAEntry[]
    clients: Client[]
    projects: Project[]
    defaultTjm: number
}>()

const now = new Date()
const currentYear = now.getFullYear()
const currentMonth = now.getMonth() + 1

const MONTHS_FR = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc']

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
function entriesForMonth(year: number, month: number) {
    return props.entries.filter(e => { const d = new Date(e.date); return d.getFullYear() === year && d.getMonth() + 1 === month })
}
function entriesForYear(year: number) {
    return props.entries.filter(e => new Date(e.date).getFullYear() === year)
}

const monthEntries = computed(() => entriesForMonth(currentYear, currentMonth))
const monthDays = computed(() => sumDays(monthEntries.value))
const monthRevenue = computed(() => sumRevenue(monthEntries.value))
const workingDays = computed(() => getWorkingDays(currentYear, currentMonth))
const fillRate = computed(() => workingDays.value > 0 ? Math.round((monthDays.value / workingDays.value) * 100) : 0)

const yearEntries = computed(() => entriesForYear(currentYear))
const yearRevenue = computed(() => sumRevenue(yearEntries.value))

const activeClients = computed(() => props.clients.filter(c => c.active))
const activeProjects = computed(() =>
    props.projects.filter(p => p.status === 'active').map(p => ({ ...p, client: getClient(p.clientId) }))
)

const recentEntries = computed(() =>
    [...props.entries]
        .sort((a, b) => b.date.localeCompare(a.date))
        .slice(0, 7)
        .map(e => ({ ...e, project: getProject(e.projectId), client: getProject(e.projectId) ? getClient(getProject(e.projectId)!.clientId) : undefined }))
)

const barChartData = computed(() => {
    const labels: string[] = []
    const data: number[] = []
    const colors: string[] = []
    for (let i = 11; i >= 0; i--) {
        let m = currentMonth - i; let y = currentYear
        if (m <= 0) { m += 12; y -= 1 }
        labels.push(MONTHS_FR[m - 1])
        data.push(sumDays(entriesForMonth(y, m)))
        colors.push(m === currentMonth && y === currentYear ? 'var(--primary)' : 'var(--muted)')
    }
    return { labels, datasets: [{ label: 'Jours facturés', data, backgroundColor: colors, borderRadius: 4, borderSkipped: false as const }] }
})

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx: { parsed: { y: number } }) => `${ctx.parsed.y} j` } } },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 11 }, stepSize: 5 } },
    },
}

function formatCurrency(n: number) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(n)
}
function formatDate(date: string) {
    return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}
</script>

<template>
    <div class="space-y-6">
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>Jours ce mois</CardTitle>
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold text-foreground">{{ monthDays }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">sur {{ workingDays }} jours ouvrés</p>
                    <div class="mt-2 h-1.5 w-full rounded-full bg-secondary">
                        <div class="h-1.5 rounded-full bg-primary transition-all" :style="{ width: `${Math.min(fillRate, 100)}%` }" />
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">{{ fillRate }}% rempli</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>CA ce mois</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold text-foreground">{{ formatCurrency(monthRevenue) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">TJM {{ defaultTjm }} €</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>Clients actifs</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold text-foreground">{{ activeClients.length }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ clients.length }} clients total</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>CA {{ currentYear }}</CardTitle>
                        <FolderKanban class="h-4 w-4 text-muted-foreground" />
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-2xl font-bold text-foreground">{{ formatCurrency(yearRevenue) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ sumDays(yearEntries) }} jours facturés</p>
                </CardContent>
            </Card>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader><CardTitle>Activité sur 12 mois</CardTitle></CardHeader>
                <CardContent>
                    <div class="h-52"><Bar :data="barChartData" :options="barChartOptions" /></div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>Dernières saisies</CardTitle></CardHeader>
                <CardContent>
                    <ul class="space-y-2">
                        <li v-for="entry in recentEntries" :key="entry.id" class="flex items-center justify-between text-sm">
                            <div class="flex min-w-0 items-center gap-2">
                                <div class="h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: entry.client?.color ?? '#888' }" />
                                <span class="truncate text-foreground">{{ entry.project?.name ?? '—' }}</span>
                            </div>
                            <div class="ml-2 flex shrink-0 items-center gap-2">
                                <span class="text-xs text-muted-foreground">{{ formatDate(entry.date) }}</span>
                                <Badge variant="secondary">{{ entry.value }}j</Badge>
                            </div>
                        </li>
                        <li v-if="recentEntries.length === 0" class="text-sm text-muted-foreground">Aucune saisie</li>
                    </ul>
                </CardContent>
            </Card>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader><CardTitle>Clients actifs</CardTitle></CardHeader>
                <CardContent>
                    <ul class="space-y-2">
                        <li v-for="client in activeClients" :key="client.id" class="flex items-center gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-medium text-white" :style="{ backgroundColor: client.color }">
                                {{ client.name.slice(0, 2).toUpperCase() }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-foreground">{{ client.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ client.contactName }}</p>
                            </div>
                        </li>
                        <li v-if="activeClients.length === 0" class="text-sm text-muted-foreground">Aucun client actif</li>
                    </ul>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>Projets en cours</CardTitle></CardHeader>
                <CardContent>
                    <ul class="space-y-2">
                        <li v-for="project in activeProjects" :key="project.id" class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-foreground">{{ project.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ project.client?.name ?? '—' }}</p>
                            </div>
                            <Badge variant="secondary">Actif</Badge>
                        </li>
                        <li v-if="activeProjects.length === 0" class="text-sm text-muted-foreground">Aucun projet actif</li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
