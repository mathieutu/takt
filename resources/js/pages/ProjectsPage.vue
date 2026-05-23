<script setup lang="ts">
import { MoreVertical, Pencil, Plus, Share2, Trash2, Users } from 'lucide-vue-next'
import { computed, onMounted, ref, watch } from 'vue'
import { router, useForm, useHttp } from '@inertiajs/vue3'
import Dialog from '../components/ui/Dialog.vue'
import DropdownMenu from '../components/ui/DropdownMenu.vue'
import Select from '../components/ui/Select.vue'
import { store as storeProject, update as updateProject, destroy as destroyProject } from '@/wayfinder/routes/projects'
import { update as updateClient, destroy as destroyClient } from '@/wayfinder/routes/clients'
import { revoke as shareRoute } from '@/wayfinder/routes/projects/share'

type Client = {
    id: number
    name: string
    daily_rate: number
    is_owner: boolean
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
    projects?: Project[]
    clients?: Client[]
    open?: boolean
}>(), {
    projects: () => [],
    clients: () => [],
    open: false,
})

const search = ref('')
const createOpen = ref(false)
const editingProject = ref<Project | null>(null)
const deletingProject = ref<Project | null>(null)
const deletingClient = ref<Client | null>(null)
const sharingProject = ref<Project | null>(null)
const shareUrl = ref('')
const copied = ref(false)
const shareLoading = ref(false)
const editingClient = ref<Client | null>(null)

const defaultClientId = props.clients.length > 0 ? String(props.clients[0]?.id) : 'new'

const createForm = useForm({
    name: '',
    description: '',
    daily_rate: '',
    client_id: defaultClientId,
    client_name: '',
    client_rate: '',
})

const isNewClient = computed(() => createForm.client_id === 'new')

