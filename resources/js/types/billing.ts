export type EntryData = { coverage: number, title: string, description: string }

export type MonthInvoice = {
  id: string,
  amount: number,
  paid_at: string | null,
  created_at: string,
  notes: string | null,
}

export type MonthRow = {
  month: string,
  days_worked: number,
  entries: Record<string, EntryData>,
  invoices: MonthInvoice[],
}

export type ProjectWithBilling = {
  id: string,
  name: string,
  daily_rate: number,
  max_month_budget: number | null,
  max_total_budget: number | null,
  is_inactive: boolean,
  client: { id: string, name: string },
  months: MonthRow[],
  months_elapsed: number,
  months_with_entries_count: number,
}
