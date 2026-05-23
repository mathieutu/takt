<script setup lang="ts">
import type { PageProps } from '../types'
import { usePage } from '@inertiajs/vue3'
import { computed, onMounted, ref, watch } from 'vue'
import AppLayout from '../layouts/AppLayout.vue'

// @ts-expect-error Inertia v3 runtime accepts false to disable layout; types are incomplete
defineOptions({ layout: false })

const props = withDefaults(defineProps<{
  projectName?: string,
  clientName?: string,
  projectId?: number,
  currentYear?: number,
  currentMonth?: number,
  reports?: Report[],
}>(), {
  projectName: '',
  clientName: '',
  projectId: 0,
  currentYear: () => new Date().getFullYear(),
  currentMonth: () => new Date().getMonth() + 1,
  reports: () => [],
})
const page = usePage<PageProps>()
const isAuthenticated = computed(() => !!page.props.auth?.user)

type Report = {
  id: number,
  project_id: number,
  start_date: string,
  day_coverage: number,
  label: string,
  comments: string,
}

const displayYear = ref(props.currentYear)
const displayMonth = ref(props.currentMonth)
const viewReportOpen = ref(false)
const viewingReport = ref<Report | null>(null)

const MONTHS_FR = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']

const today = new Date()
const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`

const holidaysCache = new Map<number, Map<string, string>>()
const holidays = ref<Map<string, string>>(new Map())

async function loadHolidays(year: number) {
  if (holidaysCache.has(year)) {
    holidays.value = holidaysCache.get(year)!
    return
  }
  try {
    const res = await fetch(`https://calendrier.api.gouv.fr/jours-feries/metropole/${year}.json`)
    if (res.ok) {
      const data: Record<string, string> = await res.json()
      const map = new Map(Object.entries(data))
      holidaysCache.set(year, map)
      holidays.value = map
    }
  } catch { /* */
  }
}

onMounted(() => loadHolidays(displayYear.value))
watch(displayYear, year => loadHolidays(year))

const monthPrefix = computed(() =>
  `${displayYear.value}-${String(displayMonth.value).padStart(2, '0')}-`,
)

const daysInMonth = computed(() => {
  const y = displayYear.value
  const m = displayMonth.value
  const count = new Date(y, m, 0).getDate()
  const DAY_LETTERS = ['D', 'L', 'M', 'M', 'J', 'V', 'S']
  return Array.from({ length: count }, (_, i) => {
    const d = i + 1
    const dateStr = `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`
    const dow = new Date(y, m - 1, d).getDay()
    return { d, dateStr, isWeekend: dow === 0 || dow === 6, letter: DAY_LETTERS[dow] }
  })
})

const reportByKey = computed(() => {
  const map = new Map<string, Report>()
  for (const r of props.reports) {
    if (r.start_date.startsWith(monthPrefix.value)) {
      map.set(`${r.project_id}:${r.start_date}`, r)
    }
  }
  return map
})

const monthDays = computed(() =>
  props.reports
    .filter(r => r.start_date.startsWith(monthPrefix.value))
    .reduce((s, r) => s + r.day_coverage / 100, 0),
)

const viewingTitle = computed(() => {
  if (!viewingReport.value) return ''
  const day = viewingReport.value.start_date.slice(8, 10).replace(/^0/, '')
  const month = MONTHS_FR[Number.parseInt(viewingReport.value.start_date.slice(5, 7)) - 1]
  return `${day} ${month}`
})

function openViewReport(report: Report) {
  viewingReport.value = report
  viewReportOpen.value = true
}

function fmtDays(v: number) {
  return `${v % 1 === 0 ? v : v.toFixed(1)}j`
}

function coverageLabel(v: number) {
  if (v === 100) return '1j'
  if (v === 50) return '½j'
  return ''
}

function prevMonth() {
  if (displayMonth.value === 1) {
    displayMonth.value = 12
    displayYear.value--
  } else {
    displayMonth.value--
  }
}

function nextMonth() {
  if (displayMonth.value === 12) {
    displayMonth.value = 1
    displayYear.value++
  } else {
    displayMonth.value++
  }
}
</script>

