<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { ChevronLeft, ChevronRight, Eye } from 'lucide-vue-next'
import Dialog from '../components/ui/Dialog.vue'
import AppLayout from '../layouts/AppLayout.vue'

defineOptions({ layout: false as any })

const page = usePage()
const isAuthenticated = computed(() => !!(page.props.auth as any)?.user)

type Report = { id: number; project_id: number; start_date: string; day_coverage: number; label: string; comments: string }

const props = withDefaults(defineProps<{
    projectName?: string
    clientName?: string
    projectId?: number
    currentYear?: number
    currentMonth?: number
    reports?: Report[]
}>(), {
    projectName: '',
    clientName: '',
    projectId: 0,
    currentYear: () => new Date().getFullYear(),
    currentMonth: () => new Date().getMonth() + 1,
    reports: () => [],
})

const displayYear = ref(props.currentYear)
const displayMonth = ref(props.currentMonth)
const viewingReport = ref<Report | null>(null)

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
    } catch { /* */ }
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
    for (const r of props.reports) {
        if (r.start_date.startsWith(monthPrefix.value)) {
            map.set(`${r.project_id}:${r.start_date}`, r)
        }
    }
    return map
})

const monthDays = computed(() =>
    props.reports
        .filter(r => r.start_date.startsWith(monthPrefix.value))
        .reduce((s, r) => s + r.day_coverage / 100, 0)
)

function fmtDays(v: number) {
    return `${v % 1 === 0 ? v : v.toFixed(1)}j`
}

function coverageLabel(v: number) {
    if (v === 100) return '1j'
    if (v === 50) return '½j'
    return ''
}

function prevMonth() {
    if (displayMonth.value === 1) { displayMonth.value = 12; displayYear.value-- }
    else displayMonth.value--
}

function nextMonth() {
    if (displayMonth.value === 12) { displayMonth.value = 1; displayYear.value++ }
    else displayMonth.value++
}
</script>

