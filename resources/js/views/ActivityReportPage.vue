<script setup lang="ts">
import { ref, computed } from 'vue'
import { ChevronLeft, ChevronRight, ChevronDown, Pencil } from 'lucide-vue-next'
import Dialog from '../components/ui/Dialog.vue'

type Project = { id: number; name: string; client_name: string; daily_rate: number }
type Report  = { id: number; start_date: string; day_coverage: number; label: string; comments: string }

const props = withDefaults(defineProps<{
    indexUrl?: string
    storeUrl?: string
    baseUrl?: string
    csrfToken?: string
    currentYear?: number
    currentMonth?: number
    projects?: Project[]
    selectedProject?: Project | null
    reports?: Report[]
}>(), {
    indexUrl: '/dashboard/reports',
    storeUrl: '/dashboard/reports',
    baseUrl: '/dashboard/reports',
    csrfToken: '',
    currentYear: () => new Date().getFullYear(),
    currentMonth: () => new Date().getMonth() + 1,
    projects: () => [],
    selectedProject: null,
    reports: () => [],
})

const localReports        = ref<Report[]>([...props.reports])
const activeProject       = ref<Project | null>(props.selectedProject ?? null)
const displayYear         = ref(props.currentYear)
const displayMonth        = ref(props.currentMonth)
const projectSelectorOpen = ref(false)
const switching           = ref(false)
const editingReport       = ref<Report | null>(null)
const editForm            = ref({ label: '', comments: '', day_coverage: 50 })

const MONTHS_FR = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre']
const DAYS_FR   = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim']

const today    = new Date()
const todayStr = `${today.getFullYear()}-${String(today.getMonth()+1).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}`

const monthReports = computed(() => {
    const prefix = `${displayYear.value}-${String(displayMonth.value).padStart(2,'0')}-`
    return localReports.value.filter(r => r.start_date.startsWith(prefix))
})

const reportByDate = computed(() => {
    const map = new Map<string, Report>()
    for (const r of monthReports.value) map.set(r.start_date, r)
    return map
})

const calendarCells = computed(() => {
    const y = displayYear.value
    const m = displayMonth.value
    const firstDay  = new Date(y, m - 1, 1)
    const lastDate  = new Date(y, m, 0).getDate()
    const startOffset = (firstDay.getDay() + 6) % 7

    const cells: { type: 'pad' | 'day'; dateStr?: string; day?: number }[] = []
    for (let i = 0; i < startOffset; i++) cells.push({ type: 'pad' })
    for (let d = 1; d <= lastDate; d++) {
        const dateStr = `${y}-${String(m).padStart(2,'0')}-${String(d).padStart(2,'0')}`
        cells.push({ type: 'day', dateStr, day: d })
    }
    const remainder = cells.length % 7
    if (remainder > 0) {
        for (let i = 0; i < 7 - remainder; i++) cells.push({ type: 'pad' })
    }
    return cells
})

const totalDays = computed(() =>
    monthReports.value.reduce((sum, r) => sum + r.day_coverage / 100, 0)
)

const caEstime = computed(() =>
    activeProject.value ? totalDays.value * activeProject.value.daily_rate : 0
)

const sortedEntries = computed(() =>
    [...monthReports.value].filter(r => r.day_coverage > 0).sort((a, b) => a.start_date.localeCompare(b.start_date))
)

function prevMonth() {
    if (displayMonth.value === 1) { displayMonth.value = 12; displayYear.value-- }
    else displayMonth.value--
}

function nextMonth() {
    if (displayMonth.value === 12) { displayMonth.value = 1; displayYear.value++ }
    else displayMonth.value++
}

async function selectProject(project: Project) {
    if (switching.value) return
    switching.value = true
    projectSelectorOpen.value = false
    try {
        const res = await fetch(`${props.indexUrl}?project_id=${project.id}`)
        const html = await res.text()
        const doc  = new DOMParser().parseFromString(html, 'text/html')
        const el   = doc.getElementById('vue-activity-reports')
        if (el) {
            const data = JSON.parse(el.dataset.props ?? '{}')
            localReports.value = data.reports ?? []
            activeProject.value = data.selectedProject ?? project
        }
    } finally {
        switching.value = false
    }
}

