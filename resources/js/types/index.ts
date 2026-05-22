import type { Component } from 'vue'

// ── Inertia page props ────────────────────────────────────────────────────────

export interface AuthUser {
    name: string
    email: string
}

export interface PageProps {
    auth: { user: AuthUser | null }
    flash: { success?: string | null }
    csrfToken: string
    [key: string]: unknown
}

declare module '@inertiajs/vue3' {
    interface PageProps {
        auth: { user: AuthUser | null }
        flash: { success?: string | null }
        csrfToken: string
        [key: string]: unknown
    }
}

// ── Domain types ──────────────────────────────────────────────────────────────

export interface Client {
    id: number
    name: string
    daily_rate: number
    is_owner?: boolean
}

export interface Project {
    id: number
    name: string
    client_id: number
    client_name: string
    description: string
    daily_rate: number | null
    is_owner: boolean
    is_shared?: boolean
    created_at?: string
}

export interface Report {
    id: number
    project_id: number
    start_date: string
    day_coverage: number
    label: string
    comments: string
}

export interface MonthRow {
    month: string
    days_worked: number
    billing_entry_id: number | null
    amount_billed: number
    payment_date: string | null
    notes: string | null
}

export interface ProjectData {
    id: number
    name: string
    daily_rate: number
    max_budget: number | null
    client_name: string
    months: MonthRow[]
}

// ── UI types ──────────────────────────────────────────────────────────────────

export interface NavItem {
    route: string
    label: string
    icon: Component
    exact?: boolean
}

export interface MenuItem {
    label: string
    icon?: Component
    action: () => void
    variant?: 'default' | 'destructive'
}

// Legacy – kept for compatibility, will be removed after full cleanup
export type DayValue = 0 | 0.5 | 1
