export const formatCurrency = (amount: number): string => new Intl.NumberFormat(navigator.languages, {
  style: 'currency',
  currency: 'EUR',
  maximumFractionDigits: 0,
}).format(amount / 100).replaceAll(' ', ' ')
