<script setup lang="ts">
import { ref, computed } from 'vue'
import { Clock } from 'lucide-vue-next'
import Card from '../components/ui/Card.vue'
import CardHeader from '../components/ui/CardHeader.vue'
import CardTitle from '../components/ui/CardTitle.vue'
import CardContent from '../components/ui/CardContent.vue'

const props = withDefaults(defineProps<{
    action?: string
    csrfToken?: string
    type?: string
    email?: string
    firstName?: string
    lastName?: string
    orgName?: string
    errors?: Record<string, string[]>
    success?: boolean
}>(), {
    action: '/dashboard/settings',
    csrfToken: '',
    type: 'user',
    email: '',
    firstName: '',
    lastName: '',
    orgName: '',
    errors: () => ({}),
    success: false,
})

const email = ref(props.email)
const password = ref('')
const passwordConfirmation = ref('')
const firstName = ref(props.firstName)
const lastName = ref(props.lastName)
const orgName = ref(props.orgName)

const isUser = computed(() => props.type === 'user')

const initials = computed(() => {
    if (isUser.value) {
        return ((firstName.value[0] ?? '') + (lastName.value[0] ?? '')).toUpperCase()
    }
    return (orgName.value[0] ?? '').toUpperCase()
})

const displayName = computed(() =>
    isUser.value ? `${firstName.value} ${lastName.value}`.trim() : orgName.value
)

function fieldError(key: string): string | null {
    return props.errors[key]?.[0] ?? null
}
</script>

<template>
    <main class="flex-1 px-6 py-8">
        <div class="mx-auto max-w-2xl space-y-6">
            <div>
                <h1 class="text-lg font-semibold text-foreground">Paramètres</h1>
                <p class="text-sm text-muted-foreground">Gérez votre profil et vos informations</p>
            </div>

            <div v-if="success"
                class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                Vos informations ont été mises à jour.
            </div>

            <form :action="action" method="POST" class="space-y-6">
                <input type="hidden" name="_token" :value="csrfToken" />
                <input type="hidden" name="_method" value="PUT" />

                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-3">
                            <div
                                class="hidden sm:flex h-10 w-10 items-center justify-center rounded-full bg-secondary text-sm font-semibold text-secondary-foreground">
                                {{ initials }}
                            </div>
                            <div class="w-fit overflow-hidden">
                                <p class="text-sm font-medium text-foreground truncate">{{ displayName }}</p>
                                <p class="text-xs text-muted-foreground truncate">{{ email }}</p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground">Adresse e-mail</label>
                                <input v-model="email" name="email" type="email" autocomplete="email"
                                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                    :class="{ 'border-destructive focus:ring-destructive': fieldError('email') }" />
                                <p v-if="fieldError('email')" class="text-xs text-destructive">{{ fieldError('email') }}
                                </p>
                            </div>

                            <template v-if="isUser">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-sm font-medium text-foreground">Prénom</label>
                                        <input v-model="firstName" name="user[first_name]" type="text"
                                            autocomplete="given-name"
                                            class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                            :class="{ 'border-destructive focus:ring-destructive': fieldError('user.first_name') }" />
                                        <p v-if="fieldError('user.first_name')" class="text-xs text-destructive">{{
                                            fieldError('user.first_name') }}</p>
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-sm font-medium text-foreground">Nom de famille</label>
                                        <input v-model="lastName" name="user[last_name]" type="text"
                                            autocomplete="family-name"
                                            class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                            :class="{ 'border-destructive focus:ring-destructive': fieldError('user.last_name') }" />
                                        <p v-if="fieldError('user.last_name')" class="text-xs text-destructive">{{
                                            fieldError('user.last_name') }}</p>
                                    </div>
                                </div>
                            </template>

                            <template v-else>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium text-foreground">Nom de l'organisation</label>
                                    <input v-model="orgName" name="organization[name]" type="text"
                                        autocomplete="organization"
                                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                        :class="{ 'border-destructive focus:ring-destructive': fieldError('organization.name') }" />
                                    <p v-if="fieldError('organization.name')" class="text-xs text-destructive">{{
                                        fieldError('organization.name') }}</p>
                                </div>
                            </template>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Mot de passe</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground">Nouveau mot de passe</label>
                                <input v-model="password" :name="password ? 'password' : undefined" type="password"
                                    autocomplete="new-password" placeholder="Laisser vide pour ne pas modifier"
                                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                    :class="{ 'border-destructive focus:ring-destructive': fieldError('password') }" />
                                <p v-if="fieldError('password')" class="text-xs text-destructive">{{
                                    fieldError('password') }}</p>
                            </div>
                            <div v-if="password" class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground">Confirmer le mot de passe</label>
                                <input v-model="passwordConfirmation" name="password_confirmation" type="password"
                                    autocomplete="new-password"
                                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex justify-end">
                    <button type="submit"
                        class="h-9 px-4 rounded-md bg-primary text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </main>
</template>
