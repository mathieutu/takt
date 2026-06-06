export type Day = { n: number, date: string, isWeekend: boolean, letter: string }

const DAY_LETTERS = ['S', 'M', 'T', 'W', 'T', 'F', 'S'] as const

export const TODAY = new Date().toISOString().slice(0, 10)

export const formatMonthName = (year: number, month: number): string => {
  const name = new Intl.DateTimeFormat(undefined, { month: 'long' }).format(new Date(year, month - 1))
  return name.charAt(0).toUpperCase() + name.slice(1)
}
export const formatDays = (days: number): string => `${days % 1 === 0 ? days : days.toFixed(1)}d`

export const daysInMonth = ({ year, month }: { year: number, month: number }): Day[] => {
  const count = new Date(year, month, 0).getDate()
  return Array.from({ length: count }, (_, i) => {
    const n = i + 1
    const date = `${year}-${String(month).padStart(2, '0')}-${String(n).padStart(2, '0')}`
    const dow = new Date(year, month - 1, n).getDay()
    return { n, date, isWeekend: dow === 0 || dow === 6, letter: DAY_LETTERS[dow] }
  })
}

export const formatDate = (date: string): string => new Date(date).toLocaleDateString(undefined, {
  day: 'numeric',
  month: 'short',
})

export const formatDateTime = (dateTime: string) => new Date(dateTime).toLocaleDateString(undefined, {
  day: 'numeric',
  month: 'short',
  hour: 'numeric',
  minute: '2-digit',
})

export const parseMonth = (month: string): { year: number, month: number } => {
  const [year, m] = month.split('-').map(Number)
  return { year: year!, month: m! }
}

export const coverageLabel = (coverage: number): string => {
  if (coverage >= 100) return '1'
  if (coverage >= 80) return '⅘'
  if (coverage >= 75) return '¾'
  if (coverage >= 66) return '⅔'
  if (coverage >= 60) return '⅗'
  if (coverage >= 50) return '½'
  if (coverage >= 40) return '⅖'
  if (coverage >= 33) return '⅓'
  if (coverage >= 25) return '¼'
  if (coverage >= 20) return '⅕'
  return '⅐'
}
