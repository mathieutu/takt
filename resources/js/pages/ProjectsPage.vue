<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { router, useForm, useHttp } from '@inertiajs/vue3'
import { store as storeProject, update as updateProject, destroy as destroyProject } from '@/wayfinder/routes/projects'
import { update as updateClient, destroy as destroyClient } from '@/wayfinder/routes/clients'
import { revoke as shareRoute } from '@/wayfinder/routes/projects/share'

type Client = {
    id: number
    name: string
    daily_rate: number
}

type Project = {
    id: number
    name: string
    description: string
    daily_rate: number | null
    client_id: number
    client_name: string
    created_at: string
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

const search          = ref('')
const createOpen      = ref(false)
const editProjectOpen = ref(false)
const deleteProjectOpen = ref(false)
const editClientOpen  = ref(false)
const deleteClientOpen = ref(false)
const shareProjectOpen = ref(false)

const editingProject  = ref<Project | null>(null)
const deletingProject = ref<Project | null>(null)
const deletingClient  = ref<Client | null>(null)
const sharingProject  = ref<Project | null>(null)
const shareUrl        = ref('')
const copied          = ref(false)
const shareLoading    = ref(false)
const editingClient   = ref<Client | null>(null)

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

const createModalOpen = computed({
    get: () => createOpen.value || createForm.hasErrors,
    set: (val) => { createOpen.value = val },
})

const clientSelectItems = computed(() => [
    { value: 'new', label: '+ Nouveau client' },
    { type: 'separator' as const },
    { type: 'label' as const, label: 'Clients existants' },
    ...props.clients.map(c => ({ value: String(c.id), label: c.name })),
])

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
    editProjectOpen.value = true
}

function menuItems(project: Project) {
    return [[
        { label: 'Modifier', icon: 'i-lucide-pencil', onSelect: () => openEdit(project) },
    ], [
        { label: 'Supprimer', icon: 'i-lucide-trash-2', color: 'error' as const, onSelect: () => { deletingProject.value = project; deleteProjectOpen.value = true } },
    ]]
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
        onSuccess: () => { editProjectOpen.value = false },
    })
}

function submitUpdateClient() {
    if (!editingClient.value) return
    router.put(updateClient(editingClient.value.id).url, {
        name: editingClient.value.name,
        daily_rate: editingClient.value.daily_rate,
    }, {
        preserveScroll: true,
        onSuccess: () => { editClientOpen.value = false },
    })
}

function submitDeleteProject() {
    if (!deletingProject.value) return
    router.delete(destroyProject(deletingProject.value.id).url, {
        preserveScroll: true,
        onSuccess: () => { deleteProjectOpen.value = false },
    })
}

function submitDeleteClient() {
    if (!deletingClient.value) return
    router.delete(destroyClient(deletingClient.value.id).url, {
        preserveScroll: true,
        onSuccess: () => { deleteClientOpen.value = false },
    })
}

const http = useHttp()

