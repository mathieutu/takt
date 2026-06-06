import type { Flash } from '@/types/inertia'
import { usePage } from '@inertiajs/vue3'
import { useToast } from '@nuxt/ui/composables'
import { watch } from 'vue'

const COLOR_MAP: Record<keyof Flash, 'success' | 'info' | 'warning' | 'error' | 'neutral'> = {
  success: 'success',
  info: 'info',
  warn: 'warning',
  error: 'error',
  message: 'neutral',
}

export const useFlash = () => {
  const toast = useToast()
  const page = usePage()

  watch(
    () => page.flash,
    flash => {
      if (!flash) return
      Object.entries(flash)
        .filter(([, message]) => message)
        .forEach(([type, message]) => toast.add({
          title: message as string,
          color: COLOR_MAP[type as keyof Flash] ?? 'neutral',
        }))
    },
    { immediate: true },
  )
}
