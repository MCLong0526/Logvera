// Display labels + colour classes for the task enums (mirrors the backend constants).

export const STATUS = {
  not_started: { label: 'Not Started', class: 'bg-slate-100 text-slate-600' },
  in_progress: { label: 'In Progress', class: 'bg-blue-100 text-blue-700' },
  on_hold: { label: 'On Hold', class: 'bg-amber-100 text-amber-700' },
  completed: { label: 'Completed', class: 'bg-green-100 text-green-700' },
  cancelled: { label: 'Cancelled', class: 'bg-red-100 text-red-700' },
}

export const PRIORITY = {
  low: { label: 'Low', class: 'bg-slate-100 text-slate-600' },
  medium: { label: 'Medium', class: 'bg-sky-100 text-sky-700' },
  high: { label: 'High', class: 'bg-orange-100 text-orange-700' },
  critical: { label: 'Critical', class: 'bg-red-100 text-red-700' },
}

export const TYPE = {
  development: 'Development',
  bug_fix: 'Bug Fix',
  testing: 'Testing',
  meeting: 'Meeting',
  documentation: 'Documentation',
  research: 'Research',
  others: 'Others',
}

export const STATUS_OPTIONS = Object.entries(STATUS).map(([value, v]) => ({ value, label: v.label }))
export const PRIORITY_OPTIONS = Object.entries(PRIORITY).map(([value, v]) => ({ value, label: v.label }))
export const TYPE_OPTIONS = Object.entries(TYPE).map(([value, label]) => ({ value, label }))

export function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

export function formatDateTime(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('en-GB', {
    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
  })
}

export function formatBytes(bytes) {
  if (!bytes) return '0 B'
  const units = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return `${(bytes / 1024 ** i).toFixed(1)} ${units[i]}`
}
