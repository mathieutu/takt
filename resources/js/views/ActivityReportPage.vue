<script setup lang="ts">
import { ref } from 'vue'
import { Plus, Trash2, CalendarDays } from 'lucide-vue-next'
import Card from '../components/ui/Card.vue'
import CardContent from '../components/ui/CardContent.vue'
import Dialog from '../components/ui/Dialog.vue'

type Project = { id: number; name: string }

type Report = {
    id: number
    label: string
    start_date: string
    day_coverage: number
    comments: string
}

const props = withDefaults(defineProps<{
    indexUrl?: string
    storeUrl?: string
    baseUrl?: string
    csrfToken?: string
    projects?: Project[]
    selectedProject?: Project | null
    reports?: Report[]
}>(), {
    indexUrl: '/dashboard/reports',
    storeUrl: '/dashboard/reports',
    baseUrl: '/dashboard/reports',
    csrfToken: '',
    projects: () => [],
    selectedProject: null,
    reports: () => [],
})

const localReports = ref<Report[]>([...props.reports])
const addOpen = ref(false)
const submitting = ref(false)
const deleteConfirmId = ref<number | null>(null)

const form = ref({
    label: '',
    start_date: '',
    day_coverage: 0,
    comments: '',
})

function formatDate(iso: string) {
    return new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })
}

function coverageLabel(value: number) {
    if (value === 100) return '1 journée'
    if (value === 50) return '½ journée'
    return `${value}% de la journée`
}

function resetForm() {
    form.value = { label: '', start_date: '', day_coverage: 0, comments: '' }
}

async function submitAdd() {
    if (!props.selectedProject || submitting.value) return
    submitting.value = true
    try {
        const res = await fetch(props.storeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': props.csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                project_id: props.selectedProject.id,
                label: form.value.label || null,
                start_date: form.value.start_date,
                day_coverage: form.value.day_coverage,
                comments: form.value.comments || null,
            }),
        })
        if (res.ok) {
            const created: Report = await res.json()
            localReports.value.unshift({
                id: created.id,
                label: created.label ?? '',
                start_date: created.start_date,
                day_coverage: created.day_coverage ?? 0,
                comments: created.comments ?? '',
            })
            addOpen.value = false
            resetForm()
        }
    } finally {
        submitting.value = false
    }
}

async function deleteReport(id: number) {
    const res = await fetch(`${props.baseUrl}/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': props.csrfToken,
            'Accept': 'application/json',
        },
    })
    if (res.ok) {
        localReports.value = localReports.value.filter(r => r.id !== id)
        deleteConfirmId.value = null
    }
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background">
        <main class="flex-1 px-6 py-8">
            <div class="mx-auto max-w-3xl space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-lg font-semibold text-foreground">Rapports d'activité</h1>
                        <p class="text-sm text-muted-foreground">
                            {{ selectedProject ? selectedProject.name : 'Sélectionnez un projet' }}
                        </p>
                    </div>
                    <button
                        v-if="selectedProject"
                        type="button"
                        class="inline-flex h-9 items-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                        @click="addOpen = true"
                    >
                        <Plus class="h-4 w-4" />
                        Ajouter
                    </button>
                </div>

                <form :action="indexUrl" method="GET">
                    <select
                        name="project_id"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                        :value="selectedProject?.id"
                        @change="($event.target as HTMLSelectElement).form!.submit()"
                    >
                        <option value="" disabled :selected="!selectedProject">Sélectionner un projet</option>
                        <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </form>

                <div v-if="!selectedProject" class="rounded-lg border border-border bg-card p-8 text-center">
                    <CalendarDays class="mx-auto h-8 w-8 text-muted-foreground" />
                    <p class="mt-2 text-sm text-muted-foreground">Sélectionnez un projet pour afficher ses rapports.</p>
                </div>

                <template v-else>
                    <p v-if="localReports.length === 0" class="text-sm text-muted-foreground">
                        Aucun rapport pour ce projet.
                    </p>

                    <div v-else class="space-y-3">
                        <div
                            v-for="report in localReports"
                            :key="report.id"
                            class="rounded-lg border border-border bg-card p-4"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-foreground">
                                        {{ report.label || '(sans intitulé)' }}
                                    </p>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                                        <span class="text-xs text-muted-foreground">{{ formatDate(report.start_date) }}</span>
                                        <span class="text-xs text-muted-foreground">·</span>
                                        <span class="text-xs text-muted-foreground">{{ coverageLabel(report.day_coverage) }}</span>
                                    </div>
                                    <p v-if="report.comments" class="mt-2 text-xs text-muted-foreground">{{ report.comments }}</p>
                                </div>
                                <button
                                    type="button"
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive"
                                    @click="deleteConfirmId = report.id"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </main>

        <Dialog :open="addOpen" title="Ajouter un rapport" @close="addOpen = false; resetForm()">
            <form class="space-y-4" @submit.prevent="submitAdd">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Intitulé <span class="text-muted-foreground font-normal">— optionnel</span></label>
                    <input
                        v-model="form.label"
                        type="text"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                    />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Date</label>
                    <input
                        v-model="form.start_date"
                        type="date"
                        required
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                    />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">
                        Temps de travail — <span class="text-primary font-medium">{{ coverageLabel(form.day_coverage) }}</span>
                    </label>
                    <input
                        v-model.number="form.day_coverage"
                        type="range"
                        min="0"
                        max="100"
                        step="5"
                        class="w-full accent-primary"
                    />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Commentaires <span class="text-muted-foreground font-normal">— optionnel</span></label>
                    <textarea
                        v-model="form.comments"
                        rows="3"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring resize-none"
                    />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent" @click="addOpen = false; resetForm()">Annuler</button>
                    <button type="submit" :disabled="submitting" class="h-9 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50">
                        Ajouter
                    </button>
                </div>
            </form>
        </Dialog>

        <Dialog :open="deleteConfirmId !== null" title="Supprimer le rapport" @close="deleteConfirmId = null">
            <p class="text-sm text-muted-foreground">Ce rapport sera définitivement supprimé.</p>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent" @click="deleteConfirmId = null">Annuler</button>
                <button type="button" class="h-9 rounded-md bg-destructive px-4 text-sm font-medium text-white transition-colors hover:bg-destructive/90" @click="deleteReport(deleteConfirmId!)">Supprimer</button>
            </div>
        </Dialog>
    </div>
</template>