watch(() => createForm.client_id, (clientId) => {
    if (clientId === 'new') {
        createForm.daily_rate = ''
    } else {
        const client = props.clients.find(c => c.id === Number(clientId))
        if (client) createForm.daily_rate = String(client.daily_rate)
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
    const client = props.clients.find(c => c.id === Number(createForm.client_id))
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

function menuItems(project: Project) {
    return [
        { label: 'Modifier', action: () => openEdit(project) },
        { label: 'Supprimer', action: () => { deletingProject.value = project }, variant: 'destructive' as const },
    ]
}

function submitCreateForm() {
    createForm.post(storeProject().url, {
        onSuccess: () => {
            createOpen.value = false
            createForm.reset()
        },
    })
}

function submitUpdateProject() {
    if (!editingProject.value) return
    router.put(updateProject(editingProject.value.id).url, {
        name: editingProject.value.name,
        description: editingProject.value.description,
        daily_rate: editingProject.value.daily_rate,
        client_id: editingProject.value.client_id,
    }, {
        preserveScroll: true,
        onSuccess: () => { editingProject.value = null },
    })
}

function submitUpdateClient() {
    if (!editingClient.value) return
    router.put(updateClient(editingClient.value.id).url, {
        name: editingClient.value.name,
        daily_rate: editingClient.value.daily_rate,
    }, {
        preserveScroll: true,
        onSuccess: () => { editingClient.value = null },
    })
}

function submitDeleteProject() {
    if (!deletingProject.value) return
    router.delete(destroyProject(deletingProject.value.id).url, {
        preserveScroll: true,
        onSuccess: () => { deletingProject.value = null },
    })
}

function submitDeleteClient() {
    if (!deletingClient.value) return
    router.delete(destroyClient(deletingClient.value.id).url, {
        preserveScroll: true,
        onSuccess: () => { deletingClient.value = null },
    })
}

const http = useHttp()

function openShare(project: Project) {
    sharingProject.value = project
    shareUrl.value = ''
    copied.value = false
    shareLoading.value = true
    http.post(shareRoute(project.id).url, {
        onSuccess: (data: any) => {
            shareUrl.value = data.url
        },
        onError: () => {
            shareUrl.value = ''
            console.error('Erreur lors de la génération du lien de partage')
        },
        onFinish: () => {
            shareLoading.value = false
        },
    })
}

function copyShareUrl() {
    navigator.clipboard.writeText(shareUrl.value)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
}

function revokeShare() {
    if (!sharingProject.value) return
    router.delete(shareRoute(sharingProject.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            sharingProject.value = null
            shareUrl.value = ''
        },
    })
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
                        </div>
                        <div class="flex items-center gap-1">
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
            <form v-if="editingClient" class="space-y-4" @submit.prevent="submitUpdateClient">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Nom<span class="text-destructive ml-0.5">*</span></label>
                    <input v-model="editingClient.name" name="name" type="text" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">TJM (€/jour)<span class="text-destructive ml-0.5">*</span></label>
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
            <div v-if="deletingClient" class="mt-4 flex justify-end gap-2">
                <button type="button" class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent" @click="deletingClient = null">Annuler</button>
                <button type="button" class="h-9 rounded-md bg-destructive px-4 text-sm font-medium text-white transition-colors hover:bg-destructive/90" @click="submitDeleteClient">Supprimer</button>
            </div>
        </Dialog>

        <Dialog :open="createOpen || createForm.hasErrors" title="Nouveau projet" @close="createOpen = false">
            <form class="space-y-4" @submit.prevent="submitCreateForm">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Client<span class="text-destructive ml-0.5">*</span></label>
                    <Select
                        v-model="createForm.client_id"
                        name="client_id"
                        :options="[
                            { value: 'new', label: '+ Nouveau client' },
                            ...clients.filter(c => c.is_owner).map(c => ({ value: c.id, label: c.name, group: 'Clients existants' })),
                        ]"
                        :error="!!createForm.errors.client_id"
                    />
                    <p v-if="createForm.errors.client_id" class="text-xs text-destructive">{{ createForm.errors.client_id }}</p>
                </div>

                <template v-if="isNewClient">
                    <div class="rounded-md border border-border bg-muted/40 p-3 space-y-3">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground">Nom du client<span class="text-destructive ml-0.5">*</span></label>
                            <input
                                v-model="createForm.client_name"
                                name="client_name"
                                type="text"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                :class="{ 'border-destructive focus:ring-destructive': createForm.errors.client_name }"
                            />
                            <p v-if="createForm.errors.client_name" class="text-xs text-destructive">{{ createForm.errors.client_name }}</p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground">TJM du client (€/jour)<span class="text-destructive ml-0.5">*</span></label>
                            <input
                                v-model="createForm.client_rate"
                                name="client_rate"
                                type="number"
                                min="0"
                                step="0.01"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                :class="{ 'border-destructive focus:ring-destructive': createForm.errors.client_rate }"
                            />
                            <p v-if="createForm.errors.client_rate" class="text-xs text-destructive">{{ createForm.errors.client_rate }}</p>
                        </div>
                    </div>
                </template>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Nom du projet<span class="text-destructive ml-0.5">*</span></label>
                    <input
                        v-model="createForm.name"
                        name="name"
                        type="text"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                        :class="{ 'border-destructive focus:ring-destructive': createForm.errors.name }"
                    />
                    <p v-if="createForm.errors.name" class="text-xs text-destructive">{{ createForm.errors.name }}</p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Description</label>
                    <input
                        v-model="createForm.description"
                        name="description"
                        type="text"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                    />
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">TJM du projet</label>
                    <input
                        v-model="createForm.daily_rate"
                        name="daily_rate"
                        type="number"
                        min="0"
                        step="0.01"
                        :placeholder="selectedCreateClientRate !== null ? `${selectedCreateClientRate} €/j (TJM client)` : 'Hérite du TJM client'"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                        :class="{ 'border-destructive focus:ring-destructive': createForm.errors.daily_rate }"
                    />
                    <p v-if="createForm.errors.daily_rate" class="text-xs text-destructive">{{ createForm.errors.daily_rate }}</p>
                </div>

                <div class="flex flex-col sm:flex-row justify-end gap-2 pt-2">
                    <button type="button" class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent" @click="createOpen = false">Annuler</button>
                    <button type="submit" :disabled="createForm.processing" class="h-9 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50">Créer</button>
                </div>
            </form>
        </Dialog>

        <Dialog :open="editingProject !== null" title="Modifier le projet" @close="editingProject = null">
            <form v-if="editingProject" class="space-y-4" @submit.prevent="submitUpdateProject">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Client<span class="text-destructive ml-0.5">*</span></label>
                    <Select
                        v-model="editingProject.client_id"
                        name="client_id"
                        :options="clients.filter(c => c.is_owner).map(c => ({ value: c.id, label: c.name }))"
                    />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Nom du projet<span class="text-destructive ml-0.5">*</span></label>
                    <input v-model="editingProject.name" name="name" type="text" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Description</label>
                    <input v-model="editingProject.description" name="description" type="text" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">TJM du projet</label>
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
            <div v-if="shareUrl" class="mt-3 flex justify-end border-t border-border pt-3">
                <button
                    type="button"
                    class="text-xs text-muted-foreground underline-offset-2 hover:text-destructive hover:underline transition-colors"
                    @click="revokeShare"
                >
                    Désactiver ce lien de partage
                </button>
            </div>
        </Dialog>

        <Dialog :open="deletingProject !== null" title="Supprimer le projet" @close="deletingProject = null">
            <p class="text-sm text-muted-foreground">
                Supprimer <span class="font-medium text-foreground">{{ deletingProject?.name }}</span> ? Cette action est irréversible.
            </p>
            <div v-if="deletingProject" class="mt-4 flex justify-end gap-2">
                <button type="button" class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent" @click="deletingProject = null">Annuler</button>
                <button type="button" class="h-9 rounded-md bg-destructive px-4 text-sm font-medium text-white transition-colors hover:bg-destructive/90" @click="submitDeleteProject">Supprimer</button>
            </div>
        </Dialog>
    </div>
</template>
