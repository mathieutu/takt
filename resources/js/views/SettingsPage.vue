<script setup lang="ts">
import { ref, computed } from 'vue'
import { User, Activity } from 'lucide-vue-next'
import { useToast } from '../composables/useToast'
import type { User as UserType } from '../types'
import Card from '../components/ui/Card.vue'
import CardHeader from '../components/ui/CardHeader.vue'
import CardTitle from '../components/ui/CardTitle.vue'
import CardContent from '../components/ui/CardContent.vue'
import ToastContainer from '../components/ui/ToastContainer.vue'

interface Stats {
    clients: number
    activeClients: number
    projects: number
    activeProjects: number
    entries: number
    totalDays: number
    totalRevenue: number
}

const props = defineProps<{
    user: UserType
    stats: Stats
}>()

const emit = defineEmits<{
    'update-profile': [data: { name: string; email: string; role: string }]
    'update-tjm': [tjm: number]
}>()

const { toast } = useToast()

const profileForm = ref({
    name: props.user.name,
    email: props.user.email,
    role: props.user.role,
})

const activityForm = ref({
    tjm: props.user.tjm,
})

const initials = computed(() =>
    profileForm.value.name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase()
)

function saveProfile() {
    if (!profileForm.value.name.trim() || !profileForm.value.email.trim()) {
        toast('Nom et email sont requis', 'error')
        return
    }
    emit('update-profile', {
        name: profileForm.value.name,
        email: profileForm.value.email,
        role: profileForm.value.role,
    })
    toast('Profil mis à jour')
}

function saveActivity() {
    if (!activityForm.value.tjm || activityForm.value.tjm <= 0) {
        toast('Le TJM doit être supérieur à 0', 'error')
        return
    }
    emit('update-tjm', Number(activityForm.value.tjm))
    toast('Paramètres d\'activité mis à jour')
}

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(amount)
}
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1 class="text-lg font-semibold text-foreground">Paramètres</h1>
            <p class="text-sm text-muted-foreground">Gérez votre profil et les préférences de l'application</p>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <User class="h-4 w-4 text-muted-foreground" />
                            <CardTitle>Profil</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <form class="space-y-4" @submit.prevent="saveProfile">
                            <div class="flex items-center gap-4">
                                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-secondary text-lg font-semibold text-secondary-foreground">
                                    {{ initials }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-foreground">{{ profileForm.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ profileForm.email }}</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium text-foreground">Nom complet *</label>
                                    <input v-model="profileForm.name" type="text" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" placeholder="Thomas Durand" />
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium text-foreground">Email *</label>
                                    <input v-model="profileForm.email" type="email" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" placeholder="thomas@freelance.fr" />
                                </div>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground">Métier / Rôle</label>
                                <input v-model="profileForm.role" type="text" class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" placeholder="Développeur web fullstack" />
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="h-9 px-4 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:bg-primary/90 transition-colors">Enregistrer le profil</button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <Activity class="h-4 w-4 text-muted-foreground" />
                            <CardTitle>Paramètres d'activité</CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <form class="space-y-4" @submit.prevent="saveActivity">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground">TJM par défaut (€/jour)</label>
                                <div class="flex items-center gap-2">
                                    <input v-model="activityForm.tjm" type="number" min="0" step="10" class="h-9 w-36 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-1 focus:ring-ring" placeholder="550" />
                                    <span class="text-sm text-muted-foreground">€ / jour</span>
                                </div>
                                <p class="text-xs text-muted-foreground">Utilisé pour les projets sans TJM spécifique. Actuellement : {{ user.tjm }} €/j</p>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="h-9 px-4 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:bg-primary/90 transition-colors">Enregistrer</button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>

            <div class="space-y-4">
                <Card>
                    <CardHeader><CardTitle>Résumé</CardTitle></CardHeader>
                    <CardContent>
                        <dl class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <dt class="text-muted-foreground">Clients</dt>
                                <dd class="font-medium text-foreground">{{ stats.clients }} ({{ stats.activeClients }} actifs)</dd>
                            </div>
                            <div class="flex justify-between text-sm">
                                <dt class="text-muted-foreground">Projets</dt>
                                <dd class="font-medium text-foreground">{{ stats.projects }} ({{ stats.activeProjects }} actifs)</dd>
                            </div>
                            <div class="flex justify-between text-sm">
                                <dt class="text-muted-foreground">Saisies CRA</dt>
                                <dd class="font-medium text-foreground">{{ stats.entries }}</dd>
                            </div>
                            <div class="border-t border-border pt-2 flex justify-between text-sm">
                                <dt class="text-muted-foreground">Total jours</dt>
                                <dd class="font-medium text-foreground">{{ stats.totalDays }}</dd>
                            </div>
                            <div class="flex justify-between text-sm">
                                <dt class="text-muted-foreground">CA total</dt>
                                <dd class="font-semibold text-foreground">{{ formatCurrency(stats.totalRevenue) }}</dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>À propos</CardTitle></CardHeader>
                    <CardContent>
                        <dl class="space-y-2 text-xs">
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">Application</dt>
                                <dd class="text-foreground font-medium">AssoFlow</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">Version</dt>
                                <dd class="text-foreground">1.0.0</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">Stack</dt>
                                <dd class="text-foreground">Laravel 12 + Vue 3</dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>

    <ToastContainer />
</template>
