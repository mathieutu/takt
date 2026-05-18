<script setup lang="ts">
import { MoreVertical, Pencil, Plus, Share2, Trash2, Users } from 'lucide-vue-next'
import { computed, onMounted, ref, watch } from 'vue'
import Dialog from '../components/ui/Dialog.vue'
import DropdownMenu from '../components/ui/DropdownMenu.vue'

type Client = {
    id: number
    name: string
    daily_rate: number
    is_owner: boolean
    is_shared: boolean
}

type Project = {
    id: number
    name: string
    description: string
    daily_rate: number | null
    client_id: number
    client_name: string
    created_at: string
    is_owner: boolean
    is_shared: boolean
}

const props = withDefaults(defineProps<{
    storeAction?: string
    baseAction?: string
    csrfToken?: string
    projects?: Project[]
    clients?: Client[]
    open?: boolean
    errors?: Record<string, string[]>
    old?: Record<string, string>
}>(), {
    storeAction: '/dashboard/projects',
    baseAction: '/dashboard/projects',
    csrfToken: '',
    projects: () => [],
    clients: () => [],
    open: false,
    errors: () => ({}),
    old: () => ({}),
})

const search = ref('')
const createOpen = ref(false)
const editingProject = ref<Project | null>(null)
const deletingProject = ref<Project | null>(null)
const deletingClient = ref<Client | null>(null)
const sharingProject = ref<Project | null>(null)
const sharingClient = ref<Client | null>(null)
const shareUrl = ref('')
const copied = ref(false)
const shareLoading = ref(false)
const editingClient = ref<Client | null>(null)

const defaultClientId = props.old?.client_id ?? (props.clients.length > 0 ? props.clients[0]?.id?.toString() : 'new')

const createForm = ref({
    name: props.old?.name ?? '',
    description: props.old?.description ?? '',
    daily_rate: props.old?.daily_rate ?? '',
    client_id: defaultClientId,
    client_name: props.old?.client_name ?? '',
    client_rate: props.old?.client_rate ?? '',
})

const isNewClient = computed(() => createForm.value.client_id === 'new')

watch(() => createForm.value.client_id, (clientId) => {
    if (clientId === 'new') {
        createForm.value.daily_rate = ''
    } else {
        const client = props.clients.find(c => c.id === Number(clientId))
        if (client) createForm.value.daily_rate = String(client.daily_rate)
    }
}, { immediate: true })

onMounted(() => {
    if (props.open || props.projects.length === 0) {
        createOpen.value = true
    }
})

const filtered = computed(() =>
    props.projects.filter(p =>
        p.name.toLowerCase().includes(search.value.toLowerCase()) ||
        p.client_name.toLowerCase().includes(search.value.toLowerCase())
    )
)

const selectedCreateClientRate = computed(() => {
    if (isNewClient.value) return null
    const client = props.clients.find(c => c.id === Number(createForm.value.client_id))
    return client?.daily_rate ?? null
})

const editClientRate = computed(() => {
    if (!editingProject.value) return null
    const client = props.clients.find(c => c.id === editingProject.value!.client_id)
    return client?.daily_rate ?? null
})

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
    !!fieldError('name') || !!fieldError('description') || !!fieldError('daily_rate') ||
    !!fieldError('client_id') || !!fieldError('client_name') || !!fieldError('client_rate')
)

function menuItems(project: Project) {
    return [
        { label: 'Modifier', action: () => openEdit(project) },
        { label: 'Supprimer', action: () => { deletingProject.value = project }, variant: 'destructive' as const },
    ]
}

async function openShare(project: Project) {
    sharingProject.value = project
    shareUrl.value = ''
    copied.value = false
    shareLoading.value = true
    try {
        const res = await fetch(`${props.baseAction}/${project.id}/share`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': props.csrfToken, 'Accept': 'application/json' },
        })
        if (!res.ok) throw new Error(`HTTP ${res.status}`)
        const data = await res.json()
        shareUrl.value = data.url
    } catch (e) {
        shareUrl.value = ''
        console.error('Erreur lors de la génération du lien de partage', e)
    } finally {
        shareLoading.value = false
    }
}

async function openShareClient(client: Client) {
    sharingClient.value = client
    shareUrl.value = ''
    copied.value = false
    shareLoading.value = true
    try {
        const res = await fetch(`/dashboard/clients/${client.id}/share`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': props.csrfToken, 'Accept': 'application/json' },
        })
        if (!res.ok) throw new Error(`HTTP ${res.status}`)
        const data = await res.json()
        shareUrl.value = data.url
    } catch (e) {
        shareUrl.value = ''
        console.error('Erreur lors de la génération du lien de partage', e)
    } finally {
        shareLoading.value = false
    }
}

