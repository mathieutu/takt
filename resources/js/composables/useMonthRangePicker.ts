import type { DateValue } from '@internationalized/date'
import { CalendarDate } from '@internationalized/date'
import { computed, type Ref, ref } from 'vue'
import { parseMonth, toYearMonth } from '@/utils/date'

type MonthRange = { from: string, to: string }

export const useMonthRangePicker = (from: Ref<string>, to: Ref<string>, onSelect?: (range: MonthRange) => void) => {
  const pickerOpen = ref(false)

  const yearMonthToCalendarDate = (yearMonth: string): CalendarDate => {
    const { year, month } = parseMonth(yearMonth)
    return new CalendarDate(year, month, 1)
  }

  const calendarValue = computed(() => ({
    start: yearMonthToCalendarDate(from.value),
    end: yearMonthToCalendarDate(to.value),
  }))

  const onRangeSelect = (value: { start: DateValue | undefined, end: DateValue | undefined } | null) => {
    if (!value?.start || !value?.end) return

    const range = { from: toYearMonth(value.start), to: toYearMonth(value.end) }

    if (onSelect) {
      onSelect(range)
    } else {
      from.value = range.from
      to.value = range.to
    }

    pickerOpen.value = false
  }

  const formatMonthLabel = (yearMonth: string, style: 'short' | 'long' = 'short'): string => {
    const { year, month } = parseMonth(yearMonth)
    return new Date(year, month - 1).toLocaleDateString('fr-FR', { month: style, year: 'numeric' })
  }

  const periodLabel = computed(() => `${formatMonthLabel(from.value)} – ${formatMonthLabel(to.value)}`)

  return { pickerOpen, calendarValue, onRangeSelect, formatMonthLabel, periodLabel }
}
