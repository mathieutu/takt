<script setup lang="ts">
import { ref, computed } from 'vue'
import { Plus, Search, MoreVertical, Mail, Phone, MapPin } from 'lucide-vue-next'
import { useToast } from '../composables/useToast'
import type { Client, Project } from '../types'
import Card from '../components/ui/Card.vue'
import Badge from '../components/ui/Badge.vue'
import Dialog from '../components/ui/Dialog.vue'
import AlertDialog from '../components/ui/AlertDialog.vue'
import Switch from '../components/ui/Switch.vue'
import ToastContainer from '../components/ui/ToastContainer.vue'

const props = defineProps<{
    clients: Client[]
    projects: Project[]
}>()

const emit = defineEmits<{
    'client-create': [client: Omit<Client, 'id' | 'createdAt'>]
    'client-update': [id: string, data: Partial<Client>]
    'client-delete': [id: string]
}>()

const { toast } = useToast()

const localClients = ref<Client[]>([...props.clients])

const search = ref('')
const showInactive = ref(false)

const dialogOpen = ref(false)
const editingClient = ref<Client | null>(null)
const deleteDialogOpen = ref(false)
const clientToDelete = ref<Client | null>(null)

const form = ref({
    name: '',
    contactName: '',
    email: '',
    phone: '',
    address: '',
    color: '#6366f1',
    active: true,
})

const COLOR_PRESETS = [
    '#6366f1', '#0ea5e9', '#10b981', '#f59e0b',
    '#ef4444', '#8b5cf6', '#f97316', '#14b8a6',
    '#ec4899', '#84cc16',
]

const filteredClients = computed(() =>
    localClients.value.filter(c => {
        const matchSearch = !search.value
            || c.name.toLowerCase().includes(search.value.toLowerCase())
            || c.contactName.toLowerCase().includes(search.value.toLowerCase())
            || c.email.toLowerCase().includes(search.value.toLowerCase())
        const matchActive = showInactive.value ? true : c.active
        return matchSearch && matchActive
    })
)

function openCreate() {
    editingClient.value = null
    form.value = { name: '', contactName: '', email: '', phone: '', address: '', color: '#6366f1', active: true }
    dialogOpen.value = true
}

function openEdit(client: Client) {
    editingClient.value = client
    form.value = {
        name: client.name,
        contactName: client.contactName,
        email: client.email,
        phone: client.phone ?? '',
        address: client.address ?? '',
        color: client.color,
        active: client.active,
    }
    dialogOpen.value = true
}

function saveClient() {
    if (!form.value.name.trim() || !form.value.email.trim()) {
        toast('Nom et email sont requis', 'error')
        return
    }
    const data = {
        name: form.value.name,
        contactName: form.value.contactName,
        email: form.value.email,
        phone: form.value.phone || undefined,
        address: form.value.address || undefined,
        color: form.value.color,
        active: form.value.active,
    }
    if (editingClient.value) {
        const idx = localClients.value.findIndex(c => c.id === editingClient.value!.id)
        if (idx !== -1) localClients.value[idx] = { ...localClients.value[idx], ...data }
        emit('client-update', editingClient.value.id, data)
        toast('Client modifié avec succès')
    } else {
        const newClient: Client = {
            id: `c${Date.now()}`,
            createdAt: new Date().toISOString().slice(0, 10),
            ...data,
        }
        localClients.value.push(newClient)
        emit('client-create', data)
        toast('Client créé avec succès')
    }
    dialogOpen.value = false
}

function confirmDelete(client: Client) {
    clientToDelete.value = client
    deleteDialogOpen.value = true
}

function doDelete() {
    if (!clientToDelete.value) return
    localClients.value = localClients.value.filter(c => c.id !== clientToDelete.value!.id)
    emit('client-delete', clientToDelete.value.id)
    toast(`Client "${clientToDelete.value.name}" supprimé`, 'info')
    deleteDialogOpen.value = false
    clientToDelete.value = null
}

function toggleActive(client: Client) {
    const idx = localClients.value.findIndex(c => c.id === client.id)
    if (idx !== -1) localClients.value[idx] = { ...localClients.value[idx], active: !client.active }
    emit('client-update', client.id, { active: !client.active })
    toast(client.active ? 'Client désactivé' : 'Client réactivé')
}

function getProjectCount(clientId: string) {
    return props.projects.filter(p => p.clientId === clientId).length
}

