<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

defineProps<{
  items: { label: string; icon?: string; action: () => void; variant?: 'default' | 'destructive' }[]
}>()

const open = ref(false)
const containerRef = ref<HTMLElement | null>(null)

function toggle() {
  open.value = !open.value
}

function handleOutside(e: MouseEvent) {
  if (containerRef.value && !containerRef.value.contains(e.target as Node)) {
    open.value = false
  }
}

onMounted(() => document.addEventListener('mousedown', handleOutside))
onUnmounted(() => document.removeEventListener('mousedown', handleOutside))
</script>

<template>
  <div ref="containerRef" class="relative">
    <div @click="toggle">
      <slot />
    </div>
    <Transition
      enter-active-class="transition-all duration-100"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition-all duration-100"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="open"
        class="absolute right-0 top-full z-50 mt-1 min-w-35 overflow-hidden rounded-md border border-border bg-background shadow-md"
      >
        <button
          v-for="(item, i) in items"
          :key="i"
          type="button"
          :class="[
            'flex w-full items-center gap-2 px-3 py-2 text-sm transition-colors',
            item.variant === 'destructive'
              ? 'text-destructive hover:bg-destructive/10'
              : 'text-foreground hover:bg-accent',
          ]"
          @click="() => { item.action(); open = false }"
        >
          {{ item.label }}
        </button>
      </div>
    </Transition>
  </div>
</template>
