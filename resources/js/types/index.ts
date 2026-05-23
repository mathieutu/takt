import type { Component } from 'vue'

// ── Inertia page props ────────────────────────────────────────────────────────

export type AuthUser = {
  name: string,
  email: string,
}

export type PageProps = {
  auth: { user: AuthUser | null },
  csrfToken: string,

  [key: string]: unknown,
}

// ── Domain types ──────────────────────────────────────────────────────────────

export type Client = {
  id: number,
  name: string,
  daily_rate: number,
  is_owner?: boolean,
}

export type Project = {
  id: number,
  name: string,
  client_id: number,
  client_name: string,
  description: string,
  daily_rate: number | null,
  is_owner: boolean,
  is_shared?: boolean,
  created_at?: string,
}

export type Report = {
  id: number,
  project_id: number,
  date: string,
  coverage: number,
  title: string,
  description: string,
}

export type MonthRow = {
  month: string,
  days_worked: number,
  billing_entry_id: number | null,
  amount_billed: number,
  payment_date: string | null,
  notes: string | null,
}

export type ProjectData = {
  id: number,
  name: string,
  daily_rate: number,
  max_budget: number | null,
  client_name: string,
  months: MonthRow[],
}

// ── UI types ──────────────────────────────────────────────────────────────────

export type NavItem = {
  route: string,
  label: string,
  icon: Component,
  exact?: boolean,
}

export type MenuItem = {
  label: string,
  icon?: Component,
  action: () => void,
  variant?: 'default' | 'destructive',
}

// Legacy – kept for compatibility, will be removed after full cleanup
export type DayValue = 0 | 0.5 | 1
