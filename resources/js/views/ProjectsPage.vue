<script setup lang="ts">
import { ref, computed } from 'vue'
import { Plus, Search, UserPlus } from 'lucide-vue-next'
import { useToast } from '../composables/useToast'
import type { Project, Client, CRAEntry } from '../types'
import Card from '../components/ui/Card.vue'
import Badge from '../components/ui/Badge.vue'
import Dialog from '../components/ui/Dialog.vue'
import AlertDialog from '../components/ui/AlertDialog.vue'
import Tabs from '../components/ui/Tabs.vue'
import ToastContainer from '../components/ui/ToastContainer.vue'

const props = defineProps<{
    projects: Project[]
    clients: Client[]
    entries: CRAEntry[]
    defaultTjm: number
}>()

const emit = defineEmits<{
    'project-create': [project: Omit<Project, 'id' | 'createdAt'>]
    'project-update': [id: string, data: Partial<Project>]
    'project-delete': [id: string]
}>()

const { toast } = useToast()

const localProjects = ref<Project[]>([...props.projects])
const localClients = ref<Client[]>([...props.clients])

const search = ref('')
const activeTab = ref<'all' | 'active' | 'paused' | 'completed'>('all')

const STATUS_TABS = [
    { value: 'all', label: 'Tous' },
    { value: 'active', label: 'Actifs' },
    { value: 'paused', label: 'En pause' },
    { value: 'completed', label: 'Terminés' },
]

const STATUS_LABELS: Record<Project['status'], string> = {
    active: 'Actif',
    paused: 'En pause',
    completed: 'Terminé',
}

// ── Project dialog ──────────────────────────────────────────
const dialogOpen = ref(false)
const editingProject = ref<Project | null>(null)
const deleteDialogOpen = ref(false)
const projectToDelete = ref<Project | null>(null)

const form = ref({
    name: '',
    clientId: '',
    description: '',
    status: 'active' as Project['status'],
    tjm: '' as string | number,
    startDate: '',
    endDate: '',
})

// ── New client dialog ───────────────────────────────────────
const newClientDialogOpen = ref(false)
const newClientName = ref('')
const newClientLoading = ref(false)

function openNewClient() {
    newClientName.value = ''
    newClientDialogOpen.value = true
}

async function createClient() {
    const name = newClientName.value.trim()
    if (!name) return

    newClientLoading.value = true
    try {
        const res = await fetch('/api/clients', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ name }),
        })

        if (!res.ok) throw new Error()

        const client: Client = await res.json()
        localClients.value.push(client)
        form.value.clientId = client.id
        newClientDialogOpen.value = false
        toast(`Client « ${client.name} » créé et sélectionné`, 'success')
    } catch {
        toast('Erreur lors de la création du client', 'error')
    } finally {
        newClientLoading.value = false
    }
}

// ── Helpers ─────────────────────────────────────────────────
function getClient(id: string) { return localClients.value.find(c => c.id === id) }

const filteredProjects = computed(() =>
    localProjects.value.filter(p => {
        const matchTab = activeTab.value === 'all' || p.status === activeTab.value
        const matchSearch = !search.value
            || p.name.toLowerCase().includes(search.value.toLowerCase())
            || (getClient(p.clientId)?.name ?? '').toLowerCase().includes(search.value.toLowerCase())
        return matchTab && matchSearch
    })
)

function openCreate() {
    editingProject.value = null
    form.value = {
        name: '',
        clientId: localClients.value[0]?.id ?? '',
        description: '',
        status: 'active',
        tjm: '',
        startDate: new Date().toISOString().slice(0, 10),
        endDate: '',
    }
    dialogOpen.value = true
}

function openEdit(project: Project) {
    editingProject.value = project
    form.value = {
        name: project.name,
        clientId: project.clientId,
        description: project.description ?? '',
        status: project.status,
        tjm: project.tjm ?? '',
        startDate: project.startDate,
        endDate: project.endDate ?? '',
    }
    dialogOpen.value = true
}

