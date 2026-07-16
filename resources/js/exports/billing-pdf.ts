import { coverageLabel, formatDays } from '@/utils/date'
import { formatCurrency } from '@/utils/number'

document.querySelectorAll<HTMLElement>('[data-currency-cents]').forEach(el => {
  el.textContent = formatCurrency(Number(el.dataset.currencyCents))
})

document.querySelectorAll<HTMLElement>('[data-days]').forEach(el => {
  el.textContent = formatDays(Number(el.dataset.days))
})

document.querySelectorAll<HTMLElement>('[data-coverage]').forEach(el => {
  el.textContent = coverageLabel(Number(el.dataset.coverage))
})
