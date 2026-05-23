export type Day = { n: number, date: string, isWeekend: boolean, letter: string }

const DAY_LETTERS = ['D', 'L', 'M', 'M', 'J', 'V', 'S'] as const

export const TODAY = new Date().toISOString().slice(0, 10)

export const formatMonthName = (year: number, month: number): string => {
  const name = new Intl.DateTimeFormat('fr-FR', { month: 'long' }).format(new Date(year, month - 1))
  return name.charAt(0).toUpperCase() + name.slice(1)
}
export const formatDays = (days: number): string => `${days % 1 === 0 ? days : days.toFixed(1)}j`

export const daysInMonth = ({ year, month }: { year: number, month: number }): Day[] => {
  const count = new Date(year, month, 0).getDate()
  return Array.from({ length: count }, (_, i) => {
    const n = i + 1
    const date = `${year}-${String(month).padStart(2, '0')}-${String(n).padStart(2, '0')}`
    const dow = new Date(year, month - 1, n).getDay()
    return { n, date, isWeekend: dow === 0 || dow === 6, letter: DAY_LETTERS[dow] }
  })
}

export const formatDate = (date: string): string => new Date(date).toLocaleDateString('fr-FR', {
  day: 'numeric',
  month: 'short',
})
