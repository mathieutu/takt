<script setup lang="ts">
import type { ComponentPublicInstance } from 'vue'
import { CalendarDate } from '@internationalized/date'
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue'
import { today } from '@/utils/date.ts'

const props = defineProps<{
  modelValue?: string | null,
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string],
}>()

const inputDate = useTemplateRef('inputDate')
const calendarOpen = ref(false)
const prevModelValue = ref<string | null>(null)

const toCalendarDate = (dateStr?: string | null): CalendarDate | null => {
  if (!dateStr) return null
  const [y, m, d] = dateStr.split('-').map(Number)
  if (!y || !m || !d) return null
  return new CalendarDate(y, m, d)
}

const toDateString = (date: { year: number, month: number, day: number } | null): string => {
  if (!date) return ''
  return `${date.year}-${String(date.month).padStart(2, '0')}-${String(date.day).padStart(2, '0')}`
}

const calendarValue = computed<CalendarDate | null>({
  get: () => toCalendarDate(props.modelValue),
  set: val => emit('update:modelValue', toDateString(val)),
})

watch(calendarValue, val => {
  if (val && calendarOpen.value) {
    calendarOpen.value = false
  }
})

const getSegmentValue = (segmentName: string): number | null => {
  const refs = (inputDate.value as any)?.inputsRef as ComponentPublicInstance[] | undefined
  const el = refs
    ?.map(r => r?.$el as HTMLElement | undefined)
    .find(el => el?.dataset?.segment === segmentName || el?.getAttribute('data-reka-date-field-segment') === segmentName)
  if (!el || el.hasAttribute('data-placeholder')) return null
  const val = Number.parseInt(el.textContent?.trim() || '')
  return Number.isNaN(val) ? null : val
}

const tryAutoComplete = async () => {
  if (calendarValue.value || prevModelValue.value) return

  await nextTick()

  const day = getSegmentValue('day')
  const month = getSegmentValue('month')
  const year = getSegmentValue('year')

  if (day === null && month === null && year === null) return

  emit('update:modelValue', toDateString({ day: day ?? today.day, month: month ?? today.month, year: year ?? today.year }))
}

const onFocusin = (e: FocusEvent) => {
  const relatedTarget = e.relatedTarget as Element | null

  if (!relatedTarget || !(e.currentTarget as Element).contains(relatedTarget)) {
    prevModelValue.value = props.modelValue || null
    return
  }

  tryAutoComplete()
}

const onFocusout = (e: FocusEvent) => {
  if (calendarOpen.value) return

  const relatedTarget = e.relatedTarget as Element | null
  if ((e.currentTarget as Element).contains(relatedTarget)) return

  tryAutoComplete()
}
</script>

<template>
  <div @focusin="onFocusin" @focusout="onFocusout">
    <UInputDate
      ref="inputDate"
      v-model="calendarValue"
      locale="fr-FR"
      class="w-full"
    >
      <template #trailing>
        <UPopover v-model:open="calendarOpen" :reference="(inputDate as any)?.inputsRef?.[3]?.$el">
          <UButton
            color="neutral"
            variant="link"
            size="sm"
            icon="i-lucide-calendar"
            aria-label="Choisir une date"
            class="px-0"
          />
          <template #content>
            <UCalendar v-model="calendarValue" class="p-2" />
          </template>
        </UPopover>
      </template>
    </UInputDate>
  </div>
</template>
