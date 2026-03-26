<script setup lang="ts">
defineProps<{
  modelValue: string
  tabs: { value: string; label: string }[]
}>()

defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <div class="flex gap-1 rounded-lg bg-muted p-1">
    <button
      v-for="tab in tabs"
      :key="tab.value"
      type="button"
      :class="[
        'w-fit rounded-md px-3 py-1.5 text-sm font-medium transition-colors focus-visible:outline-none',
        modelValue === tab.value
          ? 'bg-background text-foreground shadow-sm'
          : 'text-muted-foreground hover:text-foreground',
      ]"
      @click="$emit('update:modelValue', tab.value)"
    >
      {{ tab.label }}
    </button>
  </div>
</template>
