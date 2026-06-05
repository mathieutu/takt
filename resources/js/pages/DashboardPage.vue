<script setup lang="ts">
import { BarElement, CategoryScale, Chart as ChartJS, LinearScale, Tooltip, type TooltipItem } from 'chart.js'
import { computed } from 'vue'
import { Bar } from 'vue-chartjs'
import { formatDate, formatDays } from '@/utils/date.ts'
import { formatCurrency } from '@/utils/number.ts'

const props = defineProps<{
  entries: Entry[],
  clients: Client[],
  projects: Project[],
}>()

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip)

type Entry = { id: number, projectId: number, date: string, value: number, label: string | null }
type Client = { id: number, name: string, daily_rate: number }
type Project = {
  id: number,
  clientId: number,
  name: string,
  daily_rate: number | null,
  description: string,
}

const now = new Date()
const currentYear = now.getFullYear()
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

const monthEntries = computed(() => entriesForMonth(currentYear, currentMonth))
const monthDays = computed(() => sumDays(monthEntries.value))
const monthRevenue = computed(() => sumRevenue(monthEntries.value))
const workingDays = computed(() => getWorkingDays(currentYear, currentMonth))
const fillRate = computed(() =>
  workingDays.value > 0 ? Math.round((monthDays.value / workingDays.value) * 100) : 0,
)

const yearEntries = computed(() => props.entries.filter(e => new Date(e.date).getFullYear() === currentYear))
const yearRevenue = computed(() => sumRevenue(yearEntries.value))

const cutoff = new Date()
cutoff.setDate(cutoff.getDate() - 90)

const activeProjectIds = computed(() =>
  new Set(props.entries.filter(e => new Date(e.date) >= cutoff).map(e => e.projectId)),
)

const activeClientIds = computed(() =>
  new Set(props.projects.filter(p => activeProjectIds.value.has(p.id)).map(p => p.clientId)),
)

const activeClients = computed(() => props.clients.filter(c => activeClientIds.value.has(c.id)))

const activeProjects = computed(() =>
  props.projects
    .filter(p => activeProjectIds.value.has(p.id))
    .map(p => ({ ...p, client: props.clients.find(c => c.id === p.clientId) })),
)

const recentEntries = computed(() =>
  [...props.entries]
    .sort((a, b) => b.date.localeCompare(a.date))
    .slice(0, 7)
    .map(e => {
      const project = props.projects.find(p => p.id === e.projectId)
      const client = project ? props.clients.find(c => c.id === project.clientId) : undefined
      return { ...e, project, client }
    }),
)

