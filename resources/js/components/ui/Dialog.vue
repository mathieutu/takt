<script setup lang="ts">
import { watch, onUnmounted } from 'vue'

const props = defineProps<{
  open: boolean
  title?: string
  description?: string
  maxWidth?: string
}>()

const emit = defineEmits<{
  close: []
}>()

function onKey(e: KeyboardEvent) {
  if (e.key === 'Escape') emit('close')
}

watch(() => props.open, (val) => {
  if (val) document.addEventListener('keydown', onKey)
  else     document.removeEventListener('keydown', onKey)
}, { immediate: true })

onUnmounted(() => document.removeEventListener('keydown', onKey))
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
        <div class="absolute inset-0 bg-black/50" @mousedown="$emit('close')" />

        <!-- Dialog panel -->
        <div
          :class="[
            'relative z-10 w-full rounded-lg border border-border bg-background shadow-lg',
            maxWidth ?? 'max-w-md',
          ]"
        >
          <!-- Header -->
          <div v-if="title || $slots.header" class="flex items-start justify-between p-6 pb-4">
            <div>
              <slot name="header">
                <h2 class="text-base font-semibold text-foreground">{{ title }}</h2>
                <p v-if="description" class="mt-1 text-sm text-muted-foreground">{{ description }}</p>
              </slot>
            </div>
            <button
              type="button"
              class="ml-4 flex h-6 w-6 shrink-0 items-center justify-center rounded-sm text-muted-foreground hover:text-foreground focus-visible:outline-none"
              @click="$emit('close')"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Content -->
          <div class="px-6 pb-6" :class="{ 'pt-6': !title && !$slots.header }">
            <slot />
          </div>

          <!-- Footer -->
          <div v-if="$slots.footer" class="flex justify-end gap-2 border-t border-border px-6 py-4">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