<template>
    <!-- Connecté : AppLayout normal (header + sidebar) -->
    <AppLayout v-if="isAuthenticated">
        <div class="flex h-[calc(100vh-3.5rem)] flex-col overflow-hidden px-3 py-4 md:px-6 md:py-6">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-foreground">{{ projectName }}</h2>
                    <p class="text-xs text-muted-foreground">{{ clientName }} — CRA partagé</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <button type="button" class="flex h-7 w-7 items-center justify-center rounded-md hover:bg-accent" @click="prevMonth">
                        <ChevronLeft class="h-4 w-4 text-muted-foreground" />
                    </button>
                    <span class="min-w-35 text-center text-sm font-medium text-foreground">{{ MONTHS_FR[displayMonth - 1] }} {{ displayYear }}</span>
                    <button type="button" class="flex h-7 w-7 items-center justify-center rounded-md hover:bg-accent" @click="nextMonth">
                        <ChevronRight class="h-4 w-4 text-muted-foreground" />
                    </button>
                </div>
            </div>
            <div class="flex-1 min-h-0">
            <div class="overflow-y-auto rounded-md border border-border max-h-full">
                <table class="border-collapse" style="table-layout: fixed; width: max-content; min-width: 100%;">
                    <colgroup>
                        <col style="width: 160px; min-width: 160px;" />
                        <col v-for="day in daysInMonth" :key="day.d" style="width: 56px; min-width: 56px;" />
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="sticky left-0 top-0 z-30 border-b border-r border-border bg-background px-3 py-2 text-left text-xs font-medium text-muted-foreground">Projet</th>
                            <th v-for="day in daysInMonth" :key="day.d"
                                class="sticky top-0 z-10 border-b border-r border-border px-0 py-1.5 text-center"
                                :class="[holidays.has(day.dateStr) || day.isWeekend ? 'bg-muted-foreground/10' : 'bg-background', day.dateStr === todayStr ? 'bg-primary/15!' : '']"
                                :title="holidays.get(day.dateStr)">
                                <div class="text-xs font-semibold leading-none" :class="day.dateStr === todayStr ? 'text-primary' : 'text-foreground'">{{ day.d }}</div>
                                <div class="mt-0.5 text-[10px] leading-none" :class="day.dateStr === todayStr ? 'text-primary' : 'text-muted-foreground'">{{ day.letter }}</div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="group/row">
                            <td class="sticky left-0 z-10 border-b border-r border-border bg-background px-3 py-2">
                                <div class="truncate text-sm font-medium text-foreground">{{ projectName }}</div>
                                <div class="truncate text-xs text-muted-foreground">{{ clientName }}</div>
                            </td>
                            <td v-for="day in daysInMonth" :key="day.dateStr"
                                class="group/cell relative border-b border-r border-border select-none overflow-hidden cursor-default"
                                style="height: 52px;"
                                :class="[
                                    !(reportByKey.get(`${projectId}:${day.dateStr}`)) && holidays.has(day.dateStr) ? 'bg-muted-foreground/10' : '',
                                    !(reportByKey.get(`${projectId}:${day.dateStr}`)) && !holidays.has(day.dateStr) && day.isWeekend ? 'bg-muted-foreground/10' : '',
                                    (reportByKey.get(`${projectId}:${day.dateStr}`)?.day_coverage ?? 0) >= 100 ? 'bg-primary/25' : '',
                                    (reportByKey.get(`${projectId}:${day.dateStr}`)?.day_coverage ?? 0) > 0 && (reportByKey.get(`${projectId}:${day.dateStr}`)?.day_coverage ?? 0) < 100 ? 'bg-primary/10' : '',
                                    day.dateStr === todayStr && !(reportByKey.get(`${projectId}:${day.dateStr}`)) ? 'ring-1 ring-inset ring-primary/50' : '',
                                ]">
                                <span v-if="reportByKey.get(`${projectId}:${day.dateStr}`)" class="absolute bottom-2 left-0 right-0 text-center text-sm font-bold text-primary">
                                    {{ coverageLabel(reportByKey.get(`${projectId}:${day.dateStr}`)!.day_coverage) }}
                                </span>
                                <button v-if="reportByKey.get(`${projectId}:${day.dateStr}`) && (reportByKey.get(`${projectId}:${day.dateStr}`)!.label || reportByKey.get(`${projectId}:${day.dateStr}`)!.comments)"
                                    type="button"
                                    class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded opacity-0 transition-opacity hover:bg-primary/25 group-hover/cell:opacity-100"
                                    @click.stop="viewingReport = reportByKey.get(`${projectId}:${day.dateStr}`)!">
                                    <Eye class="h-3 w-3 text-primary" />
                                </button>
                                <span v-if="reportByKey.get(`${projectId}:${day.dateStr}`)?.label || reportByKey.get(`${projectId}:${day.dateStr}`)?.comments"
                                    class="absolute left-1.5 top-1.5 h-1.5 w-1.5 rounded-full bg-primary/70 transition-opacity group-hover/cell:opacity-0" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </div>
            <div class="mt-auto shrink-0 pt-3 flex items-center justify-between">
                <div class="hidden items-center gap-5 sm:flex">
                    <div class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm border border-border bg-primary/25"></span><span class="text-xs text-muted-foreground">1 jour</span></div>
                    <div class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm border border-border bg-primary/10"></span><span class="text-xs text-muted-foreground">½ jour</span></div>
                    <div class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm border border-border bg-muted-foreground/10"></span><span class="text-xs text-muted-foreground">Week-end</span></div>
                    <div class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm border border-border bg-muted-foreground/10"></span><span class="text-xs text-muted-foreground">Jour férié</span></div>
                </div>
                <span class="text-sm text-muted-foreground ml-auto">{{ fmtDays(monthDays) }} ce mois</span>
            </div>
        </div>
    </AppLayout>

    <!-- Invité : layout minimal avec bouton de conversion -->
    <div v-else class="flex h-screen flex-col overflow-hidden bg-background">
        <header class="flex shrink-0 items-center justify-between border-b border-border px-4 py-3 md:px-6">
            <div>
                <p class="text-sm font-semibold text-foreground">{{ projectName }}</p>
                <p class="text-xs text-muted-foreground">{{ clientName }}</p>
            </div>
            <a href="/login" class="h-8 rounded-md bg-primary px-3 text-xs font-medium text-primary-foreground transition-colors hover:bg-primary/90 inline-flex items-center">
                Se connecter
            </a>
        </header>
        <div class="flex flex-1 overflow-hidden">
            <main class="flex flex-1 flex-col overflow-hidden px-3 py-4 md:px-6 md:py-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-foreground">{{ MONTHS_FR[displayMonth - 1] }} {{ displayYear }}</h2>
                    <div class="flex shrink-0 items-center gap-2">
                        <button type="button" class="flex h-7 w-7 items-center justify-center rounded-md hover:bg-accent" @click="prevMonth">
                            <ChevronLeft class="h-4 w-4 text-muted-foreground" />
                        </button>
                        <button type="button" class="flex h-7 w-7 items-center justify-center rounded-md hover:bg-accent" @click="nextMonth">
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
                                <th class="sticky left-0 top-0 z-30 border-b border-r border-border bg-background px-3 py-2 text-left text-xs font-medium text-muted-foreground">Projet</th>
                                <th v-for="day in daysInMonth" :key="day.d"
                                    class="sticky top-0 z-10 border-b border-r border-border px-0 py-1.5 text-center"
                                    :class="[holidays.has(day.dateStr) || day.isWeekend ? 'bg-muted-foreground/10' : 'bg-background', day.dateStr === todayStr ? 'bg-primary/15!' : '']"
                                    :title="holidays.get(day.dateStr)">
                                    <div class="text-xs font-semibold leading-none" :class="day.dateStr === todayStr ? 'text-primary' : 'text-foreground'">{{ day.d }}</div>
                                    <div class="mt-0.5 text-[10px] leading-none" :class="day.dateStr === todayStr ? 'text-primary' : 'text-muted-foreground'">{{ day.letter }}</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="group/row">
                                <td class="sticky left-0 z-10 border-b border-r border-border bg-background px-3 py-2">
                                    <div class="truncate text-sm font-medium text-foreground">{{ projectName }}</div>
                                    <div class="truncate text-xs text-muted-foreground">{{ clientName }}</div>
                                </td>
                                <td v-for="day in daysInMonth" :key="day.dateStr"
                                    class="group/cell relative border-b border-r border-border select-none overflow-hidden cursor-default"
                                    style="height: 52px;"
                                    :class="[
                                        !(reportByKey.get(`${projectId}:${day.dateStr}`)) && holidays.has(day.dateStr) ? 'bg-muted-foreground/10' : '',
                                        !(reportByKey.get(`${projectId}:${day.dateStr}`)) && !holidays.has(day.dateStr) && day.isWeekend ? 'bg-muted-foreground/10' : '',
                                        (reportByKey.get(`${projectId}:${day.dateStr}`)?.day_coverage ?? 0) >= 100 ? 'bg-primary/25' : '',
                                        (reportByKey.get(`${projectId}:${day.dateStr}`)?.day_coverage ?? 0) > 0 && (reportByKey.get(`${projectId}:${day.dateStr}`)?.day_coverage ?? 0) < 100 ? 'bg-primary/10' : '',
                                        day.dateStr === todayStr && !(reportByKey.get(`${projectId}:${day.dateStr}`)) ? 'ring-1 ring-inset ring-primary/50' : '',
                                    ]">
                                    <span v-if="reportByKey.get(`${projectId}:${day.dateStr}`)" class="absolute bottom-2 left-0 right-0 text-center text-sm font-bold text-primary">
                                        {{ coverageLabel(reportByKey.get(`${projectId}:${day.dateStr}`)!.day_coverage) }}
                                    </span>
                                    <button v-if="reportByKey.get(`${projectId}:${day.dateStr}`) && (reportByKey.get(`${projectId}:${day.dateStr}`)!.label || reportByKey.get(`${projectId}:${day.dateStr}`)!.comments)"
                                        type="button"
                                        class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded opacity-0 transition-opacity hover:bg-primary/25 group-hover/cell:opacity-100"
                                        @click.stop="viewingReport = reportByKey.get(`${projectId}:${day.dateStr}`)!">
                                        <Eye class="h-3 w-3 text-primary" />
                                    </button>
                                    <span v-if="reportByKey.get(`${projectId}:${day.dateStr}`)?.label || reportByKey.get(`${projectId}:${day.dateStr}`)?.comments"
                                        class="absolute left-1.5 top-1.5 h-1.5 w-1.5 rounded-full bg-primary/70 transition-opacity group-hover/cell:opacity-0" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 flex items-center justify-between">
                    <div class="hidden items-center gap-5 sm:flex">
                        <div class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm border border-border bg-primary/25"></span><span class="text-xs text-muted-foreground">1 jour</span></div>
                        <div class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm border border-border bg-primary/10"></span><span class="text-xs text-muted-foreground">½ jour</span></div>
                        <div class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm border border-border bg-muted-foreground/10"></span><span class="text-xs text-muted-foreground">Week-end</span></div>
                        <div class="flex items-center gap-1.5"><span class="h-3 w-3 rounded-sm border border-border bg-muted-foreground/10"></span><span class="text-xs text-muted-foreground">Jour férié</span></div>
                    </div>
                    <span class="text-sm text-muted-foreground ml-auto">{{ fmtDays(monthDays) }} ce mois</span>
                </div>
            </main>
        </div>
    </div>

    <Dialog
        :open="viewingReport !== null"
        :title="`${viewingReport ? viewingReport.start_date.slice(8, 10).replace(/^0/, '') : ''} ${viewingReport ? MONTHS_FR[parseInt(viewingReport.start_date.slice(5, 7)) - 1] : ''}`"
        @close="viewingReport = null">
        <div class="space-y-4">
            <div v-if="viewingReport?.label" class="flex flex-col gap-1">
                <p class="text-xs font-medium text-muted-foreground">Titre</p>
                <p class="text-sm text-foreground">{{ viewingReport.label }}</p>
            </div>
            <div v-if="viewingReport?.comments" class="flex flex-col gap-1">
                <p class="text-xs font-medium text-muted-foreground">Description</p>
                <p class="text-sm text-foreground whitespace-pre-wrap">{{ viewingReport.comments }}</p>
            </div>
            <div class="flex justify-end pt-2">
                <button type="button"
                    class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent"
                    @click="viewingReport = null">Fermer</button>
            </div>
        </div>
    </Dialog>
</template>