const barChartData = computed(() => {
  const labels: string[] = []
  const data: number[] = []
  const colors: string[] = []
  for (let i = 11; i >= 0; i--) {
    let m = currentMonth - i
    let y = currentYear
    if (m <= 0) {
      m += 12
      y -= 1
    }
    labels.push(MONTHS_FR[m - 1])
    data.push(sumDays(entriesForMonth(y, m)))
    colors.push(m === currentMonth && y === currentYear ? 'var(--ui-primary)' : 'var(--ui-border)')
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
  <div class="flex min-h-screen flex-col bg-default">
    <main class="flex-1 px-6 py-8">
      <div class="mx-auto max-w-5xl space-y-6">
        <div>
          <h1 class="text-lg font-semibold">Tableau de bord</h1>
          <p class="text-sm text-muted">Vue d'ensemble de votre activité</p>
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
          <UCard>
            <template #header>
              <div class="flex items-center justify-between">
                <p class="text-sm font-semibold">Jours ce mois</p>
                <UIcon name="i-lucide-calendar" class="text-muted" />
              </div>
            </template>
            <p class="text-2xl font-bold">{{ formatDays(monthDays) }}</p>
            <p class="mt-1 text-xs text-muted">sur {{ workingDays }} jours ouvrés</p>
            <div class="mt-2 h-1.5 w-full rounded-full bg-muted">
              <div
                class="h-1.5 rounded-full bg-primary transition-all"
                :style="{ width: `${Math.min(fillRate, 100)}%` }"
              />
            </div>
            <p class="mt-1 text-xs text-muted">{{ fillRate }}% rempli</p>
          </UCard>

          <UCard>
            <template #header>
              <div class="flex items-center justify-between">
                <p class="text-sm font-semibold">CA ce mois</p>
                <UIcon name="i-lucide-trending-up" class="text-muted" />
              </div>
            </template>
            <p class="text-2xl font-bold">{{ formatCurrency(monthRevenue) }}</p>
            <p class="mt-1 text-xs text-muted">{{ formatDays(monthDays) }} facturés</p>
          </UCard>

          <UCard>
            <template #header>
              <div class="flex items-center justify-between">
                <p class="text-sm font-semibold">Clients actifs</p>
                <UIcon name="i-lucide-users" class="text-muted" />
              </div>
            </template>
            <p class="text-2xl font-bold">{{ activeClients.length }}</p>
            <p class="mt-1 text-xs text-muted">{{ clients.length }} clients total</p>
          </UCard>

          <UCard>
            <template #header>
              <div class="flex items-center justify-between">
                <p class="text-sm font-semibold">CA {{ currentYear }}</p>
                <UIcon name="i-lucide-folder-kanban" class="text-muted" />
              </div>
            </template>
            <p class="text-2xl font-bold">{{ formatCurrency(yearRevenue) }}</p>
            <p class="mt-1 text-xs text-muted">{{ formatDays(sumDays(yearEntries)) }} facturés</p>
          </UCard>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
          <UCard class="lg:col-span-2">
            <template #header>
              <p class="text-sm font-semibold">Activité sur 12 mois</p>
            </template>
            <div class="h-52">
              <Bar :data="barChartData" :options="barChartOptions" />
            </div>
          </UCard>

          <UCard>
            <template #header>
              <p class="text-sm font-semibold">Dernières saisies</p>
            </template>
            <ul class="space-y-2">
              <li
                v-for="entry in recentEntries" :key="entry.id"
                class="flex items-center justify-between text-sm"
              >
                <div class="flex min-w-0 items-center gap-2">
                  <div
                    class="h-2 w-2 shrink-0 rounded-full"
                    :style="{ backgroundColor: dotColor(entry.client?.name ?? '') }"
                  />
                  <span class="truncate">{{ entry.project?.name ?? '—' }}</span>
                </div>
                <div class="ml-2 flex shrink-0 items-center gap-2">
                  <span class="text-xs text-muted">{{ formatDate(entry.date) }}</span>
                  <UBadge color="neutral" variant="subtle">{{ formatDays(entry.value) }}</UBadge>
                </div>
              </li>
              <li v-if="recentEntries.length === 0" class="text-sm text-muted">
                Aucune saisie
              </li>
            </ul>
          </UCard>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <UCard>
            <template #header>
              <p class="text-sm font-semibold">Clients actifs</p>
            </template>
            <ul class="space-y-3">
              <li
                v-for="client in activeClients" :key="client.id"
                class="flex items-center gap-3"
              >
                <div
                  class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-medium"
                  :class="avatarColor(client.name)"
                >
                  {{ initials(client.name) }}
                </div>
                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm font-medium">{{ client.name }}</p>
                  <p class="text-xs text-muted">{{ client.daily_rate }} €/j</p>
                </div>
              </li>
              <li v-if="activeClients.length === 0" class="text-sm text-muted">
                Aucun client actif
              </li>
            </ul>
          </UCard>

          <UCard>
            <template #header>
              <p class="text-sm font-semibold">Projets en cours</p>
            </template>
            <ul class="space-y-3">
              <li
                v-for="project in activeProjects" :key="project.id"
                class="flex items-center justify-between gap-2"
              >
                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm font-medium">{{ project.name }}</p>
                  <p class="text-xs text-muted">{{ project.client?.name ?? '—' }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-1">
                  <UBadge color="neutral" variant="subtle">Actif</UBadge>
                </div>
              </li>
              <li v-if="activeProjects.length === 0" class="text-sm text-muted">
                Aucun projet actif
              </li>
            </ul>
          </UCard>
        </div>
      </div>
    </main>
  </div>
</template>