function saveProject() {
    if (!form.value.name.trim() || !form.value.clientId) {
        toast('Nom et client sont requis', 'error')
        return
    }
    const data: Omit<Project, 'id' | 'createdAt'> = {
        name: form.value.name,
        clientId: form.value.clientId,
        description: form.value.description || undefined,
        status: form.value.status,
        tjm: form.value.tjm !== '' ? Number(form.value.tjm) : undefined,
        startDate: form.value.startDate,
        endDate: form.value.endDate || undefined,
    }
    if (editingProject.value) {
        const idx = localProjects.value.findIndex(p => p.id === editingProject.value!.id)
        if (idx !== -1) localProjects.value[idx] = { ...localProjects.value[idx], ...data }
        emit('project-update', editingProject.value.id, data)
        toast('Projet modifié')
    } else {
        const newProject: Project = { id: `p${Date.now()}`, createdAt: new Date().toISOString().slice(0, 10), ...data }
        localProjects.value.push(newProject)
        emit('project-create', data)
        toast('Projet créé')
    }
    dialogOpen.value = false
}

function confirmDelete(project: Project) {
    projectToDelete.value = project
    deleteDialogOpen.value = true
}

function doDelete() {
    if (!projectToDelete.value) return
    localProjects.value = localProjects.value.filter(p => p.id !== projectToDelete.value!.id)
    emit('project-delete', projectToDelete.value.id)
    toast(`Projet "${projectToDelete.value.name}" supprimé`, 'info')
    deleteDialogOpen.value = false
    projectToDelete.value = null
}

function getEntryCount(projectId: string) {
    return props.entries.filter(e => e.projectId === projectId).length
}

function formatDate(date: string) {
    return new Date(date).toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' })
}

