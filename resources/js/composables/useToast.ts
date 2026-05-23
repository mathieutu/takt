import {ref} from 'vue'
import type {Flash} from "@/types/inertia";

export type ToastType = keyof Flash

export interface Toast {
    id: number
    type: ToastType
    message: string
}

const toasts = ref<Toast[]>([])
let nextId = 0

export function useToast() {
    function add(type: ToastType, message: string) {
        const id = nextId++
        toasts.value.push({id, type, message})
        setTimeout(() => remove(id), 4000)
    }

    function remove(id: number) {
        toasts.value = toasts.value.filter((t) => t.id !== id)
    }

    function fromFlash(flash: Flash) {
        Object.entries(flash).forEach(([type, message]) => {
            add(type as ToastType, message)
        })
    }

    return {toasts, add, remove, fromFlash}
}
