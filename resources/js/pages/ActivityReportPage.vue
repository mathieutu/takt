<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3'
import { computed, nextTick, ref, watch } from 'vue'
import { store as storeReport } from '@/wayfinder/routes/projects/reports'
import { destroy as destroyReport, update as updateReport } from '@/wayfinder/routes/reports'

const props = withDefaults(defineProps<{
  currentYear?: number,
  currentMonth?: number,
  projects?: Project[],
  reports?: Report[],
}>(), {
  currentYear: () => new Date().getFullYear(),
  currentMonth: () => new Date().getMonth() + 1,
  projects: () => [],
  reports: () => [],
})
const createHttp = useHttp({ start_date: '', day_coverage: 0 })
const updateCovHttp = useHttp({ day_coverage: 0 })
const destroyHttp = useHttp()
const editHttp = useHttp({ label: '', comments: '' })

type Project = { id: number, name: string, client_name: string, daily_rate: number }
type Report = {
  id: number,
  project_id: number,
  start_date: string,
  day_coverage: number,
  label: string,
  comments: string,
}

const localReports = ref<Report[]>([...props.reports])
const displayYear = ref(props.currentYear)
const displayMonth = ref(props.currentMonth)
const viewReportOpen = ref(false)
const editReportOpen = ref(false)
const editingReport = ref<Report | null>(null)
const editForm = ref({ label: '', comments: '' })
const viewingReport = ref<Report | null>(null)

const MONTHS_FR = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']

const today = new Date()
const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`

const holidaysCache = new Map<number, Map<string, string>>()
const holidays = ref<Map<string, string>>(new Map())
const tableScrollRef = ref<HTMLElement | null>(null)

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
  } catch {
    // en cas d'échec réseau, on reste avec une map vide
  }
}

function centerTodayColumn(behavior: ScrollBehavior = 'auto') {
  const isCurrentMonthDisplayed =
    displayYear.value === today.getFullYear() && displayMonth.value === today.getMonth() + 1

  if (!isCurrentMonthDisplayed || !tableScrollRef.value) {
    return
  }

  const todayColumn = tableScrollRef.value.querySelector<HTMLElement>(`#day-col-${todayStr}`)

  if (!todayColumn) {
    return
  }

  todayColumn.scrollIntoView({
    behavior,
    block: 'nearest',
    inline: 'center',
  })
}

watch(displayYear, year => loadHolidays(year), { immediate: true })
watch([displayYear, displayMonth], async () => {
  await nextTick()
  centerTodayColumn('smooth')
}, { immediate: true })

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
  for (const r of localReports.value) {
    if (r.start_date.startsWith(monthPrefix.value)) {
      map.set(`${r.project_id}:${r.start_date}`, r)
    }
  }
  return map
})

const projectStats = computed(() =>
  (props.projects ?? []).map(p => {
    const days = localReports.value
      .filter(r => r.project_id === p.id && r.start_date.startsWith(monthPrefix.value))
      .reduce((s, r) => s + r.day_coverage / 100, 0)
    return { project: p, days, ca: days * p.daily_rate }
  }),
)

const projectStatsById = computed(() =>
  new Map(projectStats.value.map(projectStat => [projectStat.project.id, projectStat])),
)

const totalDays = computed(() => projectStats.value.reduce((s, ps) => s + ps.days, 0))
const totalCa = computed(() => projectStats.value.reduce((s, ps) => s + ps.ca, 0))

