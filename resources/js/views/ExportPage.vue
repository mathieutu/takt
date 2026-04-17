<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Download, ChevronDown, X, Trash2 } from 'lucide-vue-next'

type Entry         = { id: number; start_date: string; day_coverage: number; label: string; comments: string }
type Project       = { id: number; name: string; client_name: string; daily_rate: number; entries: Entry[] }
type ProjectOption = { id: number; name: string; client_name: string }

const props = withDefaults(defineProps<{
    indexUrl?:           string
    csvUrl?:             string
    allProjects?:        ProjectOption[]
    selectedProjectIds?: number[]
    dateStart?:          string
    dateEnd?:            string
    projects?:           Project[]
}>(), {
    indexUrl:           '/dashboard/exports',
    csvUrl:             '/dashboard/exports/csv',
    allProjects:        () => [],
    selectedProjectIds: () => [],
    dateStart:          '',
    dateEnd:            '',
    projects:           () => [],
})

const MONTHS_FR = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre']

const localProjectIds = ref<number[]>([...props.selectedProjectIds])
const localDateStart  = ref(props.dateStart)
const localDateEnd    = ref(props.dateEnd)
const dropdownOpen    = ref(false)
const dropdownRef     = ref<HTMLElement | null>(null)
const localProjects   = ref<Project[]>([...props.projects])
const loading         = ref(false)
const hasLoaded       = ref(props.projects.length > 0 || props.selectedProjectIds.length > 0)

function handleOutsideClick(e: MouseEvent) {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) {
        dropdownOpen.value = false
    }
}
onMounted(() => document.addEventListener('mousedown', handleOutsideClick))
onUnmounted(() => document.removeEventListener('mousedown', handleOutsideClick))

function toggleProject(id: number) {
    const idx = localProjectIds.value.indexOf(id)
    if (idx === -1) localProjectIds.value.push(id)
    else localProjectIds.value.splice(idx, 1)
}

function removeProject(id: number) {
    localProjectIds.value = localProjectIds.value.filter(p => p !== id)
}

const selectedProjects = computed(() =>
    props.allProjects.filter(p => localProjectIds.value.includes(p.id))
)

const dropdownLabel = computed(() => {
    const n = localProjectIds.value.length
    if (n === 0) return 'Sélectionner des projets…'
    if (n === 1) return '1 projet sélectionné'
    return `${n} projets sélectionnés`
})

const allEntries = computed(() => {
    const rows: { date: string; project_name: string; client_name: string; daily_rate: number; day_coverage: number; label: string }[] = []
    for (const p of localProjects.value) {
        for (const e of p.entries) {
            rows.push({
                date:         e.start_date,
                project_name: p.name,
                client_name:  p.client_name,
                daily_rate:   p.daily_rate,
                day_coverage: e.day_coverage,
                label:        e.label,
            })
        }
    }
    return rows.sort((a, b) => a.date.localeCompare(b.date))
})

const totalDays = computed(() =>
    allEntries.value.reduce((s, e) => s + e.day_coverage / 100, 0)
)

const totalCA = computed(() =>
    allEntries.value.reduce((s, e) => s + (e.day_coverage / 100) * e.daily_rate, 0)
)

const xlsxUrl = computed(() => {
    const params = new URLSearchParams()
    for (const id of localProjectIds.value) params.append('projects[]', String(id))
    if (localDateStart.value) params.set('date_start', localDateStart.value)
    if (localDateEnd.value)   params.set('date_end',   localDateEnd.value)
    return `${props.csvUrl}?${params.toString()}`
})

