<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { csv as csvRoute, index as indexRoute, xlsx as xlsxRoute } from '@/wayfinder/routes/exports'

type Entry = { id: number, start_date: string, day_coverage: number, label: string, comments: string }
type Project = { id: number, name: string, client_name: string, daily_rate: number, entries: Entry[] }
type ProjectOption = { id: number, name: string, client_name: string }

const props = withDefaults(defineProps<{
  allProjects?: ProjectOption[],
  selectedProjectIds?: number[],
  dateStart?: string,
  dateEnd?: string,
  projects?: Project[],
}>(), {
  allProjects: () => [],
  selectedProjectIds: () => [],
  dateStart: '',
  dateEnd: '',
  projects: () => [],
})

const MONTHS_FR = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']

function readUrlParams() {
  const p = new URLSearchParams(window.location.search)
  const ids = p.getAll('projects[]').map(Number).filter(Boolean)
  return {
    ids: ids.length ? ids : [...props.selectedProjectIds],
    dateStart: p.get('date_start') ?? props.dateStart,
    dateEnd: p.get('date_end') ?? props.dateEnd,
  }
}

const _init = readUrlParams()
const localProjectIds = ref<number[]>(_init.ids)
const localDateStart = ref(_init.dateStart)
const localDateEnd = ref(_init.dateEnd)
const localProjects = ref<Project[]>([...props.projects])
const loading = ref(false)
const hasLoaded = ref(props.projects.length > 0 || props.selectedProjectIds.length > 0)

const projectOptions = computed(() =>
  props.allProjects.map(p => ({ value: p.id, label: p.name, description: p.client_name })),
)

const selectedProjects = computed(() =>
  props.allProjects.filter(p => localProjectIds.value.includes(p.id)),
)

const dropdownLabel = computed(() => {
  const n = localProjectIds.value.length
  if (n === 0) return 'Sélectionner des projets…'
  if (n === 1) return '1 projet sélectionné'
  return `${n} projets sélectionnés`
})

function removeProject(id: number) {
  localProjectIds.value = localProjectIds.value.filter(p => p !== id)
}

const allEntries = computed(() => {
  const rows: {
    date: string,
    project_name: string,
    client_name: string,
    daily_rate: number,
    day_coverage: number,
    label: string,
  }[] = []
  for (const p of localProjects.value) {
    for (const e of p.entries) {
      rows.push({
        date: e.start_date,
        project_name: p.name,
        client_name: p.client_name,
        daily_rate: p.daily_rate,
        day_coverage: e.day_coverage,
        label: e.label,
      })
    }
  }
  return rows.sort((a, b) => a.date.localeCompare(b.date))
})

const totalDays = computed(() =>
  allEntries.value.reduce((s, e) => s + e.day_coverage / 100, 0),
)

const totalCA = computed(() =>
  allEntries.value.reduce((s, e) => s + (e.day_coverage / 100) * e.daily_rate, 0),
)

function buildExportParams() {
  const params = new URLSearchParams()
  for (const id of localProjectIds.value) params.append('projects[]', String(id))
  if (localDateStart.value) params.set('date_start', localDateStart.value)
  if (localDateEnd.value) params.set('date_end', localDateEnd.value)
  return params.toString()
}

const exportUrl = computed(() => `${csvRoute.url()}?${buildExportParams()}`)
const xlsxExportUrl = computed(() => `${xlsxRoute.url()}?${buildExportParams()}`)

const exportMenuItems = computed(() => [[
  {
    label: 'Exporter CSV',
    icon: 'i-lucide-download',
    onSelect: () => {
      window.location.href = exportUrl.value
    },
  },
  {
    label: 'Exporter XLSX',
    icon: 'i-lucide-download',
    onSelect: () => {
      window.location.href = xlsxExportUrl.value
    },
  },
]])

function syncUrl() {
  const params = new URLSearchParams()
  for (const id of localProjectIds.value) params.append('projects[]', String(id))
  if (localDateStart.value) params.set('date_start', localDateStart.value)
  if (localDateEnd.value) params.set('date_end', localDateEnd.value)
  const qs = params.toString()
  history.replaceState(null, '', qs ? `?${qs}` : window.location.pathname)
}