function copyShareUrl() {
    navigator.clipboard.writeText(shareUrl.value)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
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
                    v-if="projects.length > 0"
                    v-model="search"
                    type="text"
                    placeholder="Rechercher un projet ou un client..."
                    class="h-9 w-80 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                />

                <p v-if="filtered.length === 0 && search" class="text-sm text-muted-foreground">
                    Aucun projet ne correspond à votre recherche.
                </p>

                <div v-if="projects.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
                            <div class="flex shrink-0 items-center gap-1">
                                <button
                                    v-if="project.is_owner"
                                    type="button"
                                    class="flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                    @click="openShare(project)"
                                >
                                    <Share2 class="h-4 w-4" />
                                </button>
                                <DropdownMenu v-if="project.is_owner" :items="menuItems(project)" class="shrink-0">
                                    <button
                                        type="button"
                                        class="flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                    >
                                        <MoreVertical class="h-4 w-4" />
                                    </button>
                                </DropdownMenu>
                            </div>
                        </div>

                        <p v-if="project.description" class="mt-3 text-xs text-muted-foreground line-clamp-2">{{ project.description }}</p>

                        <div class="mt-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span v-if="project.daily_rate" class="text-xs font-medium text-foreground">{{ project.daily_rate }} €/jour</span>
                                <span v-else class="text-xs text-muted-foreground">TJM client</span>
                                <span
                                    v-if="!project.is_owner"
                                    class="inline-flex items-center gap-1 rounded-full bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-700"
                                >
                                    <Users class="h-3 w-3" />
                                    Partagé avec moi
                                </span>
                                <span
                                    v-else-if="project.is_shared"
                                    class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                                    title="Ce projet est partagé"
                                >
                                    <Users class="h-3 w-3" />
                                    Partagé
                                </span>
                            </div>
                            <span v-if="project.created_at" class="text-xs text-muted-foreground">{{ project.created_at }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="clients.length > 0" class="space-y-1">
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">Clients</p>
                    <div
                        v-for="client in clients"
                        :key="client.id"
                        class="flex items-center justify-between rounded-md px-3 py-2 hover:bg-accent"
                    >
                        <div class="flex items-center gap-2">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-medium" :class="avatarColor(client.name)">
                                {{ initials(client.name) }}
                            </div>
                            <span class="text-sm text-foreground">{{ client.name }}</span>
                            <span class="text-xs text-muted-foreground">{{ client.daily_rate }} €/j</span>
                            <span
                                v-if="!client.is_owner"
                                class="inline-flex items-center gap-1 rounded-full bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-700"
                            >
                                <Users class="h-3 w-3" />
                                Partagé avec moi
                            </span>
                            <span
                                v-else-if="client.is_shared"
                                class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                            >
                                <Users class="h-3 w-3" />
                                Partagé
                            </span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button
                                v-if="client.is_owner"
                                type="button"
                                class="flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                @click="openShareClient(client)"
                            >
                                <Share2 class="h-3.5 w-3.5" />
                            </button>
                            <div class="flex items-center gap-1">
                            <button
                                v-if="client.is_owner"
                                type="button"
                                class="flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                @click="editingClient = { ...client }"
                            >
                                <Pencil class="h-3.5 w-3.5" />
                            </button>
                            <button
                                v-if="client.is_owner"
                                    type="button"
                                    class="flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive"
                                    @click="deletingClient = client"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                        </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <Dialog :open="editingClient !== null" title="Modifier le client" @close="editingClient = null">
            <form v-if="editingClient" :action="`/dashboard/clients/${editingClient.id}`" method="POST" class="space-y-4">
                <input type="hidden" name="_token" :value="csrfToken" />
                <input type="hidden" name="_method" value="PUT" />
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Nom</label>
                    <input v-model="editingClient.name" name="name" type="text" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">TJM (€/jour)</label>
                    <input v-model="editingClient.daily_rate" name="daily_rate" type="number" min="0" step="0.01" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent" @click="editingClient = null">Annuler</button>
                    <button type="submit" class="h-9 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90">Enregistrer</button>
                </div>
            </form>
        </Dialog>

        <Dialog :open="deletingClient !== null" title="Supprimer le client" @close="deletingClient = null">
            <p class="text-sm text-muted-foreground">
                Supprimer <span class="font-medium text-foreground">{{ deletingClient?.name }}</span> ?
                Tous les projets et saisies associés seront définitivement supprimés.
            </p>
            <form v-if="deletingClient" :action="`/dashboard/clients/${deletingClient.id}`" method="POST" class="mt-4 flex justify-end gap-2">
                <input type="hidden" name="_token" :value="csrfToken" />
                <input type="hidden" name="_method" value="DELETE" />
                <button type="button" class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent" @click="deletingClient = null">Annuler</button>
                <button type="submit" class="h-9 rounded-md bg-destructive px-4 text-sm font-medium text-white transition-colors hover:bg-destructive/90">Supprimer</button>
            </form>
        </Dialog>

        <Dialog :open="createOpen || hasCreateErrors" title="Nouveau projet" @close="createOpen = false">
            <form :action="storeAction" method="POST" class="space-y-4">
                <input type="hidden" name="_token" :value="csrfToken" />

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Client</label>
                    <select
                        v-model="createForm.client_id"
                        name="client_id"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                        :class="{ 'border-destructive focus:ring-destructive': fieldError('client_id') }"
                    >
                        <option value="new">+ Nouveau client</option>
                        <optgroup v-if="clients.some(c => c.is_owner)" label="Clients existants">
                            <option v-for="client in clients.filter(c => c.is_owner)" :key="client.id" :value="client.id">{{ client.name }}</option>
                        </optgroup>
                    </select>
                    <p v-if="fieldError('client_id')" class="text-xs text-destructive">{{ fieldError('client_id') }}</p>
                </div>

                <template v-if="isNewClient">
                    <div class="rounded-md border border-border bg-muted/40 p-3 space-y-3">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground">Nom du client</label>
                            <input
                                v-model="createForm.client_name"
                                name="client_name"
                                type="text"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                :class="{ 'border-destructive focus:ring-destructive': fieldError('client_name') }"
                            />
                            <p v-if="fieldError('client_name')" class="text-xs text-destructive">{{ fieldError('client_name') }}</p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground">TJM du client (€/jour)</label>
                            <input
                                v-model="createForm.client_rate"
                                name="client_rate"
                                type="number"
                                min="0"
                                step="0.01"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                :class="{ 'border-destructive focus:ring-destructive': fieldError('client_rate') }"
                            />
                            <p v-if="fieldError('client_rate')" class="text-xs text-destructive">{{ fieldError('client_rate') }}</p>
                        </div>
                    </div>
                </template>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Nom du projet</label>
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
                    <label class="text-sm font-medium text-foreground">Description <span class="font-normal text-muted-foreground">— optionnel</span></label>
                    <input
                        v-model="createForm.description"
                        name="description"
                        type="text"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                    />
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">TJM du projet <span class="font-normal text-muted-foreground">— optionnel</span></label>
                    <input
                        v-model="createForm.daily_rate"
                        name="daily_rate"
                        type="number"
                        min="0"
                        step="0.01"
                        :placeholder="selectedCreateClientRate !== null ? `${selectedCreateClientRate} €/j (TJM client)` : 'Hérite du TJM client'"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                        :class="{ 'border-destructive focus:ring-destructive': fieldError('daily_rate') }"
                    />
                    <p v-if="fieldError('daily_rate')" class="text-xs text-destructive">{{ fieldError('daily_rate') }}</p>
                </div>

                <div class="flex flex-col sm:flex-row justify-end gap-2 pt-2">
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
                    <label class="text-sm font-medium text-foreground">Client</label>
                    <select v-model="editingProject.client_id" name="client_id" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring">
                        <option v-for="client in clients.filter(c => c.is_owner)" :key="client.id" :value="client.id">{{ client.name }}</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Nom du projet</label>
                    <input v-model="editingProject.name" name="name" type="text" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Description <span class="font-normal text-muted-foreground">— optionnel</span></label>
                    <input v-model="editingProject.description" name="description" type="text" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">TJM du projet <span class="font-normal text-muted-foreground">— optionnel</span></label>
                    <input
                        v-model="editingProject.daily_rate"
                        name="daily_rate"
                        type="number"
                        min="0"
                        step="0.01"
                        :placeholder="editClientRate !== null ? `${editClientRate} €/j (TJM client)` : 'Hérite du TJM client'"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                    />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent" @click="editingProject = null">Annuler</button>
                    <button type="submit" class="h-9 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90">Enregistrer</button>
                </div>
            </form>
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

        <Dialog :open="sharingClient !== null" title="Partager le client" @close="sharingClient = null">
            <p class="text-sm text-muted-foreground">
                Copiez ce lien et envoyez-le à la personne avec qui vous souhaitez partager
                <span class="font-medium text-foreground">{{ sharingClient?.name }}</span>.
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
            <form v-if="deletingProject" :action="`${baseAction}/${deletingProject.id}`" method="POST" class="mt-4 flex justify-end gap-2">
                <input type="hidden" name="_token" :value="csrfToken" />
                <input type="hidden" name="_method" value="DELETE" />
                <button type="button" class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent" @click="deletingProject = null">Annuler</button>
                <button type="submit" class="h-9 rounded-md bg-destructive px-4 text-sm font-medium text-white transition-colors hover:bg-destructive/90">Supprimer</button>
            </form>
        </Dialog>
    </div>
</template>
