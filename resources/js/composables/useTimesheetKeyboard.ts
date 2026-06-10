import type { Day } from '@/utils/date.ts'
import { nextTick, onMounted, onUnmounted, ref, type TemplateRef, watch } from 'vue'
import { useGridNavigation } from '@/composables/useGridNavigation.ts'
import { isToday } from '@/utils/date.ts'

type Project = {
  id: string,
  deleted_at?: string | null,
}

type EmitFn = {
  (event: 'cellClick', projectId: string, date: string): void,
  (event: 'actionClick', projectId: string, date: string): void,
  (event: 'setCoverage', projectId: string, date: string, coverage: number): void,
  (event: 'nextMonth'): void,
  (event: 'prevMonth'): void,
}

export const useTimesheetKeyboard = (
  tableRef: TemplateRef<HTMLTableElement>,
  getDays: () => Day[],
  getProjects: () => Project[],
  emit: EmitFn,
) => {
  const { resolveAction } = useGridNavigation()
  const pendingFocus = ref<'first' | 'last' | null>(null)

  const getActiveCell = (): HTMLElement | null => {
    const el = document.activeElement as HTMLElement | null
    if (el?.tagName === 'TD' && tableRef.value?.contains(el)) return el
    return null
  }

  const focusCell = (rowIndex: number, colIndex: number) => {
    const rows = tableRef.value?.querySelectorAll('tbody tr')
    if (!rows?.[rowIndex]) return

    const cell = rows[rowIndex].querySelectorAll('td')[colIndex + 1] as HTMLElement
    cell?.focus()
    cell?.scrollIntoView({ block: 'nearest', inline: 'nearest' })
  }

  const navigateMonth = (direction: 'next' | 'prev') => {
    pendingFocus.value = direction === 'next' ? 'first' : 'last'
    direction === 'next' ? emit('nextMonth') : emit('prevMonth')
  }

  const resolveCoords = (action: string, rowIndex: number, colIndex: number): [number, number] | null => {
    const lastCol = getDays().length - 1
    const lastRow = getProjects().length - 1

    if (action === 'move-right') return [rowIndex, Math.min(colIndex + 1, lastCol)]
    if (action === 'move-left') return [rowIndex, Math.max(colIndex - 1, 0)]
    if (action === 'move-down') return [Math.min(rowIndex + 1, lastRow), colIndex]
    if (action === 'move-up') return [Math.max(rowIndex - 1, 0), colIndex]
    if (action === 'jump-down') return [lastRow, colIndex]
    if (action === 'jump-up') return [0, colIndex]
    if (action === 'step-right') return [rowIndex, Math.min(colIndex + 7, lastCol)]
    if (action === 'step-left') return [rowIndex, Math.max(colIndex - 7, 0)]
    if (action === 'jump-left') return [rowIndex, 0]
    if (action === 'jump-right') return [rowIndex, lastCol]
    if (action === 'jump-top-left') return [0, 0]
    if (action === 'jump-bottom-right') return [lastRow, lastCol]

    return null
  }

  const handleWindowKeydown = (event: KeyboardEvent) => {
    const target = event.target as HTMLElement
    if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT' || target.isContentEditable) return

    const digit = Number.parseInt(event.key)
    if (digit >= 0 && digit <= 9) {
      const activeCell = getActiveCell()
      if (!activeCell || activeCell.dataset.deleted) return
      event.preventDefault()
      emit('setCoverage', activeCell.dataset.projectId!, activeCell.dataset.date!, digit === 0 ? 0 : Math.round(100 / digit))
      return
    }

    const action = resolveAction(event)
    if (!action) return

    if (action === 'next') {
      event.preventDefault()
      navigateMonth('next')
      return
    }

    if (action === 'prev') {
      event.preventDefault()
      navigateMonth('prev')
      return
    }

    const activeCell = getActiveCell()
    const rowIndex = Number.parseInt(activeCell?.dataset.row ?? '0')
    const colIndex = Number.parseInt(activeCell?.dataset.col ?? '0')

    if (action === 'main') {
      if (!activeCell || activeCell.dataset.deleted) return
      event.preventDefault()
      emit('cellClick', activeCell.dataset.projectId!, activeCell.dataset.date!)
      return
    }

    if (action === 'details') {
      if (!activeCell) return
      event.preventDefault()
      emit('actionClick', activeCell.dataset.projectId!, activeCell.dataset.date!)
      return
    }

    // Arrow boundary → month navigation (only when a cell is focused)
    if (action === 'move-right' && activeCell && colIndex === getDays().length - 1) {
      event.preventDefault()
      navigateMonth('next')
      return
    }

    if (action === 'move-left' && activeCell && colIndex === 0) {
      event.preventDefault()
      navigateMonth('prev')
      return
    }

    // Other navigation: if no cell focused, enter from the logical edge
    if (!activeCell) {
      event.preventDefault()
      const lastCol = getDays().length - 1
      const lastRow = getProjects().length - 1
      const todayIndex = getDays().findIndex(d => isToday(d.date))
      const isLeftward = action === 'move-left' || action === 'step-left' || action === 'jump-left' || action === 'jump-top-left' || action === 'jump-bottom-right'
      const isUpward = action === 'move-up' || action === 'jump-up'
      focusCell(isUpward ? lastRow : 0, isLeftward ? lastCol : (todayIndex !== -1 ? todayIndex : 0))
      return
    }

    event.preventDefault()
    const coords = resolveCoords(action, rowIndex, colIndex)
    if (!coords) return
    focusCell(...coords)
  }

  onMounted(() => {
    const todayIndex = getDays().findIndex(d => isToday(d.date))
    if (todayIndex !== -1) {
      const firstRow = tableRef.value?.querySelector('tbody tr')
      const cell = firstRow?.querySelectorAll('td')[todayIndex + 1] as HTMLElement
      cell?.focus()
    }

    window.addEventListener('keydown', handleWindowKeydown)
  })

  onUnmounted(() => {
    window.removeEventListener('keydown', handleWindowKeydown)
  })

  watch(() => getDays(), async () => {
    if (!pendingFocus.value) return
    await nextTick()
    focusCell(0, pendingFocus.value === 'first' ? 0 : getDays().length - 1)
    pendingFocus.value = null
  })

  return { tableRef }
}
