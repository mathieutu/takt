<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { ChevronLeft, ChevronRight, Pencil } from 'lucide-vue-next'
import Dialog from '../components/ui/Dialog.vue'

type Project = { id: number; name: string; client_name: string; daily_rate: number }
type Report = { id: number; project_id: number; start_date: string; day_coverage: number; label: string; comments: string }

const props = withDefaults(defineProps<{
    storeUrl?: string
    baseUrl?: string
    csrfToken?: string
    currentYear?: number
    currentMonth?: number
    projects?: Project[]
    reports?: Report[]
}>(), {
    storeUrl: '/dashboard/reports',
    baseUrl: '/dashboard/reports',
    csrfToken: '',
    currentYear: () => new Date().getFullYear(),
    currentMonth: () => new Date().getMonth() + 1,
    projects: () => [],
    reports: () => [],
})

const localReports = ref<Report[]>([...props.reports])
const displayYear = ref(props.currentYear)
const displayMonth = ref(props.currentMonth)
const editingReport = ref<Report | null>(null)
const editForm = ref({ label: '', comments: '' })

const MONTHS_FR = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']

const today = new Date()
const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`

const holidaysCache = new Map<number, Map<string, string>>()
const holidays = ref<Map<string, string>>(new Map())

async function loadHolidays(year: number) {
    if (holidaysCache.has(year)) {
        holidays.value = holidaysCache.get(year)!
        return
    }
    try {
        const res = await fetch(`https://calendrier.api.gouv.fr/jours-feries/metropole/${year}.json`)
        if (res.ok) {
            const data: Record<string, string> = await res.json()
            const map = new Map(Object.entries(data))
            holidaysCache.set(year, map)
            holidays.value = map
        }
    } catch {
        // en cas d'échec réseau, on reste avec une map vide
    }
}

onMounted(() => loadHolidays(displayYear.value))
watch(displayYear, (year) => loadHolidays(year))

const monthPrefix = computed(() =>
    `${displayYear.value}-${String(displayMonth.value).padStart(2, '0')}-`
)

const daysInMonth = computed(() => {
    const y = displayYear.value
    const m = displayMonth.value
    const count = new Date(y, m, 0).getDate()
    const DAY_LETTERS = ['D', 'L', 'M', 'M', 'J', 'V', 'S']
    return Array.from({ length: count }, (_, i) => {
        const d = i + 1
        const dateStr = `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`
        const dow = new Date(y, m - 1, d).getDay()
        return { d, dateStr, isWeekend: dow === 0 || dow === 6, letter: DAY_LETTERS[dow] }
    })
})

const reportByKey = computed(() => {
    const map = new Map<string, Report>()
    for (const r of localReports.value) {
        if (r.start_date.startsWith(monthPrefix.value)) {
            map.set(`${r.project_id}:${r.start_date}`, r)
        }
    }
    return map
})

const projectStats = computed(() =>
    (props.projects ?? []).map(p => {
        const days = localReports.value
            .filter(r => r.project_id === p.id && r.start_date.startsWith(monthPrefix.value))
            .reduce((s, r) => s + r.day_coverage / 100, 0)
        return { project: p, days, ca: days * p.daily_rate }
    })
)

const totalDays = computed(() => projectStats.value.reduce((s, ps) => s + ps.days, 0))
const totalCa = computed(() => projectStats.value.reduce((s, ps) => s + ps.ca, 0))

function fmtDays(v: number) {
    return `${v % 1 === 0 ? v : v.toFixed(1)}j`
}

function prevMonth() {
    if (displayMonth.value === 1) { displayMonth.value = 12; displayYear.value-- }
    else displayMonth.value--
}

function nextMonth() {
    if (displayMonth.value === 12) { displayMonth.value = 1; displayYear.value++ }
    else displayMonth.value++
}

