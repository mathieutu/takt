<script setup lang="ts">
import { ref, computed } from 'vue'
import { Plus, MoreVertical } from 'lucide-vue-next'
import Dialog from '../components/ui/Dialog.vue'
import DropdownMenu from '../components/ui/DropdownMenu.vue'

type Client = {
    id: number
    name: string
}

type Project = {
    id: number
    name: string
    description: string
    daily_rate: number | null
    client_id: number
    client_name: string
    created_at: string
}

const props = withDefaults(defineProps<{
    storeAction?: string
    baseAction?: string
    csrfToken?: string
    projects?: Project[]
    clients?: Client[]
    errors?: Record<string, string[]>
    old?: Record<string, string>
}>(), {
    storeAction: '/dashboard/projects',
    baseAction: '/dashboard/projects',
    csrfToken: '',
    projects: () => [],
    clients: () => [],
    errors: () => ({}),
    old: () => ({}),
})

const search = ref('')
const createOpen = ref(false)
const editingProject = ref<Project | null>(null)
const deletingProject = ref<Project | null>(null)

const createForm = ref({
    name: props.old?.name ?? '',
    description: props.old?.description ?? '',
    daily_rate: props.old?.daily_rate ?? '',
    client_id: props.old?.client_id ?? (props.clients[0]?.id?.toString() ?? ''),
})

const filtered = computed(() =>
    props.projects.filter(p =>
        p.name.toLowerCase().includes(search.value.toLowerCase()) ||
        p.client_name.toLowerCase().includes(search.value.toLowerCase())
    )
)

const avatarColors = [
    'bg-blue-100 text-blue-700',
    'bg-violet-100 text-violet-700',
    'bg-emerald-100 text-emerald-700',
    'bg-amber-100 text-amber-700',
    'bg-rose-100 text-rose-700',
    'bg-cyan-100 text-cyan-700',
]

function avatarColor(name: string) {
    let hash = 0
    for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
    return avatarColors[Math.abs(hash) % avatarColors.length]
}

function initials(name: string) {
    return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()
}

function openEdit(project: Project) {
    editingProject.value = { ...project }
}

function fieldError(key: string): string | null {
    return props.errors[key]?.[0] ?? null
}

const hasCreateErrors = computed(() =>
    !!fieldError('name') || !!fieldError('description') || !!fieldError('daily_rate') || !!fieldError('client_id')
)

