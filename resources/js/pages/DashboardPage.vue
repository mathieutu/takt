<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { router, useHttp } from '@inertiajs/vue3'
import type { PageProps } from '../types'
import { update as updateClient, destroy as destroyClient } from '@/wayfinder/routes/clients'
import { update as updateProject, destroy as destroyProject, share as shareRoute } from '@/wayfinder/routes/projects'
import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Tooltip, type TooltipItem } from 'chart.js'
import { TrendingUp, Calendar, Users, FolderKanban, MoreVertical, Pencil, Share2, Trash2 } from 'lucide-vue-next'
import Card from '../components/ui/Card.vue'
import CardHeader from '../components/ui/CardHeader.vue'
import CardTitle from '../components/ui/CardTitle.vue'
import CardContent from '../components/ui/CardContent.vue'
import Badge from '../components/ui/Badge.vue'
import Dialog from '../components/ui/Dialog.vue'
import DropdownMenu from '../components/ui/DropdownMenu.vue'

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip)

onMounted(() => {
    if (props.projects.length === 0) {
        router.visit('/projects?open=1', { replace: true })
    }
})

type Entry   = { id: number; projectId: number; date: string; value: number; label: string | null }
type Client  = { id: number; name: string; daily_rate: number; is_owner: boolean }
type Project = { id: number; clientId: number; name: string; daily_rate: number | null; is_owner: boolean; is_shared: boolean; description: string }
type ActiveProject = Project & { client: Client | undefined }

const props = defineProps<{
    entries:  Entry[]
    clients:  Client[]
    projects: Project[]
}>()

const editingClient    = ref<Client | null>(null)
const deletingClient   = ref<Client | null>(null)
const editingProject   = ref<ActiveProject | null>(null)
const deletingProject  = ref<ActiveProject | null>(null)
const sharingProject   = ref<ActiveProject | null>(null)
const shareUrl         = ref('')
const copied           = ref(false)
const shareLoading     = ref(false)

const ownedClients = computed(() => props.clients.filter(c => c.is_owner))

const editProjectClientRate = computed(() => {
    if (!editingProject.value) return null
    const client = props.clients.find(c => c.id === editingProject.value!.clientId)
    return client?.daily_rate ?? null
})

function openShare(project: ActiveProject) {
    sharingProject.value = project
    shareUrl.value = ''
    copied.value = false
    shareLoading.value = true

    useHttp().post(shareRoute(project.id).url, {
        onSuccess: (data: { url: string }) => {
            shareUrl.value = data.url
        },
        onError: () => {
            shareUrl.value = ''
            console.error('Erreur lors de la génération du lien de partage')
        },
        onFinish: () => {
            shareLoading.value = false
        },
    })
}

function copyShareUrl() {
    navigator.clipboard.writeText(shareUrl.value)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
}

function menuItemsProject(project: ActiveProject) {
    return [
        { label: 'Modifier', action: () => { editingProject.value = { ...project } } },
        { label: 'Supprimer', action: () => { deletingProject.value = project }, variant: 'destructive' as const },
    ]
}

const now          = new Date()
const currentYear  = now.getFullYear()
const currentMonth = now.getMonth() + 1

const MONTHS_FR = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc']

const AVATAR_COLORS = [
    'bg-blue-100 text-blue-700',
    'bg-violet-100 text-violet-700',
    'bg-emerald-100 text-emerald-700',
    'bg-amber-100 text-amber-700',
    'bg-rose-100 text-rose-700',
    'bg-cyan-100 text-cyan-700',
]

const DOT_COLORS = ['#3b82f6', '#7c3aed', '#10b981', '#f59e0b', '#f43f5e', '#06b6d4']

function hashName(name: string): number {
    let hash = 0
    for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
    return Math.abs(hash)
}

function avatarColor(name: string): string {
    return AVATAR_COLORS[hashName(name) % AVATAR_COLORS.length]
}

function dotColor(name: string): string {
    return DOT_COLORS[hashName(name) % DOT_COLORS.length]
}

function initials(name: string): string {
    return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()
}