async function clickDay(dateStr: string) {
    if (!activeProject.value) return
    const existing = localReports.value.find(r => r.start_date === dateStr) ?? null

    if (existing && existing.id < 0) return

    if (!existing) {
        const tempId = -Date.now()
        localReports.value.push({ id: tempId, start_date: dateStr, day_coverage: 50, label: '', comments: '' })
        try {
            const res = await fetch(props.storeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': props.csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ project_id: activeProject.value.id, start_date: dateStr, day_coverage: 50 }),
            })
            if (res.ok) {
                const created = await res.json()
                const idx = localReports.value.findIndex(r => r.id === tempId)
                if (idx !== -1) localReports.value[idx] = { id: created.id, start_date: (created.start_date ?? dateStr).slice(0, 10), day_coverage: created.day_coverage ?? 50, label: created.label ?? '', comments: created.comments ?? '' }
            } else {
                localReports.value = localReports.value.filter(r => r.id !== tempId)
            }
        } catch {
            localReports.value = localReports.value.filter(r => r.id !== tempId)
        }
    } else if (existing.day_coverage < 100) {
        const idx = localReports.value.findIndex(r => r.id === existing.id)
        if (idx !== -1) localReports.value[idx] = { ...localReports.value[idx], day_coverage: 100 }
        fetch(`${props.baseUrl}/${existing.id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': props.csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ day_coverage: 100 }),
        }).then(res => {
            if (!res.ok) {
                const idx2 = localReports.value.findIndex(r => r.id === existing.id)
                if (idx2 !== -1) localReports.value[idx2] = { ...localReports.value[idx2], day_coverage: 50 }
            }
        })
    } else {
        localReports.value = localReports.value.filter(r => r.id !== existing.id)
        fetch(`${props.baseUrl}/${existing.id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': props.csrfToken, 'Accept': 'application/json' },
        }).then(res => {
            if (!res.ok) localReports.value.push(existing)
        })
    }
}

function coverageLabel(v: number) {
    if (v === 100) return '1j'
    if (v === 50)  return '½j'
    return `${v}%`
}

function dayFromDate(dateStr: string) {
    return parseInt(dateStr.split('-')[2])
}

function openEdit(report: Report) {
    editingReport.value = report
    editForm.value = { label: report.label ?? '', comments: report.comments ?? '', day_coverage: report.day_coverage }
}

