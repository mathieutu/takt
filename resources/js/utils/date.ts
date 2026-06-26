import { CalendarDate, getLocalTimeZone, today as getToday, isSameMonth, parseDate } from '@internationalized/date'

export type Day = { n: number, date: string, isWeekend: boolean, letter: string }

const DAY_LETTERS = ['D', 'L', 'M', 'M', 'J', 'V', 'S'] as const

export const today = getToday(getLocalTimeZone())
export const isToday = (date: string | undefined): boolean => date === today.toString()

export const formatMonthName = (month: number): string => {
  const name = new Intl.DateTimeFormat('fr-FR', { month: 'long' }).format(new Date().setMonth(month - 1))
  return name.charAt(0).toUpperCase() + name.slice(1)
}
export const formatDays = (days: number): string => `${days % 1 === 0 ? days : days.toFixed(1)} j`

export const daysInMonth = ({ year, month }: { year: number, month: number }): Day[] => {
  const count = new Date(year, month, 0).getDate()
  return Array.from({ length: count }, (_, i) => {
    const n = i + 1
    const date = `${year}-${String(month).padStart(2, '0')}-${String(n).padStart(2, '0')}`
    const dow = new Date(year, month - 1, n).getDay()
    return { n, date, isWeekend: dow === 0 || dow === 6, letter: DAY_LETTERS[dow] }
  })
}

export const formatDate = (date: string, withYear = false): string => new Date(date).toLocaleDateString('fr-FR', {
  day: 'numeric',
  month: 'short',
  ...(withYear ? { year: 'numeric' } : {}),
})

export const formatDateTime = (dateTime: string) => new Date(dateTime).toLocaleDateString('fr-FR', {
  day: 'numeric',
  month: 'short',
  hour: 'numeric',
  minute: '2-digit',
})

export const isInMonth = (date: string | null | undefined, year: number, month: number): boolean =>
  date != null && isSameMonth(parseDate(date), new CalendarDate(year, month, 1))

export const parseMonth = (month: string): { year: number, month: number } => {
  const [year, m] = month.split('-').map(Number)
  return { year: year!, month: m! }
}

export const formatDuration = (days: number): string => {
  const months = Math.floor(days / 30)
  const weeks = Math.floor((days % 30) / 7)
  const remainingDays = days % 30 % 7

  const parts: string[] = []
  if (months > 0) parts.push(`${months} mois`)
  if (weeks > 0) parts.push(`${weeks} semaine${weeks > 1 ? 's' : ''}`)
  if (remainingDays > 0) parts.push(`${remainingDays} jour${remainingDays > 1 ? 's' : ''}`)

  if (parts.length === 0) return '0 jour'
  if (parts.length === 1) return parts[0]!
  return `${parts.slice(0, -1).join(', ')} et ${parts.at(-1)}`
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
  if (coverage >= 17) return '⅙'
  if (coverage >= 14) return '⅐'
  if (coverage >= 13) return '⅛'
  if (coverage >= 11) return '⅑'
  return '⅑'
}
