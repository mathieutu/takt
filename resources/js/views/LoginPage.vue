<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'

defineOptions({ layout: null })

const form = useForm({
    email: '',
    password: '',
})

function submit() {
    form.post('/login', { preserveScroll: true })
}
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-background px-4">
        <div class="mb-8 flex items-center gap-2">
            <div class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-lg">
                <img src="/public/images/logo.png" class="h-full w-full object-cover" />
            </div>
            <span class="text-xl font-semibold tracking-tight text-foreground">AssoFlow</span>
        </div>

        <div class="w-full max-w-sm rounded-lg border border-border bg-card shadow-sm">
            <div class="p-6 pb-4">
                <h1 class="text-base font-semibold text-foreground">Connexion</h1>
                <p class="mt-1 text-sm text-muted-foreground">Accédez à votre espace de gestion</p>
            </div>
            <div class="p-6 pt-0">
                <form @submit.prevent="submit" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground">Adresse e-mail</label>
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
                        <label class="text-sm font-medium text-foreground">Mot de passe</label>
                        <input
                            v-model="form.password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                        />
                    </div>

                    <p v-if="form.errors['#global']" class="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">
                        {{ form.errors['#global'] }}
                    </p>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="h-9 w-full rounded-md bg-primary text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-60"
                    >
                        Se connecter
                    </button>
                </form>

                <p class="mt-4 text-center text-sm text-muted-foreground">
                    Pas encore de compte ?
                    <a href="/register" class="font-medium text-primary hover:underline">Créer un compte</a>
                </p>
            </div>
        </div>
    </div>
</template>
