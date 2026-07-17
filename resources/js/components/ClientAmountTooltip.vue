<script setup lang="ts">
import { formatCurrency } from '@/utils/number'

defineProps<{
  title: string,
  items: Array<{ clientName: string, amount: number }>,
}>()
</script>

<template>
  <div class="min-w-52 space-y-1 text-xs">
    <p class="font-medium text-muted uppercase tracking-wide text-[10px]">{{ title }}</p>
    <div v-for="item in items" :key="item.clientName" class="flex justify-between gap-4">
      <span class="truncate text-muted">{{ item.clientName }}</span>
      <span class="shrink-0 font-medium">{{ formatCurrency(item.amount) }}</span>
    </div>
    <div v-if="$slots.footer" class="border-t border-default pt-1.5 text-muted">
      <slot name="footer" />
    </div>
  </div>
</template>
