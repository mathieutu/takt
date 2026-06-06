export type GridNavAction =
  | 'main' // Space
  | 'details' // Enter
  | 'move-up' | 'move-down' | 'move-left' | 'move-right' // Arrow
  | 'jump-up' | 'jump-down' | 'jump-left' | 'jump-right' // Ctrl/Cmd + Arrow
  | 'jump-top-left' | 'jump-bottom-right' // Ctrl/Cmd + Home / End
  | 'step-left' | 'step-right' // Alt + Arrow

export function useGridNavigation() {
  function resolveAction(event: KeyboardEvent): GridNavAction | null {
    const { key, metaKey, ctrlKey, altKey } = event
    const jump = metaKey || ctrlKey

    if (key === ' ') return 'main'
    if (key === 'Enter') return 'details'

    if (key === 'Home') return jump ? 'jump-top-left' : 'jump-left'
    if (key === 'End') return jump ? 'jump-bottom-right' : 'jump-right'

    if (key === 'ArrowUp') return jump ? 'jump-up' : 'move-up'
    if (key === 'ArrowDown') return jump ? 'jump-down' : 'move-down'
    if (key === 'ArrowLeft') {
      if (jump) return 'jump-left'
      if (altKey) return 'step-left'
      return 'move-left'
    }
    if (key === 'ArrowRight') {
      if (jump) return 'jump-right'
      if (altKey) return 'step-right'
      return 'move-right'
    }

    return null
  }

  return { resolveAction }
}