function statusVariant(status: Project['status']) {
    if (status === 'active') return 'success'
    if (status === 'paused') return 'warning'
    return 'secondary'
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-foreground">Projets</h1>
                <p class="text-sm text-muted-foreground">{{ localProjects.length }} projets au total</p>
            </div>
            <button
                type="button"
                class="inline-flex items-center gap-2 h-9 px-4 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:bg-primary/90 transition-colors"
                @click="openCreate"
            >
                <Plus class="h-4 w-4" />
                Nouveau projet
            </button>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <div class="relative max-w-sm flex-1">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Rechercher un projet..."
                    class="h-9 w-full rounded-md border border-input bg-background pl-9 pr-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                />
            </div>
            <Tabs v-model="activeTab" :tabs="STATUS_TABS" />
        </div>

        <div v-if="filteredProjects.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="project in filteredProjects" :key="project.id">
                <div class="p-5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: getClient(project.clientId)?.color ?? '#888' }" />
                                <p class="truncate font-medium text-foreground">{{ project.name }}</p>
                            </div>
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ getClient(project.clientId)?.name ?? '—' }}</p>
                        </div>
                        <Badge :variant="statusVariant(project.status)">{{ STATUS_LABELS[project.status] }}</Badge>
                    </div>

                    <p v-if="project.description" class="mt-2 text-xs text-muted-foreground line-clamp-2">{{ project.description }}</p>

                    <div class="mt-3 space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">TJM</span>
                            <span class="font-medium text-foreground">{{ project.tjm ? `${project.tjm} €` : `${defaultTjm} € (défaut)` }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">Début</span>
                            <span class="font-medium text-foreground">{{ formatDate(project.startDate) }}</span>
                        </div>
                        <div v-if="project.endDate" class="flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">Fin</span>
                            <span class="font-medium text-foreground">{{ formatDate(project.endDate) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">Saisies</span>
                            <span class="font-medium text-foreground">{{ getEntryCount(project.id) }} entrées</span>
                        </div>
                    </div>

                    <div class="mt-3 flex gap-2 border-t border-border pt-3">
                        <button type="button" class="flex-1 h-7 rounded-md border border-border text-xs text-foreground hover:bg-accent transition-colors" @click="openEdit(project)">Modifier</button>
                        <button type="button" class="h-7 px-2 rounded-md border border-destructive/30 text-xs text-destructive hover:bg-destructive/10 transition-colors" @click="confirmDelete(project)">Supprimer</button>
                    </div>
                </div>
            </Card>
        </div>

        <div v-else class="flex flex-col items-center justify-center py-16 text-center">
            <p class="text-sm text-muted-foreground">Aucun projet trouvé</p>
            <button type="button" class="mt-3 text-sm text-primary hover:underline" @click="openCreate">Créer un nouveau projet</button>
        </div>
    </div>

    <!-- ── Dialog projet ── -->
    <Dialog :open="dialogOpen" :title="editingProject ? 'Modifier le projet' : 'Nouveau projet'" max-width="max-w-lg" @close="dialogOpen = false">
        <form class="space-y-4" @submit.prevent="saveProject">
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-foreground">Nom du projet *</label>
                <input v-model="form.name" type="text" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" placeholder="Refonte site vitrine" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium text-foreground">Client *</label>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 text-xs text-primary hover:text-primary/80 transition-colors"
                            @click="openNewClient"
                        >
                            <UserPlus class="h-3.5 w-3.5" />
                            Nouveau client
                        </button>
                    </div>
                    <select v-model="form.clientId" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                        <option value="" disabled>Sélectionner un client</option>
                        <option v-for="client in localClients" :key="client.id" :value="client.id">{{ client.name }}</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Statut</label>
                    <select v-model="form.status" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                        <option value="active">Actif</option>
                        <option value="paused">En pause</option>
                        <option value="completed">Terminé</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-foreground">Description</label>
                <textarea v-model="form.description" rows="2" class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring resize-none" placeholder="Description du projet..." />
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">TJM (€)</label>
                    <input v-model="form.tjm" type="number" min="0" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" :placeholder="`${defaultTjm} (défaut)`" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Date début</label>
                    <input v-model="form.startDate" type="date" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Date fin</label>
                    <input v-model="form.endDate" type="date" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="h-9 px-4 text-sm font-medium rounded-md border border-border bg-background hover:bg-accent transition-colors" @click="dialogOpen = false">Annuler</button>
                <button type="submit" class="h-9 px-4 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:bg-primary/90 transition-colors">{{ editingProject ? 'Enregistrer' : 'Créer' }}</button>
            </div>
        </form>
    </Dialog>

    <!-- ── Dialog nouveau client ── -->
    <Dialog
        :open="newClientDialogOpen"
        title="Nouveau client"
        description="Créez rapidement un client et sélectionnez-le pour votre projet."
        @close="newClientDialogOpen = false"
    >
        <form class="space-y-4" @submit.prevent="createClient">
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-foreground">Nom du client *</label>
                <input
                    v-model="newClientName"
                    type="text"
                    autofocus
                    placeholder="Acme Corp"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                />
            </div>

            <p class="text-xs text-muted-foreground">
                Vous pourrez compléter les informations du client (email, téléphone…) depuis la page Clients.
            </p>

            <div class="flex justify-end gap-2 pt-1">
                <button
                    type="button"
                    class="h-9 px-4 text-sm font-medium rounded-md border border-border bg-background hover:bg-accent transition-colors"
                    @click="newClientDialogOpen = false"
                >
                    Annuler
                </button>
                <button
                    type="submit"
                    :disabled="newClientLoading || !newClientName.trim()"
                    class="h-9 px-4 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ newClientLoading ? 'Création…' : 'Créer et sélectionner' }}
                </button>
            </div>
        </form>
    </Dialog>

    <AlertDialog
        :open="deleteDialogOpen"
        title="Supprimer le projet"
        :description="`Supprimer « ${projectToDelete?.name} » ? Toutes les saisies associées seront supprimées.`"
        confirm-label="Supprimer"
        variant="destructive"
        @confirm="doDelete"
        @cancel="deleteDialogOpen = false"
    />

    <ToastContainer />
</template>
