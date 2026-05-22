<script setup lang="ts">
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'

defineOptions({ layout: null })

const form = useForm({
    email: '',
    password: '',
    password_confirmation: '',
    type: 'user',
    model: {
        first_name: '',
        last_name: '',
        name: '',
    },
})

const isUser = computed(() => form.type === 'user')

function submit() {
    form.post('/register', { preserveScroll: true })
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
                <form @submit.prevent="submit" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground">Adresse e-mail<span class="text-destructive ml-0.5">*</span></label>
                        <input
                            v-model="form.email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                            :class="{ 'border-destructive focus:ring-destructive': form.errors.email }"
                        />
                        <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground">Mot de passe<span class="text-destructive ml-0.5">*</span></label>
                        <input
                            v-model="form.password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                            :class="{ 'border-destructive focus:ring-destructive': form.errors.password }"
                        />
                        <p v-if="form.errors.password" class="text-xs text-destructive">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground">Confirmer le mot de passe<span class="text-destructive ml-0.5">*</span></label>
                        <input
                            v-model="form.password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                        />
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-foreground">Type de compte<span class="text-destructive ml-0.5">*</span></label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <label
                                class="flex cursor-pointer items-center justify-center rounded-md border px-3 py-2 text-sm transition-colors"
                                :class="isUser
                                    ? 'border-primary bg-primary/5 text-primary font-medium'
                                    : 'border-border bg-background text-muted-foreground hover:bg-accent'"
                            >
                                <input type="radio" name="type" value="user" v-model="form.type" class="sr-only" />
                                Utilisateur
                            </label>
                            <label
                                class="flex cursor-pointer items-center justify-center rounded-md border px-3 py-2 text-sm transition-colors"
                                :class="!isUser
                                    ? 'border-primary bg-primary/5 text-primary font-medium'
                                    : 'border-border bg-background text-muted-foreground hover:bg-accent'"
                            >
                                <input type="radio" name="type" value="organization" v-model="form.type" class="sr-only" />
                                Organisation
                            </label>
                        </div>
                        <p v-if="form.errors.type" class="text-xs text-destructive">{{ form.errors.type }}</p>
                    </div>

                    <template v-if="isUser">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground">Prénom<span class="text-destructive ml-0.5">*</span></label>
                            <input
                                v-model="form.model.first_name"
                                type="text"
                                autocomplete="given-name"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                :class="{ 'border-destructive focus:ring-destructive': form.errors['model.first_name'] }"
                            />
                            <p v-if="form.errors['model.first_name']" class="text-xs text-destructive">{{ form.errors['model.first_name'] }}</p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground">Nom de famille<span class="text-destructive ml-0.5">*</span></label>
                            <input
                                v-model="form.model.last_name"
                                type="text"
                                autocomplete="family-name"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                :class="{ 'border-destructive focus:ring-destructive': form.errors['model.last_name'] }"
                            />
                            <p v-if="form.errors['model.last_name']" class="text-xs text-destructive">{{ form.errors['model.last_name'] }}</p>
                        </div>
                    </template>

                    <template v-else>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground">Nom de l'organisation<span class="text-destructive ml-0.5">*</span></label>
                            <input
                                v-model="form.model.name"
                                type="text"
                                autocomplete="organization"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                :class="{ 'border-destructive focus:ring-destructive': form.errors['model.name'] }"
                            />
                            <p v-if="form.errors['model.name']" class="text-xs text-destructive">{{ form.errors['model.name'] }}</p>
                        </div>
                    </template>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="h-9 w-full rounded-md bg-primary text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-60"
                    >
                        Créer mon compte
                    </button>
                </form>

                <p class="mt-4 text-center text-sm text-muted-foreground">
                    Déjà un compte ?
                    <Link href="/login" class="font-medium text-primary hover:underline">Se connecter</Link>
                </p>
            </div>
        </div>
    </div>
</template>
