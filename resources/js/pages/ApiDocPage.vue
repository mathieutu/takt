<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps<{
  base_url: string,
  api_token: string | null,
  projects: { id: string, name: string, client_name: string }[],
}>()

const selectedProjectId = ref(props.projects[0]?.id ?? '{project_id}')

const apiBase = computed(() => `${props.base_url}/api`)
const exampleProjectId = computed(() => selectedProjectId.value)
const exampleToken = computed(() => props.api_token ?? '{votre_token}')

const curlExample = computed(() => `curl -X PATCH "${apiBase.value}/projects/${exampleProjectId.value}/entries" \\
  -H "Authorization: Bearer ${exampleToken.value}" \\
  -H "Content-Type: application/json" \\
  -H "Accept: application/json" \\
  -d '{
    "entries": [
      { "date": "2026-01-15", "coverage": 75, "title": "Développement" },
      { "date": "2026-01-16", "coverage": 50 }
    ]
  }'`)
</script>

<template>
  <Head title="Documentation API" />
  <main class="flex-1 px-6 py-8">
    <div class="mx-auto max-w-2xl space-y-6">
      <div>
        <h1 class="text-lg font-semibold">Documentation API</h1>
        <p class="text-sm text-muted">Accédez à vos données Takt depuis vos propres scripts.</p>
      </div>

      <UCard>
        <template #header>
          <h2 class="text-sm font-semibold">Authentification</h2>
        </template>
        <div class="space-y-3 text-sm">
          <p>Toutes les requêtes doivent inclure un header <code class="rounded bg-neutral-100 px-1 py-0.5 font-mono text-xs dark:bg-neutral-800">Authorization</code> avec votre token API.</p>
          <pre class="overflow-x-auto rounded-lg bg-neutral-100 p-3 font-mono text-xs dark:bg-neutral-800">Authorization: Bearer {{ exampleToken }}</pre>
          <p class="text-muted">Générez votre token depuis votre <ULink href="/profile" class="font-medium underline">page de profil</ULink>.</p>
        </div>
      </UCard>

      <UCard>
        <template #header>
          <h2 class="text-sm font-semibold">Endpoints</h2>
        </template>
        <div class="space-y-6">
          <div class="space-y-3">
            <div class="flex items-center gap-2">
              <UBadge label="PATCH" color="warning" variant="subtle" class="font-mono" />
              <code class="text-xs font-mono">/api/projects/{'{project_id}'}/entries</code>
            </div>
            <p class="text-sm text-muted">Synchronise (crée ou met à jour) des entrées de timesheet sur un projet.</p>

            <div class="space-y-2">
              <p class="text-xs font-medium uppercase tracking-wide text-muted">Corps de la requête</p>
              <pre class="overflow-x-auto rounded-lg bg-neutral-100 p-3 font-mono text-xs dark:bg-neutral-800">{
  "entries": [
    {
      "date":        "2026-01-15",  // requis — format YYYY-MM-DD
      "coverage":    75,            // requis — entier entre 0 et 100
      "title":       "Dev feature", // optionnel
      "description": "..."          // optionnel
    }
  ]
}</pre>
            </div>

            <div class="space-y-2">
              <p class="text-xs font-medium uppercase tracking-wide text-muted">Réponse (200)</p>
              <pre class="overflow-x-auto rounded-lg bg-neutral-100 p-3 font-mono text-xs dark:bg-neutral-800">{ "message": "Entries synchronized." }</pre>
            </div>

            <div class="space-y-2">
              <p class="text-xs font-medium uppercase tracking-wide text-muted">Codes d'erreur</p>
              <ul class="space-y-1 text-sm">
                <li><code class="font-mono text-xs">401</code> — Token manquant ou invalide</li>
                <li><code class="font-mono text-xs">403</code> — Le projet n'appartient pas à votre compte</li>
                <li><code class="font-mono text-xs">422</code> — Données invalides (couverture hors plage, date manquante…)</li>
              </ul>
            </div>
          </div>
        </div>
      </UCard>

      <UCard>
        <template #header>
          <h2 class="text-sm font-semibold">Exemple</h2>
        </template>
        <div class="space-y-3">
          <div v-if="projects.length" class="flex items-center gap-3">
            <p class="text-sm shrink-0">Projet :</p>
            <USelect
              v-model="selectedProjectId"
              :items="projects.map(p => ({ label: `${p.client_name} — ${p.name}`, value: p.id }))"
              class="flex-1"
            />
          </div>
          <pre class="overflow-x-auto rounded-lg bg-neutral-100 p-3 font-mono text-xs dark:bg-neutral-800 whitespace-pre-wrap break-all">{{ curlExample }}</pre>
        </div>
      </UCard>
    </div>
  </main>
</template>
