<script setup lang="ts">
import type { MonthRangePreset } from '@/composables/useMonthRangePicker'
import type { RouteDefinition } from '@/wayfinder'
import { router } from '@inertiajs/vue3'
import { CalendarDate } from '@internationalized/date'
import { computed } from 'vue'
import { useMonthRangePicker } from '@/composables/useMonthRangePicker'
import { parseMonth, toYearMonth } from '@/utils/date'

type OptionalMonthRange = { from?: string, to?: string }

const props = defineProps<{
  from: string | null,
  to: string | null,
  presets: MonthRangePreset[],
  hrefFor: (range: OptionalMonthRange) => RouteDefinition<'get'>,
  // Shown instead of the period when no range is selected; the prev/next arrows are hidden then.
  emptyLabel?: string,
}>()

const { pickerOpen, calendarValue, onRangeSelect, periodLabel } = useMonthRangePicker(
  computed(() => props.from),
  computed(() => props.to),
  range => router.visit(props.hrefFor(range), { preserveScroll: true }),
)

const shiftPeriod = (months: number): OptionalMonthRange | null => {
  if (!props.from || !props.to) return null

  const shift = (yearMonth: string) => {
    const { year, month } = parseMonth(yearMonth)
    return toYearMonth(new CalendarDate(year, month, 1).add({ months }))
  }

  return { from: shift(props.from), to: shift(props.to) }
}

const prevPeriod = computed(() => shiftPeriod(-1))
const nextPeriod = computed(() => shiftPeriod(1))

const isActivePreset = (preset: MonthRangePreset) =>
  (preset.from ?? null) === props.from && (preset.to ?? null) === props.to
</script>

<template>
  <div class="flex items-center gap-1">
    <UButton
      v-if="prevPeriod"
      icon="i-lucide-chevron-left"
      color="neutral"
      variant="ghost"
      size="sm"
      :href="hrefFor(prevPeriod)"
      preserveScroll
    />
    <UPopover v-model:open="pickerOpen">
      <UButton
        :label="periodLabel || emptyLabel"
        icon="i-lucide-calendar-days"
        color="neutral"
        variant="outline"
        size="sm"
        class="w-44 justify-center"
        :ui="{ label: 'truncate' }"
      />
      <template #content>
        <div class="flex items-center">
          <div class="flex flex-col gap-0.5 border-r border-default p-2">
            <UButton
              v-for="preset in presets"
              :key="preset.label"
              :label="preset.label"
              :href="hrefFor({ from: preset.from, to: preset.to })"
              color="neutral"
              :variant="isActivePreset(preset) ? 'soft' : 'ghost'"
              size="sm"
              class="justify-start"
              preserveScroll
            />
          </div>
          <UCalendar
            type="month"
            range
            size="sm"
            locale="fr-FR"
            :modelValue="calendarValue"
            class="p-2"
            @update:modelValue="onRangeSelect"
          />
        </div>
      </template>
    </UPopover>
    <UButton
      v-if="nextPeriod"
      icon="i-lucide-chevron-right"
      color="neutral"
      variant="ghost"
      size="sm"
      :href="hrefFor(nextPeriod)"
      preserveScroll
    />
  </div>
</template>