async function applyFilters() {
  if (localProjectIds.value.length === 0) return
  loading.value = true
  syncUrl()
  const params = new URLSearchParams()
  for (const id of localProjectIds.value) params.append('projects[]', String(id))
  if (localDateStart.value) params.set('date_start', localDateStart.value)
  if (localDateEnd.value) params.set('date_end', localDateEnd.value)
  try {
    const res = await fetch(`${indexRoute.url()}?${params.toString()}`, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
    if (res.ok) {
      const data = await res.json()
      localProjects.value = data.projects
      hasLoaded.value = true
    }
  } finally {
    loading.value = false
  }
}

let debounceTimer: ReturnType<typeof setTimeout> | null = null

function scheduleApply() {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(applyFilters, 350)
}

watch(localProjectIds, val => {
  if (val.length === 0) {
    localProjects.value = []
    hasLoaded.value = false
    history.replaceState(null, '', window.location.pathname)
  } else {
    scheduleApply()
  }
}, { deep: true })

watch([localDateStart, localDateEnd], () => {
  if (localProjectIds.value.length > 0) scheduleApply()
  else syncUrl()
})

function clearExport() {
  if (debounceTimer) clearTimeout(debounceTimer)
  localProjects.value = []
  localProjectIds.value = []
  localDateStart.value = ''
  localDateEnd.value = ''
  hasLoaded.value = false
  history.replaceState(null, '', window.location.pathname)
}

function formatDate(dateStr: string) {
  const [y, m, d] = dateStr.split('-')
  return `${d} ${MONTHS_FR[Number.parseInt(m) - 1].slice(0, 3)}. ${y}`
}

function coverageLabel(v: number) {
  if (v === 100) return '1j'
  if (v === 50) return '½j'
  return `${v}%`
}
</script>

<template>
  <div class="px-6 py-8">
    <div class="mx-auto max-w-5xl space-y-6">
      <div>
        <h1 class="text-lg font-semibold">Exports</h1>
        <p class="text-sm text-muted">Prévisualisez et exportez vos activités</p>
      </div>

      <UCard>
        <div class="space-y-3">
          <div class="flex flex-wrap items-end gap-3">
            <div class="flex flex-col gap-1.5 min-w-65">
              <label class="text-xs font-medium text-muted">Projets</label>
              <USelectMenu
                v-model="localProjectIds"
                :items="projectOptions"
                valueKey="value"
                multiple
                placeholder="Sélectionner des projets…"
                class="min-w-65"
              >
                <template #default>
                  <span :class="localProjectIds.length === 0 ? 'text-muted' : 'text-default'">
                    {{ dropdownLabel }}
                  </span>
                </template>
              </USelectMenu>
            </div>

            <div class="flex items-end gap-2">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-medium text-muted">Du</label>
                <UInput v-model="localDateStart" type="date" />
              </div>
              <span class="pb-2 text-muted">—</span>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-medium text-muted">Au</label>
                <UInput v-model="localDateEnd" type="date" />
              </div>
            </div>

            <UButton
              v-if="hasLoaded || localProjectIds.length > 0 || localDateStart || localDateEnd"
              icon="i-lucide-rotate-ccw"
              label="Réinitialiser"
              color="neutral"
              variant="outline"
              :disabled="loading"
              @click="clearExport"
            />
          </div>

          <div v-if="selectedProjects.length > 0" class="flex flex-wrap gap-1.5">
            <span
              v-for="p in selectedProjects"
              :key="p.id"
              class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary"
            >
              {{ p.name }}
              <button type="button" class="ml-0.5 hover:text-primary/60 transition-colors" @click="removeProject(p.id)">
                <UIcon name="i-lucide-x" class="h-3 w-3" />
              </button>
            </span>
          </div>
        </div>
      </UCard>

      <p v-if="!hasLoaded && localProjectIds.length === 0" class="text-sm text-muted">
        Sélectionnez un ou plusieurs projets pour charger les activités.
      </p>

      <p v-else-if="hasLoaded && allEntries.length === 0" class="text-sm text-muted">
        Aucune activité pour les filtres sélectionnés.
      </p>

      <div v-else-if="hasLoaded" class="space-y-3">
        <div class="overflow-hidden rounded-lg border border-default">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-default bg-muted/40">
                <th class="px-4 py-2.5 text-left text-xs font-medium text-muted">Date</th>
                <th class="px-4 py-2.5 text-left text-xs font-medium text-muted">Projet</th>
                <th class="px-4 py-2.5 text-left text-xs font-medium text-muted">Client</th>
                <th class="px-4 py-2.5 text-left text-xs font-medium text-muted">Intitulé</th>
                <th class="px-4 py-2.5 text-right text-xs font-medium text-muted">Durée</th>
                <th class="px-4 py-2.5 text-right text-xs font-medium text-muted">Montant</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(entry, i) in allEntries"
                :key="i"
                class="border-b border-default last:border-0 hover:bg-elevated/40 transition-colors"
              >
                <td class="px-4 py-2.5 text-muted">{{ formatDate(entry.date) }}</td>
                <td class="px-4 py-2.5 font-medium">{{ entry.project_name }}</td>
                <td class="px-4 py-2.5 text-muted">{{ entry.client_name }}</td>
                <td class="px-4 py-2.5 text-muted">{{ entry.label || '—' }}</td>
                <td class="px-4 py-2.5 text-right font-medium">{{ coverageLabel(entry.day_coverage) }}</td>
                <td class="px-4 py-2.5 text-right">
                  {{
                    entry.daily_rate > 0 ? `${((entry.day_coverage / 100) * entry.daily_rate).toLocaleString('fr-FR', {
                      minimumFractionDigits: 0,
                      maximumFractionDigits: 0,
                    })} €` : '—'
                  }}
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="border-t border-default bg-muted/40">
                <td colspan="4" class="px-4 py-2.5 text-xs font-medium text-muted">Total</td>
                <td class="px-4 py-2.5 text-right text-sm font-semibold">
                  {{ totalDays % 1 === 0 ? totalDays : totalDays.toFixed(1) }}j
                </td>
                <td class="px-4 py-2.5 text-right text-sm font-semibold">
                  {{
                    totalCA > 0 ? `${totalCA.toLocaleString('fr-FR', {
                      minimumFractionDigits: 0,
                      maximumFractionDigits: 0,
                    })} €` : '—'
                  }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>

        <div class="flex justify-end">
          <UButtonGroup>
            <UButton
              as="a"
              :href="exportUrl"
              label="Exporter CSV"
              icon="i-lucide-download"
            />
            <UDropdownMenu :items="exportMenuItems" :content="{ align: 'end', side: 'top' }">
              <UButton icon="i-lucide-chevron-down" />
            </UDropdownMenu>
          </UButtonGroup>
        </div>
      </div>
    </div>
  </div>
</template>