function fmtDays(v: number) {
  return `${v % 1 === 0 ? v : v.toFixed(1)}j`
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

async function clickDay(projectId: number, dateStr: string) {
  const existing = localReports.value.find(r => r.project_id === projectId && r.start_date === dateStr) ?? null

  if (existing && existing.id < 0) return

  if (!existing) {
    const tempId = -Date.now()
    localReports.value.push({
      id: tempId,
      project_id: projectId,
      start_date: dateStr,
      day_coverage: 100,
      label: '',
      comments: '',
    })
    createHttp.start_date = dateStr
    createHttp.day_coverage = 100
    const created = await createHttp.post(storeReport(projectId).url)
    const idx = localReports.value.findIndex(r => r.id === tempId)
    if (created && idx !== -1) {
      localReports.value[idx] = {
        id: created.id,
        project_id: projectId,
        start_date: (created.start_date ?? dateStr).slice(0, 10),
        day_coverage: created.day_coverage ?? 100,
        label: created.label ?? '',
        comments: created.comments ?? '',
      }
    } else if (idx !== -1) {
      localReports.value.splice(idx, 1)
    }
  } else if (existing.day_coverage > 50) {
    const idx = localReports.value.findIndex(r => r.id === existing.id)
    if (idx !== -1) localReports.value[idx] = { ...localReports.value[idx], day_coverage: 50 }
    updateCovHttp.day_coverage = 50
    updateCovHttp.put(updateReport(existing.id).url, {
      onError: () => {
        const idx2 = localReports.value.findIndex(r => r.id === existing.id)
        if (idx2 !== -1) localReports.value[idx2] = { ...localReports.value[idx2], day_coverage: 100 }
      },
    })
  } else {
    localReports.value = localReports.value.filter(r => r.id !== existing.id)
    destroyHttp.delete(destroyReport(existing.id).url, {
      onError: () => localReports.value.push(existing),
    })
  }
}

function coverageLabel(v: number) {
  if (v === 100) return '1'
  if (v === 50) return '½'
  if (v === 33) return '⅓'
  return ''
}

function openEdit(report: Report) {
  editingReport.value = report
  editForm.value = { label: report.label ?? '', comments: report.comments ?? '' }
  editReportOpen.value = true
}

function openViewReport(report: Report) {
  viewingReport.value = report
  viewReportOpen.value = true
}

const viewingTitle = computed(() => {
  if (!viewingReport.value) return ''
  const day = viewingReport.value.start_date.slice(8, 10).replace(/^0/, '')
  const month = MONTHS_FR[Number.parseInt(viewingReport.value.start_date.slice(5, 7)) - 1]
  return `${day} ${month}`
})

const editingTitle = computed(() => {
  if (!editingReport.value) return ''
  const day = editingReport.value.start_date.slice(8, 10).replace(/^0/, '')
  const month = MONTHS_FR[Number.parseInt(editingReport.value.start_date.slice(5, 7)) - 1]
  return `${day} ${month}`
})

async function saveEdit() {
  if (!editingReport.value || editingReport.value.id < 0) return
  const report = editingReport.value
  editHttp.label = editForm.value.label
  editHttp.comments = editForm.value.comments
  const updated = await editHttp.put(updateReport(report.id).url)
  if (updated) {
    const idx = localReports.value.findIndex(r => r.id === report.id)
    if (idx !== -1) {
      localReports.value[idx] = {
        ...localReports.value[idx],
        label: updated.label ?? '',
        comments: updated.comments ?? '',
        day_coverage: updated.day_coverage ?? report.day_coverage,
      }
    }
    editingReport.value = null
    editReportOpen.value = false
  }
}
</script>

<template>
  <div class="flex h-[calc(100vh-3.5rem)] flex-col overflow-hidden bg-default">
    <div class="flex flex-1 overflow-hidden">
      <main class="flex flex-1 flex-col overflow-hidden px-3 py-4 md:px-6 md:py-6">
        <div class="mb-4 shrink-0 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <h2 class="text-sm font-semibold text-default">Compte-rendu d'activité</h2>
            <span class="text-xs text-muted">{{ fmtDays(totalDays) }} ({{
              totalCa.toLocaleString('fr-FR')
            }} €)</span>
          </div>
          <div class="flex shrink-0 items-center gap-2">
            <UButton
              icon="i-lucide-chevron-left" color="neutral" variant="ghost" size="xs"
              @click="prevMonth"
            />
            <span class="min-w-35 text-center text-sm font-medium text-default">
              {{ MONTHS_FR[displayMonth - 1] }} {{ displayYear }}
            </span>
            <UButton
              icon="i-lucide-chevron-right" color="neutral" variant="ghost" size="xs"
              @click="nextMonth"
            />
          </div>
        </div>

        <div class="flex-1 min-h-0">
          <div ref="tableScrollRef" class="overflow-auto rounded-md border border-default max-h-full">
            <table
              class="border-collapse"
              style="table-layout: fixed; width: max-content; min-width: 100%;"
            >
              <colgroup>
                <col style="width: 220px; min-width: 220px;" />
                <col v-for="day in daysInMonth" :key="day.d" style="width: 56px; min-width: 56px;" />
              </colgroup>
              <thead>
                <tr>
                  <th class="sticky left-0 top-0 z-30 border-b border-r border-default bg-default px-3 py-2 text-left text-xs font-medium text-muted">
                    Projet
                  </th>
                  <th
                    v-for="day in daysInMonth" :id="`day-col-${day.dateStr}`" :key="day.d"
                    class="sticky top-0 z-10 border-b border-r border-default px-0 py-1.5 text-center"
                    :class="[
                      holidays.has(day.dateStr) || day.isWeekend ? 'bg-elevated' : 'bg-default',
                      day.dateStr === todayStr ? 'bg-primary/15!' : '',
                    ]"
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
                <tr v-if="!projects?.length">
                  <td
                    :colspan="daysInMonth.length + 1"
                    class="px-4 py-8 text-center text-sm text-muted"
                  >
                    Aucun projet disponible.
                  </td>
                </tr>
                <tr v-for="project in projects" :key="project.id" class="group/row">
                  <td class="sticky left-0 z-10 border-b border-r border-default bg-default px-3 py-2">
                    <div class="truncate text-sm font-medium text-default">{{ project.name }}</div>
                    <div class="truncate text-xs text-muted flex items-center gap-1">
                      <span class="flex-1">{{ project.client_name }}</span>
                      <span>{{
                        fmtDays(projectStatsById.get(project.id)?.days ?? 0)
                      }} ({{
                        (projectStatsById.get(project.id)?.ca ?? 0).toLocaleString('fr-FR')
                      }} €)</span>
                    </div>
                  </td>
                  <td
                    v-for="day in daysInMonth" :key="day.dateStr"
                    class="group/cell relative border-b border-r border-default transition-colors select-none overflow-hidden cursor-pointer"
                    style="height: 52px;"
                    :class="[
                      !(reportByKey.get(`${project.id}:${day.dateStr}`)) && holidays.has(day.dateStr) ? 'bg-elevated' : '',
                      !(reportByKey.get(`${project.id}:${day.dateStr}`)) && !holidays.has(day.dateStr) && day.isWeekend ? 'bg-elevated' : '',
                      (reportByKey.get(`${project.id}:${day.dateStr}`)?.day_coverage ?? 0) >= 100 ? 'bg-primary/25 hover:bg-primary/30' : '',
                      (reportByKey.get(`${project.id}:${day.dateStr}`)?.day_coverage ?? 0) > 0 && (reportByKey.get(`${project.id}:${day.dateStr}`)?.day_coverage ?? 0) < 100 ? 'bg-primary/10 hover:bg-primary/15' : '',
                      !(reportByKey.get(`${project.id}:${day.dateStr}`)) ? 'hover:bg-elevated/60' : '',
                      day.dateStr === todayStr && !(reportByKey.get(`${project.id}:${day.dateStr}`)) ? 'ring-1 ring-inset ring-primary/50' : '',
                    ]"
                    @click="clickDay(project.id, day.dateStr)"
                  >
                    <span
                      v-if="reportByKey.get(`${project.id}:${day.dateStr}`)"
                      class="absolute bottom-2 left-0 right-0 text-center text-sm font-bold text-primary"
                    >
                      {{
                        coverageLabel(reportByKey.get(`${project.id}:${day.dateStr}`)!.day_coverage)
                      }}
                    </span>
                    <button
                      v-if="reportByKey.get(`${project.id}:${day.dateStr}`) && reportByKey.get(`${project.id}:${day.dateStr}`)!.id > 0"
                      type="button"
                      class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded opacity-0 transition-opacity hover:bg-primary/25 group-hover/cell:opacity-100"
                      @click.stop="openEdit(reportByKey.get(`${project.id}:${day.dateStr}`)!)"
                    >
                      <UIcon name="i-lucide-pencil" class="h-3 w-3 text-primary" />
                    </button>
                    <button
                      v-else-if="reportByKey.get(`${project.id}:${day.dateStr}`) && (reportByKey.get(`${project.id}:${day.dateStr}`)!.label || reportByKey.get(`${project.id}:${day.dateStr}`)!.comments)"
                      type="button"
                      class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded opacity-0 transition-opacity hover:bg-primary/25 group-hover/cell:opacity-100"
                      @click.stop="openViewReport(reportByKey.get(`${project.id}:${day.dateStr}`)!)"
                    >
                      <UIcon name="i-lucide-eye" class="h-3 w-3 text-primary" />
                    </button>
                    <span
                      v-if="reportByKey.get(`${project.id}:${day.dateStr}`)?.label || reportByKey.get(`${project.id}:${day.dateStr}`)?.comments"
                      class="absolute left-1.5 top-1.5 h-1.5 w-1.5 rounded-full bg-primary/70 transition-opacity group-hover/cell:opacity-0"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div><!-- end flex-1 min-h-0 -->
        <div class="mt-auto shrink-0">
          <div class="pt-3 hidden items-center gap-5 sm:flex">
            <div class="flex items-center gap-1.5">
              <span class="h-3 w-3 rounded-sm border border-default bg-primary/25" />
              <span class="text-xs text-muted">1 jour</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="h-3 w-3 rounded-sm border border-default bg-primary/10" />
              <span class="text-xs text-muted">½ jour</span>
            </div>
            <div class="flex items-center gap-1.5">
              <span class="h-3 w-3 rounded-sm border border-default bg-elevated" />
              <span class="text-xs text-muted">Week-ends et jours fériés</span>
            </div>
          </div>
          <div class="pt-2 flex items-center justify-between border-t border-default md:hidden">
            <span class="text-sm text-muted">{{ fmtDays(totalDays) }} saisis</span>
            <span class="text-sm font-semibold text-default">{{ totalCa.toLocaleString('fr-FR') }} €</span>
          </div>
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

  <UModal v-model:open="editReportOpen" :title="editingTitle">
    <template #body>
      <div class="space-y-4">
        <UFormField label="Titre">
          <UInput
            v-model="editForm.label" type="text" placeholder="Ex : Développement feature X"
            class="w-full"
          />
        </UFormField>
        <UFormField label="Description">
          <UTextarea
            v-model="editForm.comments" :rows="3" placeholder="Détails de l'activité…"
            class="w-full"
          />
        </UFormField>
      </div>
    </template>
    <template #footer="{ close }">
      <div class="flex justify-end gap-2">
        <UButton label="Annuler" color="neutral" variant="outline" @click="close" />
        <UButton label="Enregistrer" @click="saveEdit" />
      </div>
    </template>
  </UModal>
</template>
