<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import Card from '../components/ui/Card.vue'
import CardHeader from '../components/ui/CardHeader.vue'
import CardTitle from '../components/ui/CardTitle.vue'
import CardContent from '../components/ui/CardContent.vue'

const props = defineProps<{
    account: {
        email: string
        type: string
        user?: { first_name: string; last_name: string } | null
        organization?: { name: string } | null
    }
}>()

const page = usePage()
const success = computed(() => (page.props.flash as any)?.success)

const form = useForm({
    email: props.account.email,
    password: '',
    password_confirmation: '',
    user: {
        first_name: props.account.user?.first_name ?? '',
        last_name:  props.account.user?.last_name  ?? '',
    },
    organization: {
        name: props.account.organization?.name ?? '',
    },
})

const isUser = computed(() => props.account.type === 'user')

const initials = computed(() => {
    if (isUser.value) {
        return ((form.user.first_name[0] ?? '') + (form.user.last_name[0] ?? '')).toUpperCase()
    }
    return (form.organization.name[0] ?? '').toUpperCase()
})

const displayName = computed(() =>
    isUser.value
        ? `${form.user.first_name} ${form.user.last_name}`.trim()
        : form.organization.name
)

const showPassword = ref(false)

function submit() {
    form.put('/dashboard/settings', { preserveScroll: true })
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

            <form @submit.prevent="submit" class="space-y-6">
                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-3">
                            <div
                                class="hidden sm:flex h-10 w-10 items-center justify-center rounded-full bg-secondary text-sm font-semibold text-secondary-foreground">
                                {{ initials }}
                            </div>
                            <div class="w-fit overflow-hidden">
                                <p class="text-sm font-medium text-foreground truncate">{{ displayName }}</p>
                                <p class="text-xs text-muted-foreground truncate">{{ form.email }}</p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground">Adresse e-mail<span class="text-destructive ml-0.5">*</span></label>
                                <input v-model="form.email" name="email" type="email" autocomplete="email"
                                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                    :class="{ 'border-destructive focus:ring-destructive': form.errors.email }" />
                                <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
                            </div>

                            <template v-if="isUser">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-sm font-medium text-foreground">Prénom<span class="text-destructive ml-0.5">*</span></label>
                                        <input v-model="form.user.first_name" type="text" autocomplete="given-name"
                                            class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                            :class="{ 'border-destructive focus:ring-destructive': form.errors['user.first_name'] }" />
                                        <p v-if="form.errors['user.first_name']" class="text-xs text-destructive">{{ form.errors['user.first_name'] }}</p>
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-sm font-medium text-foreground">Nom de famille<span class="text-destructive ml-0.5">*</span></label>
                                        <input v-model="form.user.last_name" type="text" autocomplete="family-name"
                                            class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                            :class="{ 'border-destructive focus:ring-destructive': form.errors['user.last_name'] }" />
                                        <p v-if="form.errors['user.last_name']" class="text-xs text-destructive">{{ form.errors['user.last_name'] }}</p>
                                    </div>
                                </div>
                            </template>

                            <template v-else>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium text-foreground">Nom de l'organisation<span class="text-destructive ml-0.5">*</span></label>
                                    <input v-model="form.organization.name" type="text" autocomplete="organization"
                                        class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                        :class="{ 'border-destructive focus:ring-destructive': form.errors['organization.name'] }" />
                                    <p v-if="form.errors['organization.name']" class="text-xs text-destructive">{{ form.errors['organization.name'] }}</p>
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
                                <input v-model="form.password" type="password" autocomplete="new-password"
                                    placeholder="Laisser vide pour ne pas modifier"
                                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                    :class="{ 'border-destructive focus:ring-destructive': form.errors.password }"
                                    @input="showPassword = form.password.length > 0" />
                                <p v-if="form.errors.password" class="text-xs text-destructive">{{ form.errors.password }}</p>
                            </div>
                            <div v-if="showPassword" class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground">Confirmer le mot de passe</label>
                                <input v-model="form.password_confirmation" type="password" autocomplete="new-password"
                                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex justify-end">
                    <button type="submit" :disabled="form.processing"
                        class="h-9 px-4 rounded-md bg-primary text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-60">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </main>
</template>