async function applyFilters() {
    if (localProjectIds.value.length === 0) return
    loading.value = true
    dropdownOpen.value = false
    const params = new URLSearchParams()
    for (const id of localProjectIds.value) params.append('projects[]', String(id))
    if (localDateStart.value) params.set('date_start', localDateStart.value)
    if (localDateEnd.value)   params.set('date_end',   localDateEnd.value)
    try {
        const res = await fetch(`${props.indexUrl}?${params.toString()}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
        if (res.ok) {
            const data = await res.json()
            localProjects.value = data.projects
            hasLoaded.value = true
        }
    } finally {
        loading.value = false
    }
}

function clearExport() {
    localProjects.value = []
    localProjectIds.value = []
    localDateStart.value = ''
    localDateEnd.value = ''
    hasLoaded.value = false
}

function formatDate(dateStr: string) {
    const [y, m, d] = dateStr.split('-')
    return `${d} ${MONTHS_FR[parseInt(m) - 1].slice(0, 3)}. ${y}`
}

function coverageLabel(v: number) {
    if (v === 100) return '1j'
    if (v === 50)  return '½j'
    return `${v}%`
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background">
        <main class="flex-1 px-6 py-8">
            <div class="mx-auto max-w-5xl space-y-6">

                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-lg font-semibold text-foreground">Exports</h1>
                        <p class="text-sm text-muted-foreground">Prévisualisez et exportez vos activités</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="hasLoaded"
                            type="button"
                            @click="clearExport"
                            class="inline-flex h-9 items-center gap-2 rounded-md border border-border px-4 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent/60 hover:text-foreground"
                        >
                            <Trash2 class="h-4 w-4" />
                            Réinitialiser
                        </button>
                        <a
                            v-if="hasLoaded && allEntries.length > 0"
                            :href="xlsxUrl"
                            class="inline-flex h-9 items-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                        >
                            <Download class="h-4 w-4" />
                            Exporter XLSX
                        </a>
                    </div>
                </div>

                <div class="rounded-lg border border-border bg-card p-4 space-y-3">
                    <div class="flex flex-wrap items-end gap-3">

                        <div class="flex flex-col gap-1.5 relative min-w-[260px]" ref="dropdownRef">
                            <label class="text-xs font-medium text-muted-foreground">Projets</label>
                            <button
                                type="button"
                                @click="dropdownOpen = !dropdownOpen"
                                class="flex h-9 w-full items-center justify-between rounded-md border border-input bg-background px-3 text-sm text-foreground transition-colors hover:bg-accent/40 focus:outline-none"
                            >
                                <span :class="localProjectIds.length === 0 ? 'text-muted-foreground' : 'text-foreground'">
                                    {{ dropdownLabel }}
                                </span>
                                <ChevronDown class="h-4 w-4 text-muted-foreground shrink-0 ml-2 transition-transform" :class="dropdownOpen ? 'rotate-180' : ''" />
                            </button>

                            <div
                                v-if="dropdownOpen"
                                class="absolute top-full left-0 z-20 mt-1 w-full min-w-[260px] rounded-md border border-border shadow-lg"
                                style="background-color: var(--background)"
                            >
                                <div class="max-h-52 overflow-y-auto p-1">
                                    <label
                                        v-for="p in allProjects"
                                        :key="p.id"
                                        class="flex items-center gap-2.5 rounded px-2.5 py-2 text-sm cursor-pointer hover:bg-accent/60 transition-colors"
                                        @click.prevent="toggleProject(p.id)"
                                    >
                                        <span
                                            class="flex h-4 w-4 shrink-0 items-center justify-center rounded border transition-colors"
                                            :class="localProjectIds.includes(p.id) ? 'bg-primary border-primary' : 'border-input bg-background'"
                                        >
                                            <svg v-if="localProjectIds.includes(p.id)" viewBox="0 0 10 8" fill="none" class="h-2.5 w-2.5">
                                                <path d="M1 4l2.5 2.5L9 1" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                        <span class="flex-1 text-foreground truncate">{{ p.name }}</span>
                                        <span class="text-muted-foreground text-xs shrink-0">{{ p.client_name }}</span>
                                    </label>
                                    <p v-if="allProjects.length === 0" class="px-3 py-2 text-sm text-muted-foreground">
                                        Aucun projet disponible
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-end gap-2">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-medium text-muted-foreground">Du</label>
                                <input
                                    v-model="localDateStart"
                                    type="date"
                                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                />
                            </div>
                            <span class="pb-2 text-muted-foreground">—</span>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-medium text-muted-foreground">Au</label>
                                <input
                                    v-model="localDateEnd"
                                    type="date"
                                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                />
                            </div>
                        </div>

                        <button
                            type="button"
                            :disabled="loading || localProjectIds.length === 0"
                            class="h-9 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed"
                            @click="applyFilters"
                        >
                            <span v-if="loading" class="flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                </svg>
                                Chargement…
                            </span>
                            <span v-else>Appliquer</span>
                        </button>
                    </div>

                    <div v-if="selectedProjects.length > 0" class="flex flex-wrap gap-1.5">
                        <span
                            v-for="p in selectedProjects"
                            :key="p.id"
                            class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary"
                        >
                            {{ p.name }}
                            <button type="button" @click="removeProject(p.id)" class="ml-0.5 hover:text-primary/60 transition-colors">
                                <X class="h-3 w-3" />
                            </button>
                        </span>
                    </div>
                </div>

                <p v-if="!hasLoaded && localProjectIds.length === 0" class="text-sm text-muted-foreground">
                    Sélectionnez un ou plusieurs projets puis cliquez sur Appliquer.
                </p>

                <p v-else-if="!hasLoaded && localProjectIds.length > 0" class="text-sm text-muted-foreground">
                    Cliquez sur Appliquer pour charger les activités.
                </p>

                <p v-else-if="hasLoaded && allEntries.length === 0" class="text-sm text-muted-foreground">
                    Aucune activité pour les filtres sélectionnés.
                </p>

                <div v-else-if="hasLoaded" class="overflow-hidden rounded-lg border border-border">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/40">
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-muted-foreground">Date</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-muted-foreground">Projet</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-muted-foreground">Client</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-muted-foreground">Intitulé</th>
                                <th class="px-4 py-2.5 text-right text-xs font-medium text-muted-foreground">Durée</th>
                                <th class="px-4 py-2.5 text-right text-xs font-medium text-muted-foreground">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(entry, i) in allEntries"
                                :key="i"
                                class="border-b border-border last:border-0 hover:bg-accent/40 transition-colors"
                            >
                                <td class="px-4 py-2.5 text-muted-foreground">{{ formatDate(entry.date) }}</td>
                                <td class="px-4 py-2.5 font-medium text-foreground">{{ entry.project_name }}</td>
                                <td class="px-4 py-2.5 text-muted-foreground">{{ entry.client_name }}</td>
                                <td class="px-4 py-2.5 text-muted-foreground">{{ entry.label || '—' }}</td>
                                <td class="px-4 py-2.5 text-right font-medium text-foreground">{{ coverageLabel(entry.day_coverage) }}</td>
                                <td class="px-4 py-2.5 text-right text-foreground">
                                    {{ entry.daily_rate > 0 ? ((entry.day_coverage / 100) * entry.daily_rate).toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + '\u00a0€' : '—' }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-border bg-muted/40">
                                <td colspan="4" class="px-4 py-2.5 text-xs font-medium text-muted-foreground">Total</td>
                                <td class="px-4 py-2.5 text-right text-sm font-semibold text-foreground">
                                    {{ totalDays % 1 === 0 ? totalDays : totalDays.toFixed(1) }}j
                                </td>
                                <td class="px-4 py-2.5 text-right text-sm font-semibold text-foreground">
                                    {{ totalCA > 0 ? totalCA.toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + '\u00a0€' : '—' }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            </div>
        </main>
    </div>
</template>