function formatDate(date: string) {
    return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-foreground">Clients</h1>
                <p class="text-sm text-muted-foreground">{{ localClients.length }} clients au total</p>
            </div>
            <button
                type="button"
                class="inline-flex items-center gap-2 h-9 px-4 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:bg-primary/90 transition-colors"
                @click="openCreate"
            >
                <Plus class="h-4 w-4" />
                Nouveau client
            </button>
        </div>

        <div class="flex items-center gap-4">
            <div class="relative flex-1 max-w-sm">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Rechercher un client..."
                    class="h-9 w-full rounded-md border border-input bg-background pl-9 pr-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                />
            </div>
            <Switch v-model="showInactive" label="Afficher inactifs" />
        </div>

        <div v-if="filteredClients.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="client in filteredClients" :key="client.id">
                <div class="p-5">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-semibold text-white"
                                :style="{ backgroundColor: client.color }"
                            >
                                {{ client.name.slice(0, 2).toUpperCase() }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-foreground leading-tight">{{ client.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ client.contactName }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <Badge :variant="client.active ? 'success' : 'secondary'">
                                {{ client.active ? 'Actif' : 'Inactif' }}
                            </Badge>
                            <div class="relative group">
                                <button
                                    type="button"
                                    class="flex h-7 w-7 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground transition-colors"
                                    @click.stop
                                >
                                    <MoreVertical class="h-4 w-4" />
                                </button>
                                <div class="absolute right-0 top-full z-20 mt-1 hidden w-36 rounded-md border border-border bg-background shadow-md py-1 group-focus-within:block">
                                    <button type="button" class="w-full px-3 py-1.5 text-left text-sm hover:bg-accent transition-colors" @click="openEdit(client)">Modifier</button>
                                    <button type="button" class="w-full px-3 py-1.5 text-left text-sm hover:bg-accent transition-colors" @click="toggleActive(client)">{{ client.active ? 'Désactiver' : 'Réactiver' }}</button>
                                    <button type="button" class="w-full px-3 py-1.5 text-left text-sm text-destructive hover:bg-destructive/10 transition-colors" @click="confirmDelete(client)">Supprimer</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 space-y-1.5">
                        <div class="flex items-center gap-2 text-xs text-muted-foreground">
                            <Mail class="h-3 w-3 shrink-0" />
                            <span class="truncate">{{ client.email }}</span>
                        </div>
                        <div v-if="client.phone" class="flex items-center gap-2 text-xs text-muted-foreground">
                            <Phone class="h-3 w-3 shrink-0" />
                            <span>{{ client.phone }}</span>
                        </div>
                        <div v-if="client.address" class="flex items-center gap-2 text-xs text-muted-foreground">
                            <MapPin class="h-3 w-3 shrink-0" />
                            <span class="truncate">{{ client.address }}</span>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between border-t border-border pt-3">
                        <span class="text-xs text-muted-foreground">{{ getProjectCount(client.id) }} projet(s)</span>
                        <span class="text-xs text-muted-foreground">Depuis {{ formatDate(client.createdAt) }}</span>
                    </div>

                    <div class="mt-2 flex gap-2">
                        <button
                            type="button"
                            class="flex-1 h-7 rounded-md border border-border text-xs text-foreground hover:bg-accent transition-colors"
                            @click="openEdit(client)"
                        >
                            Modifier
                        </button>
                        <button
                            type="button"
                            class="h-7 px-2 rounded-md border border-destructive/30 text-xs text-destructive hover:bg-destructive/10 transition-colors"
                            @click="confirmDelete(client)"
                        >
                            Supprimer
                        </button>
                    </div>
                </div>
            </Card>
        </div>

        <div v-else class="flex flex-col items-center justify-center py-16 text-center">
            <p class="text-sm text-muted-foreground">Aucun client trouvé</p>
            <button type="button" class="mt-3 text-sm text-primary hover:underline" @click="openCreate">Créer un nouveau client</button>
        </div>
    </div>

    <Dialog :open="dialogOpen" :title="editingClient ? 'Modifier le client' : 'Nouveau client'" max-width="max-w-lg" @close="dialogOpen = false">
        <form class="space-y-4" @submit.prevent="saveClient">
            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Nom de l'entreprise *</label>
                    <input v-model="form.name" type="text" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" placeholder="Agence Nova" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Contact</label>
                    <input v-model="form.contactName" type="text" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" placeholder="Sophie Martin" />
                </div>
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-foreground">Email *</label>
                <input v-model="form.email" type="email" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" placeholder="contact@client.fr" />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Téléphone</label>
                    <input v-model="form.phone" type="tel" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" placeholder="01 23 45 67 89" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-foreground">Statut</label>
                    <div class="flex h-9 items-center">
                        <Switch v-model="form.active" :label="form.active ? 'Actif' : 'Inactif'" />
                    </div>
                </div>
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-foreground">Adresse</label>
                <input v-model="form.address" type="text" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" placeholder="12 rue Example, Paris" />
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-foreground">Couleur</label>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="color in COLOR_PRESETS"
                        :key="color"
                        type="button"
                        :style="{ backgroundColor: color }"
                        :class="['h-7 w-7 rounded-full border-2 transition-transform hover:scale-110', form.color === color ? 'border-foreground scale-110' : 'border-transparent']"
                        @click="form.color = color"
                    />
                    <input v-model="form.color" type="color" class="h-7 w-7 rounded-full cursor-pointer border border-border" />
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" class="h-9 px-4 text-sm font-medium rounded-md border border-border bg-background hover:bg-accent transition-colors" @click="dialogOpen = false">Annuler</button>
                <button type="submit" class="h-9 px-4 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:bg-primary/90 transition-colors">{{ editingClient ? 'Enregistrer' : 'Créer' }}</button>
            </div>
        </form>
    </Dialog>

    <AlertDialog
        :open="deleteDialogOpen"
        title="Supprimer le client"
        :description="`Êtes-vous sûr de vouloir supprimer le client « ${clientToDelete?.name} » ? Tous ses projets et saisies CRA seront également supprimés. Cette action est irréversible.`"
        confirm-label="Supprimer"
        variant="destructive"
        @confirm="doDelete"
        @cancel="deleteDialogOpen = false"
    />

    <ToastContainer />
</template>
