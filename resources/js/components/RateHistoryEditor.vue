<script setup lang="ts">
import { computed } from 'vue'
import DateInput from '@/components/DateInput.vue'
import { formatDate, today } from '@/utils/date'
import { formatCurrency } from '@/utils/number'

const props = defineProps<{
  history: Record<string, number | null> | null,
  unit: string,
  modelValue: number | null,
  date: string,
  error?: string,
  dateError?: string,
}>()

const emit = defineEmits<{
  'update:modelValue': [number | null],
  'update:date': [string],
}>()

const entries = computed(() =>
  Object.entries(props.history ?? {})
    .map(([date, amount]) => ({ date, amount }))
    .toSorted((a, b) => b.date.localeCompare(a.date)),
)

// The entries are sorted latest-first, so the current one is the first whose date has come to pass
// — matches Project::getDailyRateForDate(today())/getMonthlyBudgetForDate(today()) on the backend.
const currentDate = computed(() => entries.value.find(e => e.date <= today.toString())?.date ?? null)

// `val` can come through as an empty string (or NaN) rather than null when the field is cleared —
// checked explicitly rather than a plain falsy check, which would also catch a genuine `0` (a
// meaningful value here, not "no value": see ProjectController::update/setMonthlyBudgetFrom).
const setAmount = (val: number | null) => {
  if (val === null || (val as unknown) === '' || Number.isNaN(val)) {
    emit('update:modelValue', null)
    return
  }

  emit('update:modelValue', Math.round(val * 100))
}
</script>

<template>
  <div class="space-y-2">
    <ul v-if="entries.length" class="divide-y divide-default rounded-md border border-default">
      <li
        v-for="entry in entries"
        :key="entry.date"
        class="flex items-center justify-between gap-2 px-3 py-2 text-sm"
        :class="entry.date === currentDate ? 'font-semibold' : 'text-muted'"
      >
        <span>
          <template v-if="entry.amount === null">Sans limite</template>
          <template v-else>{{ formatCurrency(entry.amount) }}{{ unit }}</template>
        </span>
        <span>{{ formatDate(entry.date, true) }}</span>
      </li>
    </ul>

    <div class="flex items-start gap-2">
      <UFormField class="flex-1" :error="error">
        <UInput
          type="number"
          min="0"
          step="0.01"
          placeholder="Nouvelle valeur"
          class="w-full"
          :modelValue="modelValue !== null ? modelValue / 100 : null"
          @update:modelValue="setAmount"
        />
      </UFormField>
      <UFormField class="w-36" hint="Date d'effet" :error="dateError">
        <DateInput :modelValue="date" @update:modelValue="(val: string) => emit('update:date', val)" />
      </UFormField>
    </div>
  </div>
</template>
