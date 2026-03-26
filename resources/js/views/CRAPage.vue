<script setup lang="ts">
import { ref, computed } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { useToast } from '../composables/useToast'
import type { Project, Client, CRAEntry, DayValue } from '../types'
import Card from '../components/ui/Card.vue'
import CardHeader from '../components/ui/CardHeader.vue'
import CardTitle from '../components/ui/CardTitle.vue'
import CardContent from '../components/ui/CardContent.vue'
import Badge from '../components/ui/Badge.vue'
import ToastContainer from '../components/ui/ToastContainer.vue'

const props = defineProps<{
    projects: Project[]
    clients: Client[]
    entries: CRAEntry[]
    defaultTjm: number
    year?: number
    month?: number
}>()

const emit = defineEmits<{
    'entry-change': [date: string, projectId: string, newValue: DayValue]
}>()

const { toast } = useToast()

const now = new Date()
const currentYear = ref(props.year ?? now.getFullYear())
const currentMonth = ref(props.month ?? now.getMonth() + 1)

const localEntries = ref<CRAEntry[]>([...props.entries])

const selectedProjectId = ref(props.projects.find(p => p.status === 'active')?.id ?? '')

const MONTHS_FR = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']
const DAYS_FR = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']

const monthLabel = computed(() => `${MONTHS_FR[currentMonth.value - 1]} ${currentYear.value}`)

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

function prevMonth() {
    if (currentMonth.value === 1) { currentMonth.value = 12; currentYear.value-- }
    else currentMonth.value--
}

function nextMonth() {
    if (currentMonth.value === 12) { currentMonth.value = 1; currentYear.value++ }
    else currentMonth.value++
}

const calendarDays = computed(() => {
    const year = currentYear.value
    const month = currentMonth.value
    const firstDay = new Date(year, month - 1, 1)
    const lastDay = new Date(year, month, 0)
    const totalDays = lastDay.getDate()
    let startOffset = firstDay.getDay() - 1
    if (startOffset < 0) startOffset = 6
    const days: { date: string | null; dayNum: number | null; isWeekend: boolean }[] = []
    for (let i = 0; i < startOffset; i++) days.push({ date: null, dayNum: null, isWeekend: false })
    for (let d = 1; d <= totalDays; d++) {
        const dateObj = new Date(year, month - 1, d)
        const isWeekend = dateObj.getDay() === 0 || dateObj.getDay() === 6
        const mm = String(month).padStart(2, '0')
        const dd = String(d).padStart(2, '0')
        days.push({ date: `${year}-${mm}-${dd}`, dayNum: d, isWeekend })
    }
    while (days.length % 7 !== 0) days.push({ date: null, dayNum: null, isWeekend: false })
    return days
})

const monthEntries = computed(() =>
    localEntries.value.filter(e => {
        const d = new Date(e.date)
        return d.getFullYear() === currentYear.value && d.getMonth() + 1 === currentMonth.value
    })
)

function getEntryValue(date: string, projectId: string) {
    return localEntries.value.find(e => e.date === date && e.projectId === projectId)?.value ?? 0
}

function getDayTotal(date: string) {
    return localEntries.value.filter(e => e.date === date).reduce((sum, e) => sum + e.value, 0)
}

const activeProjects = computed(() =>
    props.projects.filter(p => p.status === 'active').map(p => ({ ...p, client: getClient(p.clientId) }))
)

function toggleEntry(date: string, projectId: string) {
    const idx = localEntries.value.findIndex(e => e.date === date && e.projectId === projectId)
    if (idx === -1) {
        localEntries.value.push({ id: `e${Date.now()}`, date, projectId, value: 0.5 })
        emit('entry-change', date, projectId, 0.5)
    } else {
        const current = localEntries.value[idx].value
        if (current === 0.5) {
            localEntries.value[idx] = { ...localEntries.value[idx], value: 1 }
            emit('entry-change', date, projectId, 1)
        } else {
            localEntries.value.splice(idx, 1)
            emit('entry-change', date, projectId, 0)
        }
    }
}

function handleDayClick(date: string | null) {
    if (!date || !selectedProjectId.value) return
    const dayTotal = getDayTotal(date)
    const currentVal = getEntryValue(date, selectedProjectId.value)
    if (currentVal === 0 && dayTotal >= 1) {
        toast('La journée est déjà complète (1 jour)', 'error')
        return
    }
    if (currentVal === 0.5 && dayTotal >= 1) {
        toast('Impossible : la journée est déjà complète', 'error')
        return
    }
    toggleEntry(date, selectedProjectId.value)
    const newVal = getEntryValue(date, selectedProjectId.value)
    const label = newVal === 0.5 ? '0.5j' : newVal === 1 ? '1j' : 'supprimé'
    toast(`Saisie ${label}`, 'success')
}

const totalDays = computed(() => sumDays(monthEntries.value))
const totalRevenue = computed(() => sumRevenue(monthEntries.value))
const workingDays = computed(() => getWorkingDays(currentYear.value, currentMonth.value))
const fillRate = computed(() => workingDays.value > 0 ? Math.round((totalDays.value / workingDays.value) * 100) : 0)

const projectRecap = computed(() =>
    activeProjects.value
        .map(p => {
            const ents = monthEntries.value.filter(e => e.projectId === p.id)
            return { ...p, days: sumDays(ents), revenue: sumRevenue(ents) }
        })
        .filter(p => p.days > 0)
)

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(amount)
}

