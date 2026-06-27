import process from 'node:process'

const PROD_URL = 'https://takt.mathieutu.dev'
const API_TOKEN = ''
const PROJECT_ID = ''

type CalendarEvent = {
  summary: string,
  description: string,
  location: string,
  start: string,
  end: string,
  totalHours: number,
}

type CalendarJson = {
  events: CalendarEvent[],
}

type Entry = {
  date: string,
  coverage: number,
  title: string,
}

const calendarUrl = process.argv[2]
if (!calendarUrl) {
  console.error('Usage: node scripts/sync-calendar.ts <calendar-url>')
  process.exit(1)
}

const calendar: CalendarJson = await fetch(calendarUrl).then(r => r.json())

const eventsByDate = Map.groupBy(calendar.events, event => event.start.slice(0, 10))

const entries: Entry[] = [...eventsByDate.entries()].map(([date, events]) => {
  const totalHours = events.reduce((sum, e) => sum + e.totalHours, 0)
  const coverage = Math.min(Math.round(totalHours / 7 * 100), 100)

  const titles = [...new Set(events.map(e => e.summary.split(' - ').slice(0, 2).join(' - ')))]

  return {
    date,
    coverage,
    title: titles.join(' + '),
  }
})

console.log(`Syncing ${entries.length} entries to project ${PROJECT_ID}...`)

const response = await fetch(`${PROD_URL}/api/projects/${PROJECT_ID}/entries`, {
  method: 'PATCH',
  headers: {
    'Authorization': `Bearer ${API_TOKEN}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  body: JSON.stringify({ entries }),
})

if (!response.ok) {
  const body = await response.text()
  console.error(`Error ${response.status}: ${body}`)
  process.exit(1)
}

const result = await response.json()
console.log('Done:', result.message)
