<script setup lang="ts">
import { ref, computed } from 'vue'

type OldValues = {
    email: string
    type: string
    model_first_name: string
    model_last_name: string
    model_name: string
}

const props = withDefaults(defineProps<{
    action?: string
    csrfToken?: string
    errors?: Record<string, string[]>
    old?: OldValues
}>(), {
    action: '/register',
    csrfToken: '',
    errors: () => ({}),
    old: () => ({
        email: '',
        type: 'user',
        model_first_name: '',
        model_last_name: '',
        model_name: '',
    }),
})

const email = ref(props.old.email)
const password = ref('')
const passwordConfirmation = ref('')
const accountType = ref(props.old.type || 'user')
const firstName = ref(props.old.model_first_name)
const lastName = ref(props.old.model_last_name)
const orgName = ref(props.old.model_name)

const isUser = computed(() => accountType.value === 'user')

function fieldError(key: string): string | null {
    return props.errors[key]?.[0] ?? null
}
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-background px-4 py-12">
        <div class="mb-8 flex items-center gap-2">
            <div class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-lg">
                <img src="/public/images/logo.png" class="h-full w-full object-cover" />
            </div>
            <span class="text-xl font-semibold tracking-tight text-foreground">AssoFlow</span>
        </div>

        <div class="w-full max-w-sm rounded-lg border border-border bg-card shadow-sm">
            <div class="p-6 pb-4">
                <h1 class="text-base font-semibold text-foreground">Créer un compte</h1>
                <p class="mt-1 text-sm text-muted-foreground">Rejoignez AssoFlow pour gérer votre activité</p>
            </div>
            <div class="p-6 pt-0">
                <form :action="action" method="POST" class="flex flex-col gap-4">
                    <input type="hidden" name="_token" :value="csrfToken" />

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground">Adresse e-mail</label>
                        <input
                            v-model="email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                            :class="{ 'border-destructive focus:ring-destructive': fieldError('email') }"
                        />
                        <p v-if="fieldError('email')" class="text-xs text-destructive">{{ fieldError('email') }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground">Mot de passe</label>
                        <input
                            v-model="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                            :class="{ 'border-destructive focus:ring-destructive': fieldError('password') }"
                        />
                        <p v-if="fieldError('password')" class="text-xs text-destructive">{{ fieldError('password') }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground">Confirmer le mot de passe</label>
                        <input
                            v-model="passwordConfirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                        />
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-foreground">Type de compte</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <label
                                class="flex cursor-pointer items-center justify-center rounded-md border px-3 py-2 text-sm transition-colors"
                                :class="isUser
                                    ? 'border-primary bg-primary/5 text-primary font-medium'
                                    : 'border-border bg-background text-muted-foreground hover:bg-accent'"
                            >
                                <input type="radio" name="type" value="user" v-model="accountType" class="sr-only" />
                                Utilisateur
                            </label>
                            <label
                                class="flex cursor-pointer items-center justify-center rounded-md border px-3 py-2 text-sm transition-colors"
                                :class="!isUser
                                    ? 'border-primary bg-primary/5 text-primary font-medium'
                                    : 'border-border bg-background text-muted-foreground hover:bg-accent'"
                            >
                                <input type="radio" name="type" value="organization" v-model="accountType" class="sr-only" />
                                Organisation
                            </label>
                        </div>
                        <p v-if="fieldError('type')" class="text-xs text-destructive">{{ fieldError('type') }}</p>
                    </div>

                    <template v-if="isUser">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground">Prénom</label>
                            <input
                                v-model="firstName"
                                name="model[first_name]"
                                type="text"
                                autocomplete="given-name"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                :class="{ 'border-destructive focus:ring-destructive': fieldError('model.first_name') }"
                            />
                            <p v-if="fieldError('model.first_name')" class="text-xs text-destructive">{{ fieldError('model.first_name') }}</p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground">Nom de famille</label>
                            <input
                                v-model="lastName"
                                name="model[last_name]"
                                type="text"
                                autocomplete="family-name"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                :class="{ 'border-destructive focus:ring-destructive': fieldError('model.last_name') }"
                            />
                            <p v-if="fieldError('model.last_name')" class="text-xs text-destructive">{{ fieldError('model.last_name') }}</p>
                        </div>
                    </template>

                    <template v-else>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground">Nom de l'organisation</label>
                            <input
                                v-model="orgName"
                                name="model[name]"
                                type="text"
                                autocomplete="organization"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                :class="{ 'border-destructive focus:ring-destructive': fieldError('model.name') }"
                            />
                            <p v-if="fieldError('model.name')" class="text-xs text-destructive">{{ fieldError('model.name') }}</p>
                        </div>
                    </template>

                    <button
                        type="submit"
                        class="h-9 w-full rounded-md bg-primary text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                    >
                        Créer mon compte
                    </button>
                </form>

                <p class="mt-4 text-center text-sm text-muted-foreground">
                    Déjà un compte ?
                    <a href="/login" class="font-medium text-primary hover:underline">Se connecter</a>
                </p>
            </div>
        </div>
    </div>
</template>