<template>
  <!-- Connecté : AppLayout normal (header + sidebar) -->
  <AppLayout v-if="isAuthenticated">
    <div class="flex h-[calc(100vh-3.5rem)] flex-col overflow-hidden px-3 py-4 md:px-6 md:py-6">
      <div class="mb-4 flex items-center justify-between">
        <div>
          <h2 class="text-sm font-semibold text-default">{{ projectName }}</h2>
          <p class="text-xs text-muted">{{ clientName }} — CRA partagé</p>
        </div>
        <div class="flex shrink-0 items-center gap-2">
          <UButton icon="i-lucide-chevron-left" color="neutral" variant="ghost" size="xs" @click="prevMonth" />
          <span class="min-w-35 text-center text-sm font-medium text-default">{{
            MONTHS_FR[displayMonth - 1]
          }} {{ displayYear }}</span>
          <UButton
            icon="i-lucide-chevron-right" color="neutral" variant="ghost" size="xs"
            @click="nextMonth"
          />
        </div>
      </div>
      <div class="flex-1 min-h-0">
        <div class="overflow-y-auto rounded-md border border-default max-h-full">
          <table class="border-collapse" style="table-layout: fixed; width: max-content; min-width: 100%;">
            <colgroup>
              <col style="width: 160px; min-width: 160px;" />
              <col v-for="day in daysInMonth" :key="day.d" style="width: 56px; min-width: 56px;" />
            </colgroup>
            <thead>
              <tr>
                <th class="sticky left-0 top-0 z-30 border-b border-r border-default bg-default px-3 py-2 text-left text-xs font-medium text-muted">
                  Projet
                </th>
                <th
                  v-for="day in daysInMonth" :key="day.d"
                  class="sticky top-0 z-10 border-b border-r border-default px-0 py-1.5 text-center"
                  :class="[holidays.has(day.dateStr) || day.isWeekend ? 'bg-elevated' : 'bg-default', day.dateStr === todayStr ? 'bg-primary/15!' : '']"
                  :title="holidays.get(day.dateStr)"
                >
                  <div
                    class="text-xs font-semibold leading-none"
                    :class="day.dateStr === todayStr ? 'text-primary' : 'text-default'"
                  >
                    {{ day.d }}
                  </div>
                  <div
                    class="mt-0.5 text-[10px] leading-none"
                    :class="day.dateStr === todayStr ? 'text-primary' : 'text-muted'"
                  >
                    {{ day.letter }}
                  </div>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr class="group/row">
                <td class="sticky left-0 z-10 border-b border-r border-default bg-default px-3 py-2">
                  <div class="truncate text-sm font-medium text-default">{{ projectName }}</div>
                  <div class="truncate text-xs text-muted">{{ clientName }}</div>
                </td>
                <td
                  v-for="day in daysInMonth" :key="day.dateStr"
                  class="group/cell relative border-b border-r border-default select-none overflow-hidden cursor-default"
                  style="height: 52px;"
                  :class="[
                    !(reportByKey.get(`${projectId}:${day.dateStr}`)) && holidays.has(day.dateStr) ? 'bg-elevated' : '',
                    !(reportByKey.get(`${projectId}:${day.dateStr}`)) && !holidays.has(day.dateStr) && day.isWeekend ? 'bg-elevated' : '',
                    (reportByKey.get(`${projectId}:${day.dateStr}`)?.day_coverage ?? 0) >= 100 ? 'bg-primary/25' : '',
                    (reportByKey.get(`${projectId}:${day.dateStr}`)?.day_coverage ?? 0) > 0 && (reportByKey.get(`${projectId}:${day.dateStr}`)?.day_coverage ?? 0) < 100 ? 'bg-primary/10' : '',
                    day.dateStr === todayStr && !(reportByKey.get(`${projectId}:${day.dateStr}`)) ? 'ring-1 ring-inset ring-primary/50' : '',
                  ]"
                >
                  <span
                    v-if="reportByKey.get(`${projectId}:${day.dateStr}`)"
                    class="absolute bottom-2 left-0 right-0 text-center text-sm font-bold text-primary"
                  >
                    {{ coverageLabel(reportByKey.get(`${projectId}:${day.dateStr}`)!.day_coverage) }}
                  </span>
                  <button
                    v-if="reportByKey.get(`${projectId}:${day.dateStr}`) && (reportByKey.get(`${projectId}:${day.dateStr}`)!.label || reportByKey.get(`${projectId}:${day.dateStr}`)!.comments)"
                    type="button"
                    class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded opacity-0 transition-opacity hover:bg-primary/25 group-hover/cell:opacity-100"
                    @click.stop="openViewReport(reportByKey.get(`${projectId}:${day.dateStr}`)!)"
                  >
                    <UIcon name="i-lucide-eye" class="h-3 w-3 text-primary" />
                  </button>
                  <span
                    v-if="reportByKey.get(`${projectId}:${day.dateStr}`)?.label || reportByKey.get(`${projectId}:${day.dateStr}`)?.comments"
                    class="absolute left-1.5 top-1.5 h-1.5 w-1.5 rounded-full bg-primary/70 transition-opacity group-hover/cell:opacity-0"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="mt-auto shrink-0 pt-3 flex items-center justify-between">
        <div class="hidden items-center gap-5 sm:flex">
          <div class="flex items-center gap-1.5">
            <span
              class="h-3 w-3 rounded-sm border border-default bg-primary/25"
            /><span
              class="text-xs text-muted"
            >1 jour</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span
              class="h-3 w-3 rounded-sm border border-default bg-primary/10"
            /><span
              class="text-xs text-muted"
            >½ jour</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span
              class="h-3 w-3 rounded-sm border border-default bg-elevated"
            /><span class="text-xs text-muted">Week-end</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span
              class="h-3 w-3 rounded-sm border border-default bg-elevated"
            /><span class="text-xs text-muted">Jour férié</span>
          </div>
        </div>
        <span class="text-sm text-muted ml-auto">{{ fmtDays(monthDays) }} ce mois</span>
      </div>
    </div>
  </AppLayout>

  <!-- Invité : layout minimal avec bouton de connexion -->
  <div v-else class="flex h-screen flex-col overflow-hidden bg-default">
    <header class="flex shrink-0 items-center justify-between border-b border-default px-4 py-3 md:px-6">
      <div>
        <p class="text-sm font-semibold text-default">{{ projectName }}</p>
        <p class="text-xs text-muted">{{ clientName }}</p>
      </div>
      <UButton as="a" href="/login" label="Se connecter" size="sm" />
    </header>
    <div class="flex flex-1 overflow-hidden">
      <main class="flex flex-1 flex-col overflow-hidden px-3 py-4 md:px-6 md:py-6">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-default">
            {{ MONTHS_FR[displayMonth - 1] }} {{
              displayYear
            }}
          </h2>
          <div class="flex shrink-0 items-center gap-2">
            <UButton
              icon="i-lucide-chevron-left" color="neutral" variant="ghost" size="xs"
              @click="prevMonth"
            />
            <UButton
              icon="i-lucide-chevron-right" color="neutral" variant="ghost" size="xs"
              @click="nextMonth"
            />
          </div>
        </div>
        <div class="flex-1 overflow-auto rounded-md border border-default">
          <table class="border-collapse" style="table-layout: fixed; width: max-content; min-width: 100%;">
            <colgroup>
              <col style="width: 160px; min-width: 160px;" />
              <col v-for="day in daysInMonth" :key="day.d" style="width: 56px; min-width: 56px;" />
            </colgroup>
            <thead>
              <tr>
                <th class="sticky left-0 top-0 z-30 border-b border-r border-default bg-default px-3 py-2 text-left text-xs font-medium text-muted">
                  Projet
                </th>
                <th
                  v-for="day in daysInMonth" :key="day.d"
                  class="sticky top-0 z-10 border-b border-r border-default px-0 py-1.5 text-center"
                  :class="[holidays.has(day.dateStr) || day.isWeekend ? 'bg-elevated' : 'bg-default', day.dateStr === todayStr ? 'bg-primary/15!' : '']"
                  :title="holidays.get(day.dateStr)"
                >
                  <div
                    class="text-xs font-semibold leading-none"
                    :class="day.dateStr === todayStr ? 'text-primary' : 'text-default'"
                  >
                    {{ day.d }}
                  </div>
                  <div
                    class="mt-0.5 text-[10px] leading-none"
                    :class="day.dateStr === todayStr ? 'text-primary' : 'text-muted'"
                  >
                    {{ day.letter }}
                  </div>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr class="group/row">
                <td class="sticky left-0 z-10 border-b border-r border-default bg-default px-3 py-2">
                  <div class="truncate text-sm font-medium text-default">{{ projectName }}</div>
                  <div class="truncate text-xs text-muted">{{ clientName }}</div>
                </td>
                <td
                  v-for="day in daysInMonth" :key="day.dateStr"
                  class="group/cell relative border-b border-r border-default select-none overflow-hidden cursor-default"
                  style="height: 52px;"
                  :class="[
                    !(reportByKey.get(`${projectId}:${day.dateStr}`)) && holidays.has(day.dateStr) ? 'bg-elevated' : '',
                    !(reportByKey.get(`${projectId}:${day.dateStr}`)) && !holidays.has(day.dateStr) && day.isWeekend ? 'bg-elevated' : '',
                    (reportByKey.get(`${projectId}:${day.dateStr}`)?.day_coverage ?? 0) >= 100 ? 'bg-primary/25' : '',
                    (reportByKey.get(`${projectId}:${day.dateStr}`)?.day_coverage ?? 0) > 0 && (reportByKey.get(`${projectId}:${day.dateStr}`)?.day_coverage ?? 0) < 100 ? 'bg-primary/10' : '',
                    day.dateStr === todayStr && !(reportByKey.get(`${projectId}:${day.dateStr}`)) ? 'ring-1 ring-inset ring-primary/50' : '',
                  ]"
                >
                  <span
                    v-if="reportByKey.get(`${projectId}:${day.dateStr}`)"
                    class="absolute bottom-2 left-0 right-0 text-center text-sm font-bold text-primary"
                  >
                    {{ coverageLabel(reportByKey.get(`${projectId}:${day.dateStr}`)!.day_coverage) }}
                  </span>
                  <button
                    v-if="reportByKey.get(`${projectId}:${day.dateStr}`) && (reportByKey.get(`${projectId}:${day.dateStr}`)!.label || reportByKey.get(`${projectId}:${day.dateStr}`)!.comments)"
                    type="button"
                    class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded opacity-0 transition-opacity hover:bg-primary/25 group-hover/cell:opacity-100"
                    @click.stop="openViewReport(reportByKey.get(`${projectId}:${day.dateStr}`)!)"
                  >
                    <UIcon name="i-lucide-eye" class="h-3 w-3 text-primary" />
                  </button>
                  <span
                    v-if="reportByKey.get(`${projectId}:${day.dateStr}`)?.label || reportByKey.get(`${projectId}:${day.dateStr}`)?.comments"
                    class="absolute left-1.5 top-1.5 h-1.5 w-1.5 rounded-full bg-primary/70 transition-opacity group-hover/cell:opacity-0"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="mt-3 flex items-center justify-between">
          <div class="hidden items-center gap-5 sm:flex">
            <div class="flex items-center gap-1.5">
              <span
                class="h-3 w-3 rounded-sm border border-default bg-primary/25"
              /><span
                class="text-xs text-muted"
              >1 jour</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span
                class="h-3 w-3 rounded-sm border border-default bg-primary/10"
              /><span
                class="text-xs text-muted"
              >½ jour</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span
                class="h-3 w-3 rounded-sm border border-default bg-elevated"
              /><span
                class="text-xs text-muted"
              >Week-end</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span
                class="h-3 w-3 rounded-sm border border-default bg-elevated"
              /><span
                class="text-xs text-muted"
              >Jour férié</span>
            </div>
          </div>
          <span class="text-sm text-muted ml-auto">{{ fmtDays(monthDays) }} ce mois</span>
        </div>
      </main>
    </div>
  </div>

  <UModal v-model:open="viewReportOpen" :title="viewingTitle">
    <template #body>
      <div class="space-y-4">
        <div v-if="viewingReport?.label" class="flex flex-col gap-1">
          <p class="text-xs font-medium text-muted">Titre</p>
          <p class="text-sm">{{ viewingReport.label }}</p>
        </div>
        <div v-if="viewingReport?.comments" class="flex flex-col gap-1">
          <p class="text-xs font-medium text-muted">Description</p>
          <p class="text-sm whitespace-pre-wrap">{{ viewingReport.comments }}</p>
        </div>
      </div>
    </template>
    <template #footer="{ close }">
      <div class="flex justify-end">
        <UButton label="Fermer" color="neutral" variant="outline" @click="close" />
      </div>
    </template>
  </UModal>
</template>