function getProjectRate(projectId: number): number {
    const project = props.projects.find(p => p.id === projectId)
    if (!project) return 0
    if (project.daily_rate !== null) return project.daily_rate
    const client = props.clients.find(c => c.id === project.clientId)
    return client?.daily_rate ?? 0
}

function sumDays(ents: Entry[]): number {
    return ents.reduce((acc, e) => acc + e.value, 0)
}

function sumRevenue(ents: Entry[]): number {
    return ents.reduce((acc, e) => acc + e.value * getProjectRate(e.projectId), 0)
}

function entriesForMonth(year: number, month: number): Entry[] {
    return props.entries.filter(e => {
        const d = new Date(e.date)
        return d.getFullYear() === year && d.getMonth() + 1 === month
    })
}

function getWorkingDays(year: number, month: number): number {
    let count = 0
    const d = new Date(year, month - 1, 1)
    while (d.getMonth() === month - 1) {
        if (d.getDay() !== 0 && d.getDay() !== 6) count++
        d.setDate(d.getDate() + 1)
    }
    return count
}

function formatDays(d: number): string {
    return d % 1 === 0 ? `${d}j` : `${d.toFixed(1)}j`
}

function formatCurrency(n: number): string {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(n)
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
}

const monthEntries = computed(() => entriesForMonth(currentYear, currentMonth))
const monthDays    = computed(() => sumDays(monthEntries.value))
const monthRevenue = computed(() => sumRevenue(monthEntries.value))
const workingDays  = computed(() => getWorkingDays(currentYear, currentMonth))
const fillRate     = computed(() =>
    workingDays.value > 0 ? Math.round((monthDays.value / workingDays.value) * 100) : 0
)

const yearEntries = computed(() => props.entries.filter(e => new Date(e.date).getFullYear() === currentYear))
const yearRevenue = computed(() => sumRevenue(yearEntries.value))

const cutoff = new Date()
cutoff.setDate(cutoff.getDate() - 90)

const activeProjectIds = computed(() =>
    new Set(props.entries.filter(e => new Date(e.date) >= cutoff).map(e => e.projectId))
)

const activeClientIds = computed(() =>
    new Set(props.projects.filter(p => activeProjectIds.value.has(p.id)).map(p => p.clientId))
)

const activeClients = computed(() => props.clients.filter(c => activeClientIds.value.has(c.id)))

const activeProjects = computed(() =>
    props.projects
        .filter(p => activeProjectIds.value.has(p.id))
        .map(p => ({ ...p, client: props.clients.find(c => c.id === p.clientId) }))
)

const recentEntries = computed(() =>
    [...props.entries]
        .sort((a, b) => b.date.localeCompare(a.date))
        .slice(0, 7)
        .map(e => {
            const project = props.projects.find(p => p.id === e.projectId)
            const client  = project ? props.clients.find(c => c.id === project.clientId) : undefined
            return { ...e, project, client }
        })
)