function openShare(project: Project) {
    sharingProject.value = project
    shareUrl.value = ''
    copied.value = false
    shareLoading.value = true
    shareProjectOpen.value = true
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
            shareProjectOpen.value = false
            sharingProject.value = null
            shareUrl.value = ''
        },
    })
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-default">
        <main class="flex-1 px-6 py-8">
            <div class="mx-auto max-w-5xl space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-lg font-semibold">Projets</h1>
                        <p class="text-sm text-muted">{{ projects.length }} projet{{ projects.length !== 1 ? 's' : '' }} au total</p>
                    </div>
                    <UButton
                        label="Nouveau projet"
                        icon="i-lucide-plus"
                        @click="createOpen = true"
                    />
                </div>

                <UInput
                    v-if="projects.length > 0"
                    v-model="search"
                    type="text"
                    placeholder="Rechercher un projet ou un client..."
                    icon="i-lucide-search"
                    class="w-80"
                />

                <p v-if="filtered.length === 0 && search" class="text-sm text-muted">
                    Aucun projet ne correspond à votre recherche.
                </p>

                <div v-if="projects.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="project in filtered"
                        :key="project.id"
                        class="rounded-lg border border-default bg-elevated p-5"
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
                                    <p class="truncate text-sm font-semibold">{{ project.name }}</p>
                                    <p class="truncate text-xs text-muted">{{ project.client_name }}</p>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <UButton
                                    icon="i-lucide-share-2"
                                    color="neutral"
                                    variant="ghost"
                                    size="xs"
                                    @click="openShare(project)"
                                />
                                <UDropdownMenu :items="menuItems(project)" class="shrink-0">
                                    <UButton
                                        icon="i-lucide-more-vertical"
                                        color="neutral"
                                        variant="ghost"
                                        size="xs"
                                    />
                                </UDropdownMenu>
                            </div>
                        </div>

                        <p v-if="project.description" class="mt-3 text-xs text-muted line-clamp-2">{{ project.description }}</p>

                        <div class="mt-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span v-if="project.daily_rate" class="text-xs font-medium">{{ project.daily_rate }} €/jour</span>
                                <span v-else class="text-xs text-muted">TJM client</span>
                                <span
                                    v-if="project.is_shared"
                                    class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs text-muted"
                                    title="Ce projet est partagé"
                                >
                                    <UIcon name="i-lucide-users" class="h-3 w-3" />
                                    Partagé
                                </span>
                            </div>
                            <span v-if="project.created_at" class="text-xs text-muted">{{ project.created_at }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="clients.length > 0" class="space-y-1">
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted">Clients</p>
                    <div
                        v-for="client in clients"
                        :key="client.id"
                        class="flex items-center justify-between rounded-md px-3 py-2 hover:bg-elevated"
                    >
                        <div class="flex items-center gap-2">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-medium" :class="avatarColor(client.name)">
                                {{ initials(client.name) }}
                            </div>
                            <span class="text-sm">{{ client.name }}</span>
                            <span class="text-xs text-muted">{{ client.daily_rate }} €/j</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <UButton
                                icon="i-lucide-pencil"
                                color="neutral"
                                variant="ghost"
                                size="xs"
                                @click="editingClient = { ...client }; editClientOpen = true"
                            />
                            <UButton
                                icon="i-lucide-trash-2"
                                color="error"
                                variant="ghost"
                                size="xs"
                                @click="deletingClient = client; deleteClientOpen = true"
                            />
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <UModal v-model:open="editClientOpen" title="Modifier le client">
            <template #body>
                <form v-if="editingClient" class="space-y-4" @submit.prevent="submitUpdateClient">
                    <UFormField label="Nom" required>
                        <UInput v-model="editingClient.name" type="text" class="w-full" />
                    </UFormField>
                    <UFormField label="TJM (€/jour)" required>
                        <UInput v-model="editingClient.daily_rate" type="number" min="0" step="0.01" class="w-full" />
                    </UFormField>
                </form>
            </template>
            <template #footer="{ close }">
                <div class="flex justify-end gap-2">
                    <UButton label="Annuler" color="neutral" variant="outline" @click="close" />
                    <UButton label="Enregistrer" @click="submitUpdateClient" />
                </div>
            </template>
        </UModal>

        <UModal v-model:open="deleteClientOpen" title="Supprimer le client">
            <template #body>
                <p class="text-sm text-muted">
                    Supprimer <span class="font-medium text-default">{{ deletingClient?.name }}</span> ?
                    Tous les projets et saisies associés seront définitivement supprimés.
                </p>
            </template>
            <template #footer="{ close }">
                <div class="flex justify-end gap-2">
                    <UButton label="Annuler" color="neutral" variant="outline" @click="close" />
                    <UButton label="Supprimer" color="error" @click="submitDeleteClient" />
                </div>
            </template>
        </UModal>

        <UModal v-model:open="createModalOpen" title="Nouveau projet">
            <template #body>
                <form class="space-y-4" @submit.prevent="submitCreateForm">
                    <UFormField label="Client" required :error="createForm.errors.client_id">
                        <USelect
                            v-model="createForm.client_id"
                            :items="clientSelectItems"
                            :color="createForm.errors.client_id ? 'error' : undefined"
                            class="w-full"
                        />
                    </UFormField>

                    <template v-if="isNewClient">
                        <div class="rounded-md border border-default bg-muted/40 p-3 space-y-3">
                            <UFormField label="Nom du client" required :error="createForm.errors.client_name">
                                <UInput
                                    v-model="createForm.client_name"
                                    type="text"
                                    :color="createForm.errors.client_name ? 'error' : undefined"
                                    class="w-full"
                                />
                            </UFormField>
                            <UFormField label="TJM du client (€/jour)" required :error="createForm.errors.client_rate">
                                <UInput
                                    v-model="createForm.client_rate"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :color="createForm.errors.client_rate ? 'error' : undefined"
                                    class="w-full"
                                />
                            </UFormField>
                        </div>
                    </template>

                    <UFormField label="Nom du projet" required :error="createForm.errors.name">
                        <UInput
                            v-model="createForm.name"
                            type="text"
                            :color="createForm.errors.name ? 'error' : undefined"
                            class="w-full"
                        />
                    </UFormField>

                    <UFormField label="Description">
                        <UInput v-model="createForm.description" type="text" class="w-full" />
                    </UFormField>

                    <UFormField label="TJM du projet" :error="createForm.errors.daily_rate">
                        <UInput
                            v-model="createForm.daily_rate"
                            type="number"
                            min="0"
                            step="0.01"
                            :placeholder="selectedCreateClientRate !== null ? `${selectedCreateClientRate} €/j (TJM client)` : 'Hérite du TJM client'"
                            :color="createForm.errors.daily_rate ? 'error' : undefined"
                            class="w-full"
                        />
                    </UFormField>
                </form>
            </template>
            <template #footer="{ close }">
                <div class="flex flex-col sm:flex-row justify-end gap-2">
                    <UButton label="Annuler" color="neutral" variant="outline" @click="close" />
                    <UButton label="Créer" :loading="createForm.processing" @click="submitCreateForm" />
                </div>
            </template>
        </UModal>

        <UModal v-model:open="editProjectOpen" title="Modifier le projet">
            <template #body>
                <form v-if="editingProject" class="space-y-4" @submit.prevent="submitUpdateProject">
                    <UFormField label="Client" required>
                        <USelect
                            v-model="editingProject.client_id"
                            :items="clients.map(c => ({ value: c.id, label: c.name }))"
                            class="w-full"
                        />
                    </UFormField>
                    <UFormField label="Nom du projet" required>
                        <UInput v-model="editingProject.name" type="text" class="w-full" />
                    </UFormField>
                    <UFormField label="Description">
                        <UInput v-model="editingProject.description" type="text" class="w-full" />
                    </UFormField>
                    <UFormField label="TJM du projet">
                        <UInput
                            v-model="editingProject.daily_rate"
                            type="number"
                            min="0"
                            step="0.01"
                            :placeholder="editClientRate !== null ? `${editClientRate} €/j (TJM client)` : 'Hérite du TJM client'"
                            class="w-full"
                        />
                    </UFormField>
                </form>
            </template>
            <template #footer="{ close }">
                <div class="flex justify-end gap-2">
                    <UButton label="Annuler" color="neutral" variant="outline" @click="close" />
                    <UButton label="Enregistrer" @click="submitUpdateProject" />
                </div>
            </template>
        </UModal>

        <UModal v-model:open="shareProjectOpen" title="Partager le projet">
            <template #body>
                <div class="space-y-3">
                    <p class="text-sm text-muted">
                        Copiez ce lien et envoyez-le à la personne avec qui vous souhaitez partager
                        <span class="font-medium text-default">{{ sharingProject?.name }}</span>.
                    </p>
                    <div class="flex gap-2">
                        <UInput
                            :model-value="shareLoading ? 'Chargement…' : shareUrl"
                            readonly
                            class="min-w-0 flex-1"
                        />
                        <UButton
                            :label="copied ? 'Copié !' : 'Copier'"
                            :disabled="shareLoading || !shareUrl"
                            @click="copyShareUrl"
                        />
                    </div>
                    <div v-if="shareUrl" class="flex justify-end border-t border-default pt-3">
                        <button
                            type="button"
                            class="text-xs text-muted underline-offset-2 hover:text-error hover:underline transition-colors"
                            @click="revokeShare"
                        >
                            Désactiver ce lien de partage
                        </button>
                    </div>
                </div>
            </template>
            <template #footer="{ close }">
                <div class="flex justify-end gap-2">
                    <UButton label="Fermer" color="neutral" variant="outline" @click="close" />
                </div>
            </template>
        </UModal>

        <UModal v-model:open="deleteProjectOpen" title="Supprimer le projet">
            <template #body>
                <p class="text-sm text-muted">
                    Supprimer <span class="font-medium text-default">{{ deletingProject?.name }}</span> ? Cette action est irréversible.
                </p>
            </template>
            <template #footer="{ close }">
                <div class="flex justify-end gap-2">
                    <UButton label="Annuler" color="neutral" variant="outline" @click="close" />
                    <UButton label="Supprimer" color="error" @click="submitDeleteProject" />
                </div>
            </template>
        </UModal>
    </div>
</template>
