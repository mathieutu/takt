<script setup lang="ts">
import { CalendarDate } from '@internationalized/date'
import { computed, ref, watch } from 'vue'
import { today } from '@/utils/date.ts'

const props = defineProps<{
  modelValue?: string | null,
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string],
}>()

const calendarOpen = ref(false)

const toDisplay = (dateStr?: string | null): string => {
  if (!dateStr) return ''
  const [y, m, d] = dateStr.split('-')
  if (!y || !m || !d) return ''
  return `${d}/${m}/${y}`
}

const parseInput = (raw: string): string | null => {
  const trimmed = raw.trim()
  if (!trimmed) return ''

  const parts = trimmed.split(/[/\-.]/)

  const day = parseInt(parts[0] || '')
  if (!day || day < 1 || day > 31) return null

  const month = parts[1] ? (parseInt(parts[1]) || today.month) : today.month
  if (month < 1 || month > 12) return null

  let year = parts[2] ? (parseInt(parts[2]) || today.year) : today.year
  if (year > 0 && year < 100) year += 2000

  const date = new Date(year, month - 1, day)
  if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) return null

  return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`
}

const displayValue = ref(toDisplay(props.modelValue))

watch(() => props.modelValue, val => {
  displayValue.value = toDisplay(val)
})

const calendarValue = computed<CalendarDate | null>({
  get: () => {
    if (!props.modelValue) return null
    const [y, m, d] = props.modelValue.split('-').map(Number)
    if (!y || !m || !d) return null
    return new CalendarDate(y, m, d)
  },
  set: val => {
    if (!val) return
    const str = `${val.year}-${String(val.month).padStart(2, '0')}-${String(val.day).padStart(2, '0')}`
    emit('update:modelValue', str === props.modelValue ? '' : str)
    calendarOpen.value = false
  },
})

const onBlur = () => {
  const parsed = parseInput(displayValue.value)
  if (parsed === null) {
    displayValue.value = ''
    emit('update:modelValue', '')
    return
  }
  displayValue.value = toDisplay(parsed)
  emit('update:modelValue', parsed)
}
</script>

<template>
  <UInput
    v-model="displayValue"
    placeholder="JJ/MM/AAAA"
    class="w-full"
    @blur="onBlur"
  >
    <template #trailing>
      <UPopover v-model:open="calendarOpen">
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
  </UInput>
</template>
