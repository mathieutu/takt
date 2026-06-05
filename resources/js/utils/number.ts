export const formatCurrency = (amount: number): string => new Intl.NumberFormat(undefined, {
  style: 'currency',
  currency: 'EUR',
  maximumFractionDigits: 0,
}).format(amount / 100)
