<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Plus, MoreVertical } from 'lucide-vue-next'
import Dialog from '../components/ui/Dialog.vue'
import DropdownMenu from '../components/ui/DropdownMenu.vue'
import { router, useForm } from '@inertiajs/vue3'
import { store as storeClient, update as updateClient, destroy as destroyClient } from '@/routes/clients'

type Client = {
    id: number
    name: string
    daily_rate: number
    created_at: string
}

const props = withDefaults(defineProps<{
    clients?: Client[]
}>(), {
    clients: () => [],
})

const search = ref('')
const createOpen = ref(false)
const editingClient = ref<Client | null>(null)
const deletingClient = ref<Client | null>(null)

const form = useForm({
    name: '',
    daily_rate: '',
})

onMounted(() => {
    if (props.clients.length === 0) createOpen.value = true
})

const filtered = computed(() =>
    props.clients.filter(c =>
        c.name.toLowerCase().includes(search.value.toLowerCase())
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

function openEdit(client: Client) {
    editingClient.value = { ...client }
}

function submitCreate() {
    form.post(storeClient().url, {
        preserveScroll: true,
        onSuccess: () => {
            createOpen.value = false
            form.reset()
        },
    })
}

function submitEdit() {
    if (!editingClient.value) return
    router.put(updateClient(editingClient.value.id).url, {
        name: editingClient.value.name,
        daily_rate: editingClient.value.daily_rate,
    }, {
        preserveScroll: true,
        onSuccess: () => { editingClient.value = null },
    })
}

function submitDelete() {
    if (!deletingClient.value) return
    router.delete(destroyClient(deletingClient.value.id).url, {
        preserveScroll: true,
        onSuccess: () => { deletingClient.value = null },
    })
}

const hasCreateErrors = computed(() => !!form.errors.name || !!form.errors.daily_rate)

function menuItems(client: Client) {
    return [
        { label: 'Modifier', action: () => openEdit(client) },
        { label: 'Supprimer', action: () => { deletingClient.value = client }, variant: 'destructive' as const },
    ]
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background">
        <main class="flex-1 px-6 py-8">
            <div class="mx-auto max-w-5xl space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-lg font-semibold text-foreground">Clients</h1>
                        <p class="text-sm text-muted-foreground">{{ clients.length }} client{{ clients.length !== 1 ? 's' : '' }} au total</p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-9 items-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                        @click="createOpen = true"
                    >
                        <Plus class="h-4 w-4" />
                        Nouveau client
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Rechercher un client..."
                        class="h-9 w-72 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                    />
                </div>

                <p v-if="filtered.length === 0 && search" class="text-sm text-muted-foreground">
                    Aucun client ne correspond à votre recherche.
                </p>

                <p v-else-if="clients.length === 0" class="text-sm text-muted-foreground">
                    Aucun client pour l'instant.
                </p>

                <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="client in filtered"
                        :key="client.id"
                        class="rounded-lg border border-border bg-card p-5"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold"
                                    :class="avatarColor(client.name)"
                                >
                                    {{ initials(client.name) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-foreground">{{ client.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ client.daily_rate }} €/jour</p>
                                </div>
                            </div>
                            <DropdownMenu :items="menuItems(client)" class="shrink-0">
                                <button
                                    type="button"
                                    class="flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                >
                                    <MoreVertical class="h-4 w-4" />
                                </button>
                            </DropdownMenu>
                        </div>

                        <p v-if="client.created_at" class="mt-4 text-xs text-muted-foreground">
                            Créé le {{ client.created_at }}
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <Dialog :open="createOpen || hasCreateErrors" title="Nouveau client" @close="createOpen = false">
            <div class="space-y-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Nom<span class="text-destructive ml-0.5">*</span></label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                        :class="{ 'border-destructive focus:ring-destructive': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">TJM (€/jour)<span class="text-destructive ml-0.5">*</span></label>
                    <input
                        v-model="form.daily_rate"
                        type="number"
                        min="0"
                        step="0.01"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                        :class="{ 'border-destructive focus:ring-destructive': form.errors.daily_rate }"
                    />
                    <p v-if="form.errors.daily_rate" class="text-xs text-destructive">{{ form.errors.daily_rate }}</p>
                </div>
                <div class="flex flex-col sm:flex-row justify-end gap-2 pt-2">
                    <button type="button" class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent" @click="createOpen = false">Annuler</button>
                    <button type="button" :disabled="form.processing" class="h-9 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90" @click="submitCreate">Créer</button>
                </div>
            </div>
        </Dialog>

        <Dialog :open="editingClient !== null" title="Modifier le client" @close="editingClient = null">
            <div v-if="editingClient" class="space-y-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Nom<span class="text-destructive ml-0.5">*</span></label>
                    <input v-model="editingClient.name" type="text" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">TJM (€/jour)<span class="text-destructive ml-0.5">*</span></label>
                    <input v-model="editingClient.daily_rate" type="number" min="0" step="0.01" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent" @click="editingClient = null">Annuler</button>
                    <button type="button" class="h-9 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90" @click="submitEdit">Enregistrer</button>
                </div>
            </div>
        </Dialog>

        <Dialog :open="deletingClient !== null" title="Supprimer le client" @close="deletingClient = null">
            <p class="text-sm text-muted-foreground">
                Supprimer <span class="font-medium text-foreground">{{ deletingClient?.name }}</span> ? Cette action est irréversible.
            </p>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" class="h-9 rounded-md border border-border px-4 text-sm text-foreground transition-colors hover:bg-accent" @click="deletingClient = null">Annuler</button>
                <button type="button" class="h-9 rounded-md bg-destructive px-4 text-sm font-medium text-white transition-colors hover:bg-destructive/90" @click="submitDelete">Supprimer</button>
            </div>
        </Dialog>
    </div>
</template>
