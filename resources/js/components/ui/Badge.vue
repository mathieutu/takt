<script setup lang="ts">
withDefaults(defineProps<{
  variant?: 'default' | 'secondary' | 'outline' | 'destructive' | 'success' | 'warning'
}>(), { variant: 'default' })
</script>

<template>
  <span :class="[
    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
    variant === 'default' && 'bg-primary text-primary-foreground',
    variant === 'secondary' && 'bg-secondary text-secondary-foreground',
    variant === 'outline' && 'border border-border text-foreground',
    variant === 'destructive' && 'bg-destructive text-white',
    variant === 'success' && 'bg-emerald-100 text-emerald-700',
    variant === 'warning' && 'bg-amber-100 text-amber-700',
  ]"><slot /></span>
</template>