function menuItems(project: Project) {
    return [
        { label: 'Modifier', action: () => openEdit(project) },
        { label: 'Supprimer', action: () => { deletingProject.value = project }, variant: 'destructive' as const },
    ]
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background">
        <main class="flex-1 px-6 py-8">
            <div class="mx-auto max-w-5xl space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-lg font-semibold text-foreground">Projets</h1>
                        <p class="text-sm text-muted-foreground">{{ projects.length }} projet{{ projects.length !== 1 ? 's' : '' }} au total</p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-9 items-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                        @click="createOpen = true"
                    >
                        <Plus class="h-4 w-4" />
                        Nouveau projet
                    </button>
                </div>

                <input
                    v-model="search"
                    type="text"
                    placeholder="Rechercher un projet ou un client..."
                    class="h-9 w-80 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                />

                <p v-if="clients.length === 0" class="text-sm text-muted-foreground">
                    Créez d'abord un client avant d'ajouter des projets.
                </p>

                <p v-else-if="projects.length === 0" class="text-sm text-muted-foreground">
                    Aucun projet pour l'instant.
                </p>

                <p v-else-if="filtered.length === 0" class="text-sm text-muted-foreground">
                    Aucun projet ne correspond à votre recherche.
                </p>

                <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="project in filtered"
                        :key="project.id"
                        class="rounded-lg border border-border bg-card p-5"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold"
                                    :class="avatarColor(project.name)"
                                >
                                    {{ initials(project.name) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-foreground">{{ project.name }}</p>
                                    <p class="truncate text-xs text-muted-foreground">{{ project.client_name }}</p>
                                </div>
                            </div>
                            <DropdownMenu :items="menuItems(project)" class="shrink-0">
                                <button
                                    type="button"
                                    class="flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                >
                                    <MoreVertical class="h-4 w-4" />
                                </button>
                            </DropdownMenu>
                        </div>

                        <p v-if="project.description" class="mt-3 text-xs text-muted-foreground line-clamp-2">{{ project.description }}</p>

                        <div class="mt-3 flex items-center justify-between">
                            <span v-if="project.daily_rate" class="text-xs font-medium text-foreground">{{ project.daily_rate }} €/jour</span>
                            <span v-else class="text-xs text-muted-foreground">TJM client</span>
                            <span v-if="project.created_at" class="text-xs text-muted-foreground">{{ project.created_at }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <Dialog :open="createOpen || hasCreateErrors" title="Nouveau projet" @close="createOpen = false">
            <form :action="storeAction" method="POST" class="space-y-4">
                <input type="hidden" name="_token" :value="csrfToken" />
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Nom</label>
                    <input
                        v-model="createForm.name"
                        name="name"
                        type="text"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                        :class="{ 'border-destructive focus:ring-destructive': fieldError('name') }"
                    />
                    <p v-if="fieldError('name')" class="text-xs text-destructive">{{ fieldError('name') }}</p>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Description</label>
                    <input
                        v-model="createForm.description"
                        name="description"
                        type="text"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                        :class="{ 'border-destructive focus:ring-destructive': fieldError('description') }"
                    />
                    <p v-if="fieldError('description')" class="text-xs text-destructive">{{ fieldError('description') }}</p>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">TJM spécifique (€/jour) <span class="text-muted-foreground font-normal">— optionnel</span></label>
                    <input
                        v-model="createForm.daily_rate"
                        name="daily_rate"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="Utilise le TJM du client par défaut"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                        :class="{ 'border-destructive focus:ring-destructive': fieldError('daily_rate') }"
                    />
                    <p v-if="fieldError('daily_rate')" class="text-xs text-destructive">{{ fieldError('daily_rate') }}</p>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Client</label>
                    <select
                        v-model="createForm.client_id"
                        name="client_id"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                        :class="{ 'border-destructive focus:ring-destructive': fieldError('client_id') }"
                    >
                        <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                    </select>
                    <p v-if="fieldError('client_id')" class="text-xs text-destructive">{{ fieldError('client_id') }}</p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent" @click="createOpen = false">Annuler</button>
                    <button type="submit" class="h-9 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90">Créer</button>
                </div>
            </form>
        </Dialog>

        <Dialog :open="editingProject !== null" title="Modifier le projet" @close="editingProject = null">
            <form v-if="editingProject" :action="`${baseAction}/${editingProject.id}`" method="POST" class="space-y-4">
                <input type="hidden" name="_token" :value="csrfToken" />
                <input type="hidden" name="_method" value="PUT" />
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Nom</label>
                    <input v-model="editingProject.name" name="name" type="text" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Description</label>
                    <input v-model="editingProject.description" name="description" type="text" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">TJM spécifique (€/jour) <span class="text-muted-foreground font-normal">— optionnel</span></label>
                    <input v-model="editingProject.daily_rate" name="daily_rate" type="number" min="0" step="0.01" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Client</label>
                    <select v-model="editingProject.client_id" name="client_id" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                        <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent" @click="editingProject = null">Annuler</button>
                    <button type="submit" class="h-9 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90">Enregistrer</button>
                </div>
            </form>
        </Dialog>

        <Dialog :open="deletingProject !== null" title="Supprimer le projet" @close="deletingProject = null">
            <p class="text-sm text-muted-foreground">
                Supprimer <span class="font-medium text-foreground">{{ deletingProject?.name }}</span> ? Cette action est irréversible.
            </p>
            <form v-if="deletingProject" :action="`${baseAction}/${deletingProject.id}`" method="POST" class="mt-4 flex justify-end gap-2">
                <input type="hidden" name="_token" :value="csrfToken" />
                <input type="hidden" name="_method" value="DELETE" />
                <button type="button" class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent" @click="deletingProject = null">Annuler</button>
                <button type="submit" class="h-9 rounded-md bg-destructive px-4 text-sm font-medium text-white transition-colors hover:bg-destructive/90">Supprimer</button>
            </form>
        </Dialog>
    </div>
</template>