async function saveEdit() {
    if (!editingReport.value || editingReport.value.id < 0) return
    const report = editingReport.value
    const res = await fetch(`${props.baseUrl}/${report.id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': props.csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify(editForm.value),
    })
    if (res.ok) {
        const updated = await res.json()
        const idx = localReports.value.findIndex(r => r.id === report.id)
        if (idx !== -1) localReports.value[idx] = {
            ...localReports.value[idx],
            label: updated.label ?? '',
            comments: updated.comments ?? '',
            day_coverage: updated.day_coverage ?? report.day_coverage,
        }
        editingReport.value = null
    }
}
</script>

<template>
    <div class="flex h-screen flex-col overflow-hidden bg-background">
        <div class="flex flex-1 overflow-hidden">
            <main class="flex flex-1 flex-col overflow-hidden px-6 py-6">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-muted-foreground">Projet actif :</span>

                        <div class="relative">
                            <button
                                type="button"
                                class="inline-flex h-8 items-center gap-2 rounded-md border border-border bg-background px-3 text-sm font-medium text-foreground transition-colors hover:bg-accent disabled:opacity-50"
                                :disabled="switching"
                                @click="projectSelectorOpen = !projectSelectorOpen"
                            >
                                <span v-if="activeProject" class="h-2 w-2 rounded-full bg-primary"></span>
                                {{ activeProject ? activeProject.name : 'Sélectionner un projet' }}
                                <ChevronDown class="h-3.5 w-3.5 text-muted-foreground" />
                            </button>
                            <div
                                v-if="projectSelectorOpen"
                                class="absolute left-0 top-full z-50 mt-1 min-w-[220px] overflow-hidden rounded-md border border-border bg-background shadow-md"
                            >
                                <button
                                    v-for="p in projects"
                                    :key="p.id"
                                    type="button"
                                    class="flex w-full flex-col px-3 py-2 text-left transition-colors hover:bg-accent"
                                    :class="activeProject?.id === p.id ? 'bg-accent' : ''"
                                    @click="selectProject(p)"
                                >
                                    <span class="text-sm font-medium text-foreground">{{ p.name }}</span>
                                    <span class="text-xs text-muted-foreground">{{ p.client_name }}</span>
                                </button>
                            </div>
                        </div>

                        <span
                            v-if="activeProject"
                            class="rounded-full border border-border px-2.5 py-0.5 text-xs font-medium text-foreground"
                        >
                            {{ activeProject.client_name }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" class="flex h-7 w-7 items-center justify-center rounded-md hover:bg-accent" @click="prevMonth">
                            <ChevronLeft class="h-4 w-4 text-muted-foreground" />
                        </button>
                        <span class="min-w-[140px] text-center text-sm font-medium text-foreground">
                            {{ MONTHS_FR[displayMonth - 1] }} {{ displayYear }}
                        </span>
                        <button type="button" class="flex h-7 w-7 items-center justify-center rounded-md hover:bg-accent" @click="nextMonth">
                            <ChevronRight class="h-4 w-4 text-muted-foreground" />
                        </button>
                    </div>
                </div>

                <div class="flex-1" :class="{ 'opacity-60 pointer-events-none': switching }">
                    <div class="grid grid-cols-7 border border-border">
                        <div v-for="(day, idx) in DAYS_FR" :key="day"
                            class="py-2 text-center text-xs font-medium text-muted-foreground"
                            :class="idx > 0 ? 'border-l border-border' : ''"
                        >
                            {{ day }}
                        </div>
                    </div>

                    <div class="grid h-[calc(100%-37px)] grid-cols-7" style="grid-auto-rows: 1fr">
                        <div
                            v-for="(cell, i) in calendarCells"
                            :key="i"
                            class="group relative border-b border-r border-border transition-colors"
                            :class="[
                                i % 7 === 0 ? 'border-l border-border' : '',
                                cell.type === 'day' && activeProject ? 'cursor-pointer hover:bg-accent/50' : '',
                                cell.type === 'day' && cell.dateStr === todayStr ? 'ring-2 ring-inset ring-primary' : '',
                                cell.type === 'day' && (reportByDate.get(cell.dateStr!)?.day_coverage ?? 0) >= 100 ? 'bg-primary/25' : '',
                                cell.type === 'day' && (reportByDate.get(cell.dateStr!)?.day_coverage ?? 0) > 0 && (reportByDate.get(cell.dateStr!)?.day_coverage ?? 0) < 100 ? 'bg-primary/10' : '',
                            ]"
                            @click="cell.type === 'day' && cell.dateStr ? clickDay(cell.dateStr) : null"
                        >
                            <template v-if="cell.type === 'day'">
                                <span
                                    class="absolute left-2 top-1.5 text-xs"
                                    :class="cell.dateStr === todayStr ? 'font-semibold text-primary' : 'text-foreground'"
                                >
                                    {{ cell.day }}
                                </span>
                                <button
                                    v-if="reportByDate.get(cell.dateStr!) && reportByDate.get(cell.dateStr!)!.id > 0"
                                    type="button"
                                    class="absolute right-1.5 top-1.5 flex h-5 w-5 items-center justify-center rounded opacity-0 transition-opacity group-hover:opacity-100 hover:bg-primary/20"
                                    @click.stop="openEdit(reportByDate.get(cell.dateStr!)!)"
                                >
                                    <Pencil class="h-3 w-3 text-primary" />
                                </button>
                                <template v-if="reportByDate.get(cell.dateStr!)">
                                    <span class="absolute bottom-1.5 right-2 text-xs font-medium text-primary">
                                        {{ coverageLabel(reportByDate.get(cell.dateStr!)!.day_coverage) }}
                                    </span>
                                    <span
                                        v-if="reportByDate.get(cell.dateStr!)!.label || reportByDate.get(cell.dateStr!)!.comments"
                                        class="absolute bottom-1.5 left-2 h-1.5 w-1.5 rounded-full bg-primary"
                                    />
                                </template>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-6">
                    <div class="flex items-center gap-1.5">
                        <span class="h-3.5 w-3.5 rounded-sm border border-border bg-primary/25"></span>
                        <span class="text-xs text-muted-foreground">½ ou 1 jour — projet sélectionné</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="h-3.5 w-3.5 rounded-full border-2 border-primary"></span>
                        <span class="text-xs text-muted-foreground">Aujourd'hui</span>
                    </div>
                </div>
            </main>

            <aside class="w-60 shrink-0 border-l border-border px-5 py-6">
                <p class="mb-4 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Récap du mois</p>
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">Jours saisis</span>
                        <span class="font-semibold text-foreground">{{ totalDays % 1 === 0 ? totalDays : totalDays.toFixed(1) }}j</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">CA estimé</span>
                        <span class="font-semibold text-foreground">{{ caEstime.toLocaleString('fr-FR') }} €</span>
                    </div>
                </div>
                <div class="mt-4 border-t border-border pt-4">
                    <p v-if="sortedEntries.length === 0" class="text-xs text-muted-foreground">Aucune saisie ce mois.</p>
                    <ul v-else class="space-y-1.5">
                        <li v-for="r in sortedEntries" :key="r.id" class="flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">{{ dayFromDate(r.start_date) }} {{ MONTHS_FR[displayMonth - 1].slice(0, 3) }}.</span>
                            <span class="font-medium text-foreground">{{ coverageLabel(r.day_coverage) }}</span>
                        </li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>

    <Dialog :open="editingReport !== null" :title="`${editingReport ? dayFromDate(editingReport.start_date) : ''} ${editingReport ? MONTHS_FR[parseInt(editingReport.start_date.split('-')[1]) - 1] : ''}`" @close="editingReport = null">
        <div class="space-y-4">
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-foreground">Titre</label>
                <input
                    v-model="editForm.label"
                    type="text"
                    placeholder="Ex : Développement feature X"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                />
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-foreground">Description</label>
                <textarea
                    v-model="editForm.comments"
                    rows="3"
                    placeholder="Détails de l'activité…"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring resize-none"
                />
            </div>
            <div class="flex flex-col gap-1.5">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium text-foreground">Durée</label>
                    <span class="text-sm font-semibold text-primary">{{ editForm.day_coverage }}%</span>
                </div>
                <input
                    v-model.number="editForm.day_coverage"
                    type="range"
                    min="0"
                    max="100"
                    step="5"
                    class="w-full accent-primary"
                />
                <div class="flex justify-between text-xs text-muted-foreground">
                    <span>0%</span>
                    <span>25%</span>
                    <span>50%</span>
                    <span>75%</span>
                    <span>100%</span>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent" @click="editingReport = null">Annuler</button>
                <button type="button" class="h-9 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90" @click="saveEdit">Enregistrer</button>
            </div>
        </div>
    </Dialog>
</template>
