export type DayValue = 0 | 0.5 | 1

export interface User {
  id: string
  name: string
  email: string
  role: string
  tjm: number
  avatar?: string
}

export interface Client {
  id: string
  name: string
  contactName: string
  email: string
  phone?: string
  address?: string
  color: string
  createdAt: string
  active: boolean
}

export interface Project {
  id: string
  clientId: string
  name: string
  description?: string
  status: 'active' | 'paused' | 'completed'
  tjm?: number
  startDate: string
  endDate?: string
  createdAt: string
}

export interface CRAEntry {
  id: string
  date: string
  projectId: string
  value: DayValue
  note?: string
}
