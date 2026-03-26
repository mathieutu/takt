<script setup lang="ts">
defineProps<{
  modelValue?: boolean
  disabled?: boolean
  label?: string
}>()

defineEmits<{
  'update:modelValue': [value: boolean]
}>()
</script>

<template>
  <label class="inline-flex items-center gap-2 cursor-pointer">
    <button
      type="button"
      role="switch"
      :aria-checked="modelValue"
      :disabled="disabled"
      :class="[
        'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
        modelValue ? 'bg-primary' : 'bg-input',
      ]"
      @click="$emit('update:modelValue', !modelValue)"
    >
      <span
        :class="[
          'pointer-events-none block h-4 w-4 rounded-full bg-background shadow-sm ring-0 transition-transform',
          modelValue ? 'translate-x-4' : 'translate-x-0',
        ]"
      />
    </button>
    <span v-if="label" class="text-sm text-foreground">{{ label }}</span>
  </label>
</template>
