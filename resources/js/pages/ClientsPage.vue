<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { store as storeClient, update as updateClient, destroy as destroyClient } from '@/wayfinder/routes/clients'

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
const editOpen = ref(false)
const editingClient = ref<Client | null>(null)
const deleteOpen = ref(false)
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
    editOpen.value = true
}

function openDelete(client: Client) {
    deletingClient.value = client
    deleteOpen.value = true
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
        onSuccess: () => { editOpen.value = false },
    })
}

function submitDelete() {
    if (!deletingClient.value) return
    router.delete(destroyClient(deletingClient.value.id).url, {
        preserveScroll: true,
        onSuccess: () => { deleteOpen.value = false },
    })
}

function menuItems(client: Client) {
    return [[
        { label: 'Modifier', icon: 'i-lucide-pencil', onSelect: () => openEdit(client) },
    ], [
        { label: 'Supprimer', icon: 'i-lucide-trash-2', color: 'error' as const, onSelect: () => openDelete(client) },
    ]]
}
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <main class="flex-1 px-6 py-8">
            <div class="mx-auto max-w-5xl space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-lg font-semibold">Clients</h1>
                        <p class="text-sm text-muted">{{ clients.length }} client{{ clients.length !== 1 ? 's' : '' }} au total</p>
                    </div>
                    <UButton
                        label="Nouveau client"
                        icon="i-lucide-plus"
                        @click="createOpen = true"
                    />
                </div>

                <UInput
                    v-model="search"
                    type="text"
                    placeholder="Rechercher un client..."
                    icon="i-lucide-search"
                    class="w-72"
                />

                <p v-if="filtered.length === 0 && search" class="text-sm text-muted">
                    Aucun client ne correspond à votre recherche.
                </p>

                <p v-else-if="clients.length === 0" class="text-sm text-muted">
                    Aucun client pour l'instant.
                </p>

                <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="client in filtered"
                        :key="client.id"
                        class="rounded-lg border border-default bg-elevated p-5"
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
                                    <p class="truncate text-sm font-semibold">{{ client.name }}</p>
                                    <p class="text-xs text-muted">{{ client.daily_rate }} €/jour</p>
                                </div>
                            </div>
                            <UDropdownMenu :items="menuItems(client)" class="shrink-0">
                                <UButton
                                    icon="i-lucide-more-vertical"
                                    color="neutral"
                                    variant="ghost"
                                    size="xs"
                                />
                            </UDropdownMenu>
                        </div>

                        <p v-if="client.created_at" class="mt-4 text-xs text-muted">
                            Créé le {{ client.created_at }}
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <UModal v-model:open="createOpen" title="Nouveau client">
            <template #body>
                <div class="space-y-4">
                    <UFormField label="Nom" required :error="form.errors.name">
                        <UInput
                            v-model="form.name"
                            type="text"
                            :color="form.errors.name ? 'error' : undefined"
                            class="w-full"
                        />
                    </UFormField>
                    <UFormField label="TJM (€/jour)" required :error="form.errors.daily_rate">
                        <UInput
                            v-model="form.daily_rate"
                            type="number"
                            min="0"
                            step="0.01"
                            :color="form.errors.daily_rate ? 'error' : undefined"
                            class="w-full"
                        />
                    </UFormField>
                </div>
            </template>
            <template #footer="{ close }">
                <div class="flex justify-end gap-2">
                    <UButton label="Annuler" color="neutral" variant="outline" @click="close" />
                    <UButton label="Créer" :loading="form.processing" @click="submitCreate" />
                </div>
            </template>
        </UModal>

        <UModal v-model:open="editOpen" title="Modifier le client">
            <template #body>
                <div v-if="editingClient" class="space-y-4">
                    <UFormField label="Nom" required>
                        <UInput v-model="editingClient.name" type="text" class="w-full" />
                    </UFormField>
                    <UFormField label="TJM (€/jour)" required>
                        <UInput v-model="editingClient.daily_rate" type="number" min="0" step="0.01" class="w-full" />
                    </UFormField>
                </div>
            </template>
            <template #footer="{ close }">
                <div class="flex justify-end gap-2">
                    <UButton label="Annuler" color="neutral" variant="outline" @click="close" />
                    <UButton label="Enregistrer" @click="submitEdit" />
                </div>
            </template>
        </UModal>

        <UModal v-model:open="deleteOpen" title="Supprimer le client">
            <template #body>
                <p class="text-sm text-muted">
                    Supprimer <span class="font-medium text-default">{{ deletingClient?.name }}</span> ? Cette action est irréversible.
                </p>
            </template>
            <template #footer="{ close }">
                <div class="flex justify-end gap-2">
                    <UButton label="Annuler" color="neutral" variant="outline" @click="close" />
                    <UButton label="Supprimer" color="error" @click="submitDelete" />
                </div>
            </template>
        </UModal>
    </div>
</template>
