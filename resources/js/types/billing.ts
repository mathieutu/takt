export type EntryData = { coverage: number, title: string, description: string, billable: boolean }

export type MonthInvoice = {
  id: string,
  amount: number,
  discount_amount: number,
  discount_percent: number | null,
  net_amount: number,
  paid_at: string | null,
  created_at: string,
  notes: string | null,
}

export type MonthRow = {
  month: string,
  daily_rate: number,
  monthly_budget: number | null,
  cumulative_budget: number | null,
  worked_days: number,
  billable_days: number,
  worked: number,
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
  months_with_entries_count: number,
  total_worked_days: number,
  total_billable_days: number,
  total_worked: number,
  total_invoiced: number,
  total_invoiced_days: number,
  total_discount: number,
  to_invoice: number,
  to_invoice_days: number,
  to_pay: number,
  to_pay_days: number,
  unbilled_since: string | null,
}

// Days where more than one day is billed to the same client across its projects (server-computed,
// see Client::overbookedDays()): `total_coverage` is in percent of a day, `projects` the split by project.
export type DuplicateBillingDay = { date: string, total_coverage: number, projects: Record<string, number> }
