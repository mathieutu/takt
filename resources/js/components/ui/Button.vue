<script setup lang="ts">
withDefaults(defineProps<{
  variant?: 'default' | 'outline' | 'ghost' | 'destructive'
  size?: 'default' | 'sm' | 'icon'
  disabled?: boolean
  type?: 'button' | 'submit'
}>(), { variant: 'default', size: 'default', type: 'button' })
</script>

<template>
  <button
    :type="type"
    :disabled="disabled"
    :class="[
      'inline-flex items-center justify-center font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:opacity-50 disabled:pointer-events-none cursor-pointer',
      variant === 'default' && 'bg-primary text-primary-foreground hover:bg-primary/90',
      variant === 'outline' && 'border border-border bg-background hover:bg-accent hover:text-accent-foreground',
      variant === 'ghost' && 'hover:bg-accent hover:text-accent-foreground',
      variant === 'destructive' && 'bg-destructive text-white hover:bg-destructive/90',
      size === 'default' && 'h-9 px-4 text-sm rounded-md',
      size === 'sm' && 'h-7 px-3 text-xs rounded-md',
      size === 'icon' && 'h-8 w-8 rounded-md',
    ]"
  ><slot /></button>
</template>
