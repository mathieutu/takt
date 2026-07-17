<script setup lang="ts">
import type { ProjectWithBilling } from '@/types/billing'
import { useToast } from '@nuxt/ui/composables'
import { computed, ref } from 'vue'
import { useMonthRangePicker } from '@/composables/useMonthRangePicker'
import { today, toYearMonth } from '@/utils/date'
import { exportMethod as exportClientBilling } from '@/wayfinder/routes/clients/billing'
import { exportMethod as exportSharedBilling } from '@/wayfinder/routes/shares/billing'

const props = defineProps<{
  projects: ProjectWithBilling[],
  clientId?: string,
  shareToken?: string,
  isShared: boolean,
}>()

const open = defineModel<boolean>('open', { required: true })

const toast = useToast()

// ── Default selection ─────────────────────────────────────────────────────────

const lastInvoiceDate = (project: ProjectWithBilling): string | null => {
  const dates = project.months.flatMap(month => month.invoices).map(invoice => invoice.created_at).toSorted()
  return dates.at(-1) ?? null
}

const oldestMonthWithData = (projects: ProjectWithBilling[]): string | null => {
  const months = projects.flatMap(project => project.months.map(month => month.month)).toSorted()
  return months[0] ?? null
}

const defaultProjectIds = (): string[] => {
  const projectsToInvoice = props.projects.filter(project => project.to_invoice > 0)
  return (projectsToInvoice.length > 0 ? projectsToInvoice : props.projects).map(project => project.id)
}

const defaultFromMonth = (defaultProjects: ProjectWithBilling[]): string => {
  const invoiceDates = defaultProjects
    .map(lastInvoiceDate)
    .filter((date): date is string => date !== null)
    .toSorted()

  if (invoiceDates.length > 0) return invoiceDates[0]!.slice(0, 7)

  return oldestMonthWithData(defaultProjects) ?? toYearMonth(today)
}

const selectedProjectIds = ref<string[]>(defaultProjectIds())
const rangeFrom = ref(defaultFromMonth(props.projects.filter(project => selectedProjectIds.value.includes(project.id))))
const rangeTo = ref(toYearMonth(today))

// ── Project selection ─────────────────────────────────────────────────────────

const projectItems = computed(() => props.projects.map(project => ({
  label: project.is_inactive ? `${project.name} (inactif)` : project.name,
  value: project.id,
})))

// ── Month range picker ─────────────────────────────────────────────────────────

const { pickerOpen, calendarValue, onRangeSelect, periodLabel } = useMonthRangePicker(rangeFrom, rangeTo)

// ── Export ─────────────────────────────────────────────────────────────────────

const isExporting = ref(false)

const exportUrl = computed(() => {
  const query = { project_ids: selectedProjectIds.value, from: rangeFrom.value, to: rangeTo.value }
  return props.isShared
    ? exportSharedBilling.url(props.shareToken!, { query })
    : exportClientBilling.url(props.clientId!, { query })
})

const extractFilename = (contentDisposition: string | null): string | null =>
  contentDisposition?.match(/filename="(.+)"/)?.[1] ?? null

const exportPdf = async () => {
  isExporting.value = true

  try {
    const response = await fetch(exportUrl.value)

    if (!response.ok) {
      const body = await response.json().catch(() => null)
      toast.add({ title: body?.message ?? 'Échec de la génération du PDF', color: 'error' })
      return
    }

    const blob = await response.blob()
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = extractFilename(response.headers.get('content-disposition')) ?? 'export.pdf'
    link.click()
    URL.revokeObjectURL(url)
    open.value = false
  } catch {
    toast.add({ title: 'Échec de la génération du PDF', color: 'error' })
  } finally {
    isExporting.value = false
  }
}
</script>

<template>
  <UModal v-model:open="open" title="Exporter la facturation">
    <template #body>
      <div class="space-y-4">
        <UCheckboxGroup v-model="selectedProjectIds" legend="Projets" :items="projectItems" />

        <div>
          <p class="mb-1.5 text-sm font-medium">Période</p>
          <UPopover v-model:open="pickerOpen">
            <UButton
              :label="periodLabel"
              icon="i-lucide-calendar-days"
              color="neutral"
              variant="outline"
              size="sm"
            />
            <template #content>
              <UCalendar
                type="month"
                range
                size="sm"
                locale="fr-FR"
                :modelValue="calendarValue"
                class="p-2"
                @update:modelValue="onRangeSelect"
              />
            </template>
          </UPopover>
        </div>
      </div>
    </template>
    <template #footer="{ close }">
      <div class="flex justify-end gap-2">
        <UButton label="Annuler" color="neutral" variant="outline" @click="close" />
        <UButton
          label="Exporter"
          icon="i-lucide-download"
          :loading="isExporting"
          :disabled="!selectedProjectIds.length"
          @click="exportPdf"
        />
      </div>
    </template>
  </UModal>
</template>