const barChartData = computed(() => {
    const labels: string[] = []
    const data:   number[] = []
    const colors: string[] = []
    for (let i = 11; i >= 0; i--) {
        let m = currentMonth - i
        let y = currentYear
        if (m <= 0) { m += 12; y -= 1 }
        labels.push(MONTHS_FR[m - 1])
        data.push(sumDays(entriesForMonth(y, m)))
        colors.push(m === currentMonth && y === currentYear ? 'var(--primary)' : 'var(--border)')
    }
    return {
        labels,
        datasets: [{
            label: 'Jours facturés',
            data,
            backgroundColor: colors,
            borderRadius: 4,
            borderSkipped: false as const,
        }],
    }
})

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: (ctx: TooltipItem<'bar'>) => `${ctx.parsed.y} j` } },
    },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 11 }, stepSize: 5 } },
    },
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background">
        <main class="flex-1 px-6 py-8">
            <div class="mx-auto max-w-5xl space-y-6">

                <div>
                    <h1 class="text-lg font-semibold text-foreground">Tableau de bord</h1>
                    <p class="text-sm text-muted-foreground">Vue d'ensemble de votre activité</p>
                </div>

                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <CardTitle>Jours ce mois</CardTitle>
                                <Calendar class="h-4 w-4 text-muted-foreground" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold text-foreground">{{ formatDays(monthDays) }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">sur {{ workingDays }} jours ouvrés</p>
                            <div class="mt-2 h-1.5 w-full rounded-full bg-secondary">
                                <div class="h-1.5 rounded-full bg-primary transition-all"
                                     :style="{ width: `${Math.min(fillRate, 100)}%` }" />
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
                            <p class="mt-1 text-xs text-muted-foreground">{{ formatDays(monthDays) }} facturés</p>
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
                            <p class="mt-1 text-xs text-muted-foreground">{{ formatDays(sumDays(yearEntries)) }} facturés</p>
                        </CardContent>
                    </Card>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <Card class="lg:col-span-2">
                        <CardHeader><CardTitle>Activité sur 12 mois</CardTitle></CardHeader>
                        <CardContent>
                            <div class="h-52">
                                <Bar :data="barChartData" :options="barChartOptions" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle>Dernières saisies</CardTitle></CardHeader>
                        <CardContent>
                            <ul class="space-y-2">
                                <li v-for="entry in recentEntries" :key="entry.id"
                                    class="flex items-center justify-between text-sm">
                                    <div class="flex min-w-0 items-center gap-2">
                                        <div class="h-2 w-2 shrink-0 rounded-full"
                                             :style="{ backgroundColor: dotColor(entry.client?.name ?? '') }" />
                                        <span class="truncate text-foreground">{{ entry.project?.name ?? '—' }}</span>
                                    </div>
                                    <div class="ml-2 flex shrink-0 items-center gap-2">
                                        <span class="text-xs text-muted-foreground">{{ formatDate(entry.date) }}</span>
                                        <Badge variant="secondary">{{ formatDays(entry.value) }}</Badge>
                                    </div>
                                </li>
                                <li v-if="recentEntries.length === 0" class="text-sm text-muted-foreground">
                                    Aucune saisie
                                </li>
                            </ul>
                        </CardContent>
                    </Card>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle>Clients actifs</CardTitle></CardHeader>
                        <CardContent>
                            <ul class="space-y-3">
                                <li v-for="client in activeClients" :key="client.id"
                                    class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-medium"
                                         :class="avatarColor(client.name)">
                                        {{ initials(client.name) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-foreground">{{ client.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ client.daily_rate }} €/j</p>
                                    </div>
                                    <div v-if="client.is_owner" class="flex shrink-0 items-center gap-1">
                                        <button
                                            type="button"
                                            class="flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                            @click="editingClient = { ...client }"
                                        >
                                            <Pencil class="h-3.5 w-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            class="flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive"
                                            @click="deletingClient = client"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </li>
                                <li v-if="activeClients.length === 0" class="text-sm text-muted-foreground">
                                    Aucun client actif
                                </li>
                            </ul>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle>Projets en cours</CardTitle></CardHeader>
                        <CardContent>
                            <ul class="space-y-3">
                                <li v-for="project in activeProjects" :key="project.id"
                                    class="flex items-center justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-foreground">{{ project.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ project.client?.name ?? '—' }}</p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-1">
                                        <Badge variant="secondary">Actif</Badge>
                                        <template v-if="project.is_owner">
                                            <button
                                                type="button"
                                                class="flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                                @click="openShare(project)"
                                            >
                                                <Share2 class="h-4 w-4" />
                                            </button>
                                            <DropdownMenu :items="menuItemsProject(project)" class="shrink-0">
                                                <button
                                                    type="button"
                                                    class="flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                                >
                                                    <MoreVertical class="h-4 w-4" />
                                                </button>
                                            </DropdownMenu>
                                        </template>
                                    </div>
                                </li>
                                <li v-if="activeProjects.length === 0" class="text-sm text-muted-foreground">
                                    Aucun projet actif
                                </li>
                            </ul>
                        </CardContent>
                    </Card>
                </div>

            </div>
        </main>
    </div>

    <Dialog :open="editingClient !== null" title="Modifier le client" @close="editingClient = null">
        <div v-if="editingClient" class="space-y-4">
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-foreground">Nom<span class="text-destructive ml-0.5">*</span></label>
                <input v-model="editingClient.name" type="text" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-foreground">TJM (€/jour)<span class="text-destructive ml-0.5">*</span></label>
                <input v-model="editingClient.daily_rate" type="number" min="0" step="0.01" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent" @click="editingClient = null">Annuler</button>
                <button
                    type="button"
                    class="h-9 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                    @click="router.put(updateClient(editingClient.id).url, { name: editingClient.name, daily_rate: editingClient.daily_rate }, { preserveScroll: true, onSuccess: () => editingClient = null })"
                >Enregistrer</button>
            </div>
        </div>
    </Dialog>

    <Dialog :open="deletingClient !== null" title="Supprimer le client" @close="deletingClient = null">
        <p class="text-sm text-muted-foreground">
            Supprimer <span class="font-medium text-foreground">{{ deletingClient?.name }}</span> ?
            Tous les projets et saisies associés seront définitivement supprimés.
        </p>
        <div v-if="deletingClient" class="mt-4 flex justify-end gap-2">
            <button type="button" class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent" @click="deletingClient = null">Annuler</button>
            <button
                type="button"
                class="h-9 rounded-md bg-destructive px-4 text-sm font-medium text-white transition-colors hover:bg-destructive/90"
                @click="router.delete(destroyClient(deletingClient.id).url, { preserveScroll: true, onSuccess: () => deletingClient = null })"
            >Supprimer</button>
        </div>
    </Dialog>

    <Dialog :open="editingProject !== null" title="Modifier le projet" @close="editingProject = null">
        <div v-if="editingProject" class="space-y-4">
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-foreground">Client<span class="text-destructive ml-0.5">*</span></label>
                <select v-model="editingProject.clientId" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                    <option v-for="c in ownedClients" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-foreground">Nom du projet<span class="text-destructive ml-0.5">*</span></label>
                <input v-model="editingProject.name" type="text" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-foreground">Description</label>
                <input v-model="editingProject.description" type="text" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-foreground">TJM du projet</label>
                <input
                    v-model="editingProject.daily_rate"
                    type="number"
                    min="0"
                    step="0.01"
                    :placeholder="editProjectClientRate !== null ? `${editProjectClientRate} €/j (TJM client)` : 'Hérite du TJM client'"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                />
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent" @click="editingProject = null">Annuler</button>
                <button
                    type="button"
                    class="h-9 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                    @click="router.put(updateProject(editingProject.id).url, { client_id: editingProject.clientId, name: editingProject.name, description: editingProject.description, daily_rate: editingProject.daily_rate }, { preserveScroll: true, onSuccess: () => editingProject = null })"
                >Enregistrer</button>
            </div>
        </div>
    </Dialog>

    <Dialog :open="sharingProject !== null" title="Partager le projet" @close="sharingProject = null">
        <p class="text-sm text-muted-foreground">
            Copiez ce lien et envoyez-le à la personne avec qui vous souhaitez partager
            <span class="font-medium text-foreground">{{ sharingProject?.name }}</span>.
        </p>
        <div class="mt-4 flex gap-2">
            <input
                :value="shareLoading ? 'Chargement…' : shareUrl"
                readonly
                class="h-9 min-w-0 flex-1 rounded-md border border-input bg-muted px-3 text-sm text-foreground focus:outline-none"
            />
            <button
                type="button"
                :disabled="shareLoading || !shareUrl"
                class="h-9 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50"
                @click="copyShareUrl"
            >
                {{ copied ? 'Copié !' : 'Copier' }}
            </button>
        </div>
    </Dialog>

    <Dialog :open="deletingProject !== null" title="Supprimer le projet" @close="deletingProject = null">
        <p class="text-sm text-muted-foreground">
            Supprimer <span class="font-medium text-foreground">{{ deletingProject?.name }}</span> ? Cette action est irréversible.
        </p>
        <div v-if="deletingProject" class="mt-4 flex justify-end gap-2">
            <button type="button" class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent" @click="deletingProject = null">Annuler</button>
            <button
                type="button"
                class="h-9 rounded-md bg-destructive px-4 text-sm font-medium text-white transition-colors hover:bg-destructive/90"
                @click="router.delete(destroyProject(deletingProject.id).url, { preserveScroll: true, onSuccess: () => deletingProject = null })"
            >Supprimer</button>
        </div>
    </Dialog>
</template>
