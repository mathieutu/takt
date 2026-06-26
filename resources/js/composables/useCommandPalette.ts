import { ref } from 'vue'

export type CommandPaletteData = {
  clients: Array<{ id: string, name: string }>,
  projects: Array<{ id: string, name: string, client: { id: string, name: string } }>,
}

const isOpen = ref(false)
const data = ref<CommandPaletteData | null>(null)

export const useCommandPalette = () => ({
  isOpen,
  data,
  open: () => {
    isOpen.value = true
  },
  close: () => {
    isOpen.value = false
  },
  toggle: () => {
    isOpen.value = !isOpen.value
  },
})
