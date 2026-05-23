<script setup lang="ts">
import {computed} from 'vue'
import {useForm, usePage} from '@inertiajs/vue3'
import {update, destroy} from '@/wayfinder/routes/profile'
import Card from '../components/ui/Card.vue'
import CardHeader from '../components/ui/CardHeader.vue'
import CardTitle from '../components/ui/CardTitle.vue'
import CardContent from '../components/ui/CardContent.vue'

const props = defineProps<{
    user: {
        name: string
        email: string
        github_id: string | null
    }
}>()

const page = usePage()
const success = computed(() => page.props.flash?.success)

const form = useForm({
    name: props.user.name,
    email: props.user.email,
})

const initials = computed(() =>
    form.name.split(' ').map((w: string) => w[0] ?? '').slice(0, 2).join('').toUpperCase()
)

function submit() {
    form.put(update().url, {preserveScroll: true})
}

function deleteAccount() {
    if (confirm('Supprimer définitivement votre compte ? Cette action est irréversible.')) {
        form.delete(destroy().url)
    }
}
</script>

<template>
    <main class="flex-1 px-6 py-8">
        <div class="mx-auto max-w-2xl space-y-6">
            <div>
                <h1 class="text-lg font-semibold text-foreground">Profil</h1>
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
                                <p class="text-sm font-medium text-foreground truncate">{{ form.name }}</p>
                                <p class="text-xs text-muted-foreground truncate">{{ form.email }}</p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground">Nom<span
                                    class="text-destructive ml-0.5">*</span></label>
                                <input v-model="form.name" type="text" autocomplete="name"
                                       class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                       :class="{ 'border-destructive focus:ring-destructive': form.errors.name }"/>
                                <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground">Adresse e-mail<span
                                    class="text-destructive ml-0.5">*</span></label>
                                <input v-model="form.email" name="email" type="email" autocomplete="email"
                                       class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                       :class="{ 'border-destructive focus:ring-destructive': form.errors.email }"/>
                                <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
                            </div>

                            <div v-if="user.github_id" class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground">GitHub</label>
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-muted-foreground" viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12Z"/>
                                    </svg>
                                    <span class="text-sm text-foreground">Connecté via GitHub</span>
                                </div>
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

            <Card>
                <CardHeader>
                    <CardTitle>Zone de danger</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-foreground">Supprimer mon compte</p>
                            <p class="text-xs text-muted-foreground">Cette action est irréversible.</p>
                        </div>
                        <button
                            type="button"
                            class="h-9 px-4 rounded-md border border-destructive text-sm font-medium text-destructive transition-colors hover:bg-destructive/10"
                            @click="deleteAccount"
                        >
                            Supprimer
                        </button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </main>
</template>