async function clickDay(projectId: number, dateStr: string) {
    const existing = localReports.value.find(r => r.project_id === projectId && r.start_date === dateStr) ?? null

    if (existing && existing.id < 0) return

    if (!existing) {
        const tempId = -Date.now()
        localReports.value.push({ id: tempId, project_id: projectId, start_date: dateStr, day_coverage: 50, label: '', comments: '' })
        try {
            const res = await fetch(props.storeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': props.csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ project_id: projectId, start_date: dateStr, day_coverage: 50 }),
            })
            if (res.ok) {
                const created = await res.json()
                const idx = localReports.value.findIndex(r => r.id === tempId)
                if (idx !== -1) localReports.value[idx] = {
                    id: created.id,
                    project_id: projectId,
                    start_date: (created.start_date ?? dateStr).slice(0, 10),
                    day_coverage: created.day_coverage ?? 50,
                    label: created.label ?? '',
                    comments: created.comments ?? '',
                }
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
    if (v === 50) return '½j'
    return ''
}

function openEdit(report: Report) {
    editingReport.value = report
    editForm.value = { label: report.label ?? '', comments: report.comments ?? '' }
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
            <main class="flex flex-1 flex-col overflow-hidden px-3 py-4 md:px-6 md:py-6">

                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-foreground">Compte-rendu d'activité</h2>
                    <div class="flex shrink-0 items-center gap-2">
                        <button type="button"
                            class="flex h-7 w-7 items-center justify-center rounded-md hover:bg-accent"
                            @click="prevMonth">
                            <ChevronLeft class="h-4 w-4 text-muted-foreground" />
                        </button>
                        <span class="min-w-35 text-center text-sm font-medium text-foreground">
                            {{ MONTHS_FR[displayMonth - 1] }} {{ displayYear }}
                        </span>
                        <button type="button"
                            class="flex h-7 w-7 items-center justify-center rounded-md hover:bg-accent"
                            @click="nextMonth">
                            <ChevronRight class="h-4 w-4 text-muted-foreground" />
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-auto rounded-md border border-border">
                    <table class="border-collapse" style="table-layout: fixed; width: max-content; min-width: 100%;">
                        <colgroup>
                            <col style="width: 160px; min-width: 160px;" />
                            <col v-for="day in daysInMonth" :key="day.d" style="width: 56px; min-width: 56px;" />
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="sticky left-0 top-0 z-30 border-b border-r border-border bg-background px-3 py-2 text-left text-xs font-medium text-muted-foreground">
                                    Projet
                                </th>
                                <th v-for="day in daysInMonth" :key="day.d"
                                    class="sticky top-0 z-10 border-b border-r border-border px-0 py-1.5 text-center"
                                    :class="[
                                        holidays.has(day.dateStr) ? 'bg-destructive/10' : day.isWeekend ? 'bg-muted-foreground/10' : 'bg-background',
                                        day.dateStr === todayStr ? 'bg-primary/15!' : '',
                                    ]"
                                    :title="holidays.get(day.dateStr)">
                                    <div class="text-xs font-semibold leading-none"
                                        :class="day.dateStr === todayStr ? 'text-primary' : holidays.has(day.dateStr) ? 'text-destructive' : 'text-foreground'">
                                        {{ day.d }}
                                    </div>
                                    <div class="mt-0.5 text-[10px] leading-none"
                                        :class="day.dateStr === todayStr ? 'text-primary' : holidays.has(day.dateStr) ? 'text-destructive/70' : 'text-muted-foreground'">
                                        {{ day.letter }}
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!projects?.length">
                                <td :colspan="daysInMonth.length + 1"
                                    class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    Aucun projet disponible.
                                </td>
                            </tr>
                            <tr v-for="project in projects" :key="project.id" class="group/row">
                                <td class="sticky left-0 z-10 border-b border-r border-border bg-background px-3 py-2">
                                    <div class="truncate text-sm font-medium text-foreground">{{ project.name }}</div>
                                    <div class="truncate text-xs text-muted-foreground">{{ project.client_name }}</div>
                                </td>
                                <td v-for="day in daysInMonth" :key="day.dateStr"
                                    class="group/cell relative cursor-pointer border-b border-r border-border transition-colors select-none overflow-hidden"
                                    style="height: 52px;"
                                    :class="[
                                        !(reportByKey.get(`${project.id}:${day.dateStr}`)) && holidays.has(day.dateStr) ? 'bg-destructive/10' : '',
                                        !(reportByKey.get(`${project.id}:${day.dateStr}`)) && !holidays.has(day.dateStr) && day.isWeekend ? 'bg-muted-foreground/10' : '',
                                        (reportByKey.get(`${project.id}:${day.dateStr}`)?.day_coverage ?? 0) >= 100 ? 'bg-primary/25 hover:bg-primary/30' : '',
                                        (reportByKey.get(`${project.id}:${day.dateStr}`)?.day_coverage ?? 0) > 0 && (reportByKey.get(`${project.id}:${day.dateStr}`)?.day_coverage ?? 0) < 100 ? 'bg-primary/10 hover:bg-primary/15' : '',
                                        !(reportByKey.get(`${project.id}:${day.dateStr}`)) ? 'hover:bg-accent/60' : '',
                                        day.dateStr === todayStr && !(reportByKey.get(`${project.id}:${day.dateStr}`)) ? 'ring-1 ring-inset ring-primary/50' : '',
                                    ]"
                                    @click="clickDay(project.id, day.dateStr)">
                                    <span v-if="reportByKey.get(`${project.id}:${day.dateStr}`)"
                                        class="absolute bottom-2 left-0 right-0 text-center text-sm font-bold text-primary">
                                        {{ coverageLabel(reportByKey.get(`${project.id}:${day.dateStr}`)!.day_coverage) }}
                                    </span>
                                    <button
                                        v-if="reportByKey.get(`${project.id}:${day.dateStr}`) && reportByKey.get(`${project.id}:${day.dateStr}`)!.id > 0"
                                        type="button"
                                        class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded opacity-0 cursor-pointer transition-opacity hover:bg-primary/25 group-hover/cell:opacity-100"
                                        @click.stop="openEdit(reportByKey.get(`${project.id}:${day.dateStr}`)!)">
                                        <Pencil class="h-3 w-3 text-primary" />
                                    </button>
                                    <span
                                        v-if="reportByKey.get(`${project.id}:${day.dateStr}`)?.label || reportByKey.get(`${project.id}:${day.dateStr}`)?.comments"
                                        class="absolute left-1.5 top-1.5 h-1.5 w-1.5 rounded-full bg-primary/70 transition-opacity group-hover/cell:opacity-0">
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 hidden items-center gap-5 sm:flex">
                    <div class="flex items-center gap-1.5">
                        <span class="h-3 w-3 rounded-sm border border-border bg-primary/25"></span>
                        <span class="text-xs text-muted-foreground">1 jour</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="h-3 w-3 rounded-sm border border-border bg-primary/10"></span>
                        <span class="text-xs text-muted-foreground">½ jour</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="h-3 w-3 rounded-sm border border-border bg-muted-foreground/10"></span>
                        <span class="text-xs text-muted-foreground">Week-end</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="h-3 w-3 rounded-sm border border-border bg-destructive/10"></span>
                        <span class="text-xs text-muted-foreground">Jour férié</span>
                    </div>
                </div>

                <div class="mt-2 flex items-center justify-between border-t border-border pt-2 md:hidden">
                    <span class="text-sm text-muted-foreground">{{ fmtDays(totalDays) }} saisis</span>
                    <span class="text-sm font-semibold text-foreground">{{ totalCa.toLocaleString('fr-FR') }} €</span>
                </div>
            </main>

            <aside class="hidden w-64 shrink-0 border-l border-border px-5 py-6 md:block overflow-y-auto">
                <p class="mb-4 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Récap du mois</p>
                <div class="space-y-4">
                    <div v-for="ps in projectStats" :key="ps.project.id">
                        <p class="mb-1 truncate text-xs font-semibold text-foreground">{{ ps.project.name }}</p>
                        <div class="space-y-0.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-muted-foreground">Jours saisis</span>
                                <span class="font-medium text-foreground">{{ fmtDays(ps.days) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-muted-foreground">CA estimé</span>
                                <span class="font-medium text-foreground">{{ ps.ca.toLocaleString('fr-FR') }} €</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 border-t border-border pt-4 space-y-1.5">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-semibold text-foreground">Total</span>
                        <span class="font-semibold text-foreground">{{ fmtDays(totalDays) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">CA total</span>
                        <span class="font-semibold text-foreground">{{ totalCa.toLocaleString('fr-FR') }} €</span>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <Dialog
        :open="editingReport !== null"
        :title="`${editingReport ? editingReport.start_date.slice(8, 10).replace(/^0/, '') : ''} ${editingReport ? MONTHS_FR[parseInt(editingReport.start_date.slice(5, 7)) - 1] : ''}`"
        @close="editingReport = null">
        <div class="space-y-4">
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-foreground">Titre</label>
                <input v-model="editForm.label" type="text" placeholder="Ex : Développement feature X"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-foreground">Description</label>
                <textarea v-model="editForm.comments" rows="3" placeholder="Détails de l'activité…"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring resize-none" />
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                    class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent"
                    @click="editingReport = null">Annuler</button>
                <button type="button"
                    class="h-9 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                    @click="saveEdit">Enregistrer</button>
            </div>
        </div>
    </Dialog>
</template>
