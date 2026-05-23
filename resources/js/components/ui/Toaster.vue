<script setup lang="ts">
import {watch} from 'vue'
import {usePage} from '@inertiajs/vue3'
import {useToast} from '@/composables/useToast.ts'
import type {ToastType} from '@/composables/useToast.ts'

const page = usePage()
const {toasts, remove, fromFlash} = useToast()

watch(
    () => page.flash,
    (flash) => {
        console.log('Flash changed:', flash)
        if (flash) {
            fromFlash(flash)
        }
    },
    {immediate: true},
)

const styles: Record<ToastType, string> = {
    success: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    info: 'border-blue-200 bg-blue-50 text-blue-800',
    warn: 'border-amber-200 bg-amber-50 text-amber-800',
    error: 'border-red-200 bg-red-50 text-red-800',
    message: 'border-border bg-background text-foreground',
}
</script>

<template>
    <Teleport to="body">
        <div class="fixed right-4 bottom-4 z-50 flex flex-col gap-2">
            <TransitionGroup
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="translate-y-2 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="flex items-start gap-3 rounded-lg border px-4 py-3 text-sm shadow-md cursor-pointer max-w-sm"
                    :class="styles[(toast.type in styles ? toast.type : 'message') as keyof typeof styles]"
                    @click="remove(toast.id)"
                >
                    {{ toast.message }}
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