function cellClass(date: string | null, isWeekend: boolean) {
    if (!date || isWeekend) return ''
    const total = getDayTotal(date)
    const val = selectedProjectId.value ? getEntryValue(date, selectedProjectId.value) : 0
    if (total === 0) return 'hover:bg-accent cursor-pointer'
    if (val === 1) return 'bg-primary/15 cursor-pointer ring-1 ring-primary/40'
    if (val === 0.5) return 'bg-primary/8 cursor-pointer ring-1 ring-primary/25'
    return 'bg-muted cursor-pointer'
}
</script>

<template>
    <div class="flex flex-col gap-6 lg:flex-row">
        <div class="flex-1 min-w-0">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h1 class="text-lg font-semibold text-foreground">Saisie CRA</h1>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-md border border-border hover:bg-accent transition-colors"
                            @click="prevMonth"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </button>
                        <span class="min-w-[140px] text-center text-sm font-medium text-foreground">{{ monthLabel }}</span>
                        <button
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-md border border-border hover:bg-accent transition-colors"
                            @click="nextMonth"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <Card>
                    <CardContent class="pt-4 pb-4">
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="project in activeProjects"
                                :key="project.id"
                                type="button"
                                :class="[
                                    'flex items-center gap-2 rounded-full px-3 py-1 text-sm font-medium transition-colors border',
                                    selectedProjectId === project.id
                                        ? 'bg-primary text-primary-foreground border-transparent'
                                        : 'border-border bg-background text-foreground hover:bg-accent',
                                ]"
                                @click="selectedProjectId = project.id"
                            >
                                <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: project.client?.color ?? '#888' }" />
                                {{ project.name }}
                            </button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-4">
                        <div class="mb-1 grid grid-cols-7 gap-1">
                            <div
                                v-for="day in DAYS_FR"
                                :key="day"
                                class="text-center text-xs font-medium text-muted-foreground py-1"
                                :class="{ 'text-muted-foreground/50': day === 'Sam' || day === 'Dim' }"
                            >
                                {{ day }}
                            </div>
                        </div>

                        <div class="grid grid-cols-7 gap-1">
                            <div
                                v-for="(day, idx) in calendarDays"
                                :key="idx"
                                :class="[
                                    'relative flex flex-col items-center justify-center rounded-md aspect-square text-sm transition-colors select-none',
                                    day.date === null ? 'opacity-0 pointer-events-none' : '',
                                    day.isWeekend ? 'bg-muted/40 text-muted-foreground cursor-default' : '',
                                    !day.isWeekend && day.date ? cellClass(day.date, day.isWeekend) : '',
                                ]"
                                @click="!day.isWeekend && handleDayClick(day.date)"
                            >
                                <span v-if="day.dayNum !== null" class="text-xs font-medium">{{ day.dayNum }}</span>
                                <template v-if="day.date && !day.isWeekend">
                                    <div class="mt-0.5 flex gap-0.5">
                                        <template v-for="project in activeProjects" :key="project.id">
                                            <span
                                                v-if="getEntryValue(day.date, project.id) > 0"
                                                class="h-1 w-1 rounded-full"
                                                :style="{ backgroundColor: project.client?.color ?? '#888' }"
                                            />
                                        </template>
                                    </div>
                                    <span
                                        v-if="getDayTotal(day.date) > 0"
                                        class="absolute bottom-1 right-1 text-[9px] font-semibold text-primary/80"
                                    >
                                        {{ getDayTotal(day.date) }}
                                    </span>
                                </template>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-3 border-t border-border pt-3">
                            <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                <div class="h-3 w-3 rounded bg-primary/15 ring-1 ring-primary/40" />
                                <span>1 jour</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                <div class="h-3 w-3 rounded bg-primary/8 ring-1 ring-primary/25" />
                                <span>0.5 jour</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                <div class="h-3 w-3 rounded bg-muted" />
                                <span>Autre projet</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                <div class="h-3 w-3 rounded bg-muted/40" />
                                <span>Week-end</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <div class="w-full lg:w-72 shrink-0 space-y-4">
            <h2 class="text-sm font-semibold text-foreground">Récapitulatif</h2>

            <Card>
                <CardHeader>
                    <CardTitle>{{ monthLabel }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">Jours saisis</span>
                            <span class="font-semibold text-foreground">{{ totalDays }} / {{ workingDays }}</span>
                        </div>
                        <div>
                            <div class="mb-1 flex justify-between text-xs text-muted-foreground">
                                <span>Taux de remplissage</span>
                                <span>{{ fillRate }}%</span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-secondary">
                                <div class="h-1.5 rounded-full bg-primary transition-all" :style="{ width: `${Math.min(fillRate, 100)}%` }" />
                            </div>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground">CA estimé</span>
                            <span class="font-semibold text-foreground">{{ formatCurrency(totalRevenue) }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card v-if="projectRecap.length > 0">
                <CardHeader>
                    <CardTitle>Par projet</CardTitle>
                </CardHeader>
                <CardContent>
                    <ul class="space-y-3">
                        <li v-for="p in projectRecap" :key="p.id" class="flex items-start gap-2">
                            <div class="mt-0.5 h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: p.client?.color ?? '#888' }" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-medium text-foreground">{{ p.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ p.client?.name ?? '—' }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-xs font-semibold text-foreground">{{ p.days }}j</p>
                                <p class="text-xs text-muted-foreground">{{ formatCurrency(p.revenue) }}</p>
                            </div>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <div class="rounded-lg bg-muted/50 p-3 text-xs text-muted-foreground space-y-1">
                <p class="font-medium text-foreground">Comment utiliser</p>
                <p>1. Sélectionnez un projet ci-dessus</p>
                <p>2. Cliquez sur un jour ouvré pour saisir</p>
                <p>3. Cliquez à nouveau pour changer la valeur</p>
                <p class="font-medium">0 → 0.5j → 1j → 0</p>
            </div>
        </div>
    </div>

    <ToastContainer />
</template>
