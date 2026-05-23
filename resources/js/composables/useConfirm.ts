import { useOverlay } from '@nuxt/ui/composables'
import ConfirmDialog from '@/components/ConfirmDialog.vue'

export type ConfirmDialogOptions = {
  title: string,
  description?: string,
  onConfirm?: () => void,
}

export const useConfirm = (defaultOptions: Partial<ConfirmDialogOptions>) => {
  const overlay = useOverlay()

  return async (options: Partial<ConfirmDialogOptions>): Promise<boolean> => {
    const props = { ...defaultOptions, ...options }
    const modal = overlay.create(ConfirmDialog, {
      destroyOnClose: true,
      props,
    })

    const confirmed = await modal.open()

    if (confirmed && props.onConfirm) {
      props.onConfirm()
    }

    return confirmed
  }
}
