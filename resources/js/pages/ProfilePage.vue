<script setup lang="ts">
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { update, destroy } from '@/wayfinder/routes/profile'

const props = defineProps<{
    user: {
        name: string
        email: string
        github_id: string | null
    }
}>()

const form = useForm({
    name: props.user.name,
    email: props.user.email,
})

const initials = computed(() =>
    form.name.split(' ').map((w: string) => w[0] ?? '').slice(0, 2).join('').toUpperCase()
)

function submit() {
    form.put(update().url, { preserveScroll: true })
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
                <h1 class="text-lg font-semibold">Profil</h1>
                <p class="text-sm text-muted">Gérez votre profil et vos informations</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <UCard>
                    <template #header>
                        <div class="flex items-center gap-3">
                            <div class="hidden sm:flex h-10 w-10 items-center justify-center rounded-full bg-elevated text-sm font-semibold shrink-0">
                                {{ initials }}
                            </div>
                            <div class="min-w-0 overflow-hidden">
                                <p class="text-sm font-medium truncate">{{ form.name }}</p>
                                <p class="text-xs text-muted truncate">{{ form.email }}</p>
                            </div>
                        </div>
                    </template>

                    <div class="space-y-4">
                        <UFormField label="Nom" required :error="form.errors.name">
                            <UInput
                                v-model="form.name"
                                type="text"
                                autocomplete="name"
                                :color="form.errors.name ? 'error' : undefined"
                                class="w-full"
                            />
                        </UFormField>

                        <UFormField label="Adresse e-mail" required :error="form.errors.email">
                            <UInput
                                v-model="form.email"
                                name="email"
                                type="email"
                                autocomplete="email"
                                :color="form.errors.email ? 'error' : undefined"
                                class="w-full"
                            />
                        </UFormField>

                        <div v-if="user.github_id" class="flex flex-col gap-1.5">
                            <span class="text-sm font-medium">GitHub</span>
                            <div class="flex items-center gap-2 text-sm text-muted">
                                <UIcon name="i-simple-icons-github" class="size-4 shrink-0" />
                                Connecté via GitHub
                            </div>
                        </div>
                    </div>
                </UCard>

                <div class="flex justify-end">
                    <UButton type="submit" :loading="form.processing" label="Enregistrer" />
                </div>
            </form>

            <UCard>
                <template #header>
                    <h2 class="text-sm font-semibold">Zone de danger</h2>
                </template>

                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium">Supprimer mon compte</p>
                        <p class="text-xs text-muted">Cette action est irréversible.</p>
                    </div>
                    <UButton
                        type="button"
                        label="Supprimer"
                        color="error"
                        variant="outline"
                        @click="deleteAccount"
                    />
                </div>
            </UCard>
        </div>
    </main>
</template>
