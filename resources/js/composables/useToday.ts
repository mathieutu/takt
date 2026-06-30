import { getLocalTimeZone, today as getToday } from '@internationalized/date'
import { onUnmounted, ref } from 'vue'

export const useToday = () => {
  const today = ref(getToday(getLocalTimeZone()))
  let timeoutId: ReturnType<typeof setTimeout> | null = null

  const scheduleNextUpdate = () => {
    const now = new Date()
    const nextMidnight = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 0, 0, 0, 0)
    const msUntilMidnight = nextMidnight.getTime() - now.getTime()

    timeoutId = setTimeout(() => {
      today.value = getToday(getLocalTimeZone())
      scheduleNextUpdate()
    }, msUntilMidnight)
  }

  scheduleNextUpdate()
  onUnmounted(() => {
    if (timeoutId !== null) clearTimeout(timeoutId)
  })

  const isToday = (date: string | undefined): boolean => date === today.value.toString()

  return { today, isToday }
}
