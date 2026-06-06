import type { Day } from '@/utils/date.ts'
import { onMounted, type TemplateRef } from 'vue'
import { useGridNavigation } from '@/composables/useGridNavigation.ts'
import { TODAY } from '@/utils/date.ts'

type Project = {
  id: string,
  deleted_at?: string | null,
}

type EmitFn = {
  (event: 'cellClick', projectId: string, date: string): void,
  (event: 'actionClick', projectId: string, date: string): void,
}

export function useTimesheetKeyboard(
  tableRef: TemplateRef<HTMLTableElement>,
  getDays: () => Day[],
  getProjects: () => Project[],
  emit: EmitFn,
) {
  const { resolveAction } = useGridNavigation()

  onMounted(() => {
    const todayIndex = getDays().findIndex(d => d.date === TODAY)
    if (todayIndex === -1) return

    const firstRow = tableRef.value?.querySelector('tbody tr')
    const cell = firstRow?.querySelectorAll('td')[todayIndex + 1] as HTMLElement
    cell?.focus()
  })

  function focusCell(rowIndex: number, colIndex: number) {
    const rows = tableRef.value?.querySelectorAll('tbody tr')
    if (!rows?.[rowIndex]) return

    const cell = rows[rowIndex].querySelectorAll('td')[colIndex + 1] as HTMLElement
    cell?.focus()
    cell?.scrollIntoView({ block: 'nearest', inline: 'nearest' })
  }

  function resolveCoords(action: string, rowIndex: number, colIndex: number): [number, number] | null {
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

  function handleCellKeydown(event: KeyboardEvent, project: Project, day: Day, rowIndex: number, colIndex: number) {
    const action = resolveAction(event)
    if (!action) return

    event.preventDefault()

    if (action === 'main') {
      if (!project.deleted_at) emit('cellClick', project.id, day.date)
      return
    }

    if (action === 'details') {
      emit('actionClick', project.id, day.date)
      return
    }

    const coords = resolveCoords(action, rowIndex, colIndex)
    if (!coords) return

    focusCell(...coords)
  }

  return { tableRef, handleCellKeydown }
}
