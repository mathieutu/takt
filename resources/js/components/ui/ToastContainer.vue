<script setup lang="ts">
import { useToast } from '../../composables/useToast'
import { CheckCircle, XCircle, Info, X } from 'lucide-vue-next'

const { toasts, dismiss } = useToast()
</script>

<template>
  <Teleport to="body">
    <div class="fixed bottom-4 right-4 z-[100] flex flex-col gap-2 pointer-events-none">
      <TransitionGroup
        enter-active-class="transition-all duration-300"
        enter-from-class="opacity-0 translate-y-2 scale-95"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition-all duration-200"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 scale-95"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="[
            'pointer-events-auto flex items-center gap-3 rounded-lg border px-4 py-3 shadow-md min-w-[280px] max-w-sm',
            toast.type === 'success' && 'bg-background border-emerald-200 text-foreground',
            toast.type === 'error' && 'bg-background border-destructive/30 text-foreground',
            toast.type === 'info' && 'bg-background border-border text-foreground',
          ]"
        >
          <CheckCircle v-if="toast.type === 'success'" class="h-4 w-4 shrink-0 text-emerald-500" />
          <XCircle v-else-if="toast.type === 'error'" class="h-4 w-4 shrink-0 text-destructive" />
          <Info v-else class="h-4 w-4 shrink-0 text-muted-foreground" />
          <p class="flex-1 text-sm">{{ toast.message }}</p>
          <button
            type="button"
            class="ml-auto shrink-0 text-muted-foreground hover:text-foreground"
            @click="dismiss(toast.id)"
          >
            <X class="h-3.5 w-3.5" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>
