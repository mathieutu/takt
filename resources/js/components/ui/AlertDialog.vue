<script setup lang="ts">
defineProps<{
  open: boolean
  title: string
  description?: string
  confirmLabel?: string
  cancelLabel?: string
  variant?: 'default' | 'destructive'
}>()

defineEmits<{
  confirm: []
  cancel: []
}>()
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-150"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50" />

        <!-- Dialog panel -->
        <div class="relative z-10 w-full max-w-sm rounded-lg border border-border bg-background shadow-lg p-6">
          <h2 class="text-base font-semibold text-foreground">{{ title }}</h2>
          <p v-if="description" class="mt-2 text-sm text-muted-foreground">{{ description }}</p>
          <div class="mt-6 flex justify-end gap-2">
            <button
              type="button"
              class="h-9 px-4 text-sm font-medium rounded-md border border-border bg-background hover:bg-accent hover:text-accent-foreground transition-colors"
              @click="$emit('cancel')"
            >
              {{ cancelLabel ?? 'Annuler' }}
            </button>
            <button
              type="button"
              :class="[
                'h-9 px-4 text-sm font-medium rounded-md transition-colors',
                variant === 'destructive'
                  ? 'bg-destructive text-white hover:bg-destructive/90'
                  : 'bg-primary text-primary-foreground hover:bg-primary/90',
              ]"
              @click="$emit('confirm')"
            >
              {{ confirmLabel ?? 'Confirmer' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
